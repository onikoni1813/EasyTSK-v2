# Easytsk V2 - Core Business Logics

> This document is the **single source of truth** for all financial, gamification, and anti-fraud
> business logic implemented in Easytsk V2. It is reverse-documented from the actual codebase
> (as of 2026-07-24) so that any future change to money/XP/reward logic MUST be reflected here,
> and any new logic MUST be implemented exactly as written here.
>
> Referenced by `Global Project Rules.md` §3: *"For any business logic (withdrawals, pending
> balances, locked rewards, anti-fraud), you MUST read and implement exactly what is written in
> `core_logics.md`."*

---

## 1. Balance System

Each `User` has 3 separate balance columns (all `decimal:2`, never allowed to go negative):

| Column | Meaning |
|---|---|
| `main_balance` | Withdrawable / spendable balance. Only this balance can be withdrawn or spent on campaigns. |
| `pending_balance` | Offerwall rewards waiting out their hold/verification period before being released to `main_balance`. |
| `locked_balance` | Referral bonus rewards locked to the **referrer** until the referred user reaches an earning milestone. |

### Rules
- Every mutation to any balance column MUST run inside `DB::transaction()`.
- `User::addMainBalance(float $amount)` — safely increments `main_balance` inside a transaction.
- `User::deductMainBalance(float $amount): bool` — uses `lockForUpdate()` (row lock) to prevent race
  conditions, only deducts if sufficient funds exist, returns `false` otherwise. **Any code path that
  spends `main_balance` on behalf of the user (campaigns, withdrawals) must use this pattern
  (lock-then-check-then-decrement) to prevent double-spend under concurrent requests.**
- Balances must never be decremented below `0`. Controllers must check sufficiency **before** the
  `DB::transaction()` block whenever user-facing rejection messages are needed (e.g. withdrawal,
  campaign creation), in addition to the safe row-locking deduction helpers for concurrency safety.

---

## 2. Task Completion & Reward Logic

Tasks (`Task` model) have 4 types: `shortlink`, `secret_code`, `social` (social proof / screenshot),
`user_ad` (user-submitted micro-campaigns, see §6).

### 2.1 Task Rotation / Anti-Repeat Limit (`daily_ip_limit`)
Enforced identically in `TaskController::checkTaskRotationLimit()` for every task type, keyed by
**either matching `user_id` OR matching `ip_address`** (this blocks both "farm accounts on one IP"
and "same account switching IP"):

- `daily_ip_limit > 0` → task may be completed at most `daily_ip_limit` times **per calendar day**
  by this user/IP combination.
- `daily_ip_limit == 0` → task is a **one-time-only** task; once completed once (ever), it is
  permanently hidden/blocked for that user/IP.
- If the limit is reached, the task is **hidden from the task list** (`TaskController::index`) and
  submission endpoints reject with an error.

### 2.2 Shortlink Tasks (`type = shortlink`)
Instant auto-approval. On submission:
1. Validate rotation limit.
2. Create `UserTask` with `status = approved` immediately (no manual review needed).
3. `user->addMainBalance(reward_coins)`
4. `GamificationService::awardXp(user, reward_xp)`
5. `ReferralService::recordReferredUserEarning(user, reward_coins)`

### 2.3 Secret Code Tasks (`type = secret_code`)
- User submits a code, compared with `trim()` (case-sensitive) against `task->secret_code`.
- `secret_code` field may hold **multiple comma-separated codes** — `secret_code_count` communicates
  to the frontend how many input fields to render.
- Correct match → instantly `approved` + same reward flow as shortlink (steps 3-5 above).
- Incorrect match → error returned, **no health penalty** on this direct endpoint
  (`submitSecretCode`). (Contrast with §2.4 where wrong codes submitted via the social-proof/dynamic
  path DO cost health — see below.)

### 2.4 Social Proof / Screenshot Tasks (`type = social`, and dynamic proof tasks)
Two submission paths exist:

**A. Dynamic proof path** (`is_dynamic=true`, task has `proof_requirements` array) — used by the
admin-configurable proof builder. Each requirement item has `{id, type: text|image, label,
is_required}`.
- All `is_required` items must be present (text non-empty, or image file uploaded).
- Every image file is validated: must be a valid image (`jpeg/png/gif/webp`), max 5MB.
- Every uploaded image is hashed & duplicate-checked via `StorageSaverService` (see §7).
- `UserTask` created with `status = pending` **by default**.
- **Auto-approval exception:** if the task also has `type = secret_code` (or a non-empty
  `secret_code`), the code(s) submitted via `text` proof requirements are compared. If they match →
  auto-approve immediately (same reward flow as §2.2). If they DO NOT match → `deductHealth(10)`,
  the `UserTask` row is deleted, and the images already saved are **not** cleaned up by this path
  (only cleaned up via `StorageSaverService::cleanupReviewedScreenshots` cron, since the file itself
  isn't linked to a valid review anymore — this is a known cleanup responsibility of the storage
  saver cron job).

**B. Legacy fallback path** (no `is_dynamic` flag) — simpler screenshot/text_proof submission.
- Must provide at least one of: `screenshot`, `text_proof`, or `secret_codes[]`.
- Screenshot: same hash/duplicate-check via `StorageSaverService`.
- If task has `secret_code`, compare submitted code(s) the same way as (A). Mismatch → health -10,
  row deleted. Match → auto-approve.
- Otherwise → stays `pending` for manual admin review.

### 2.5 Manual Admin Review (`AdminTaskReviewController`)
For any `UserTask` left in `status = pending`:
- **Approve** (`approve()`):
  1. Guard: must currently be `pending` (idempotent-safe against double-approval).
  2. `DB::transaction`: set `status = approved`, `user->addMainBalance(task->reward_coins)`,
     `GamificationService::awardXp()`, `ReferralService::recordReferredUserEarning()`.
  3. **After** the transaction commits: `StorageSaverService::deleteUserTaskScreenshots()` — proof
     images are deleted from disk immediately on approval (storage-saving requirement from Global
     Rules), but the `ScreenshotHash.image_hash` row is KEPT (with `file_path` nulled) forever, to
     continue blocking future duplicate uploads of the same image site-wide.
- **Reject** (`reject()`):
  1. Guard: must currently be `pending`.
  2. Requires `admin_note` (string, required) — always recorded for auditability.
  3. `status = rejected`, **no balance is credited**.
  4. `user->deductHealth(10)` — rejection penalty.
  5. Proof images deleted from disk the same way as approval.

---

## 3. Health System

`health` is an integer user stat ranging `0`–`100` (`User::MAX_HEALTH = 100`), acting as a
quality/trust meter. Unlike earlier versions of this system, health now both depletes AND
regenerates through well-defined paths — all mutations MUST go through `User::deductHealth()` or
`User::addHealth()` (never mutate the `health` column directly), since both methods also manage the
`health_depleted_at` gate timestamp described below.

### 3.1 Depletion (`User::deductHealth(int $amount)`)
Clamped at a minimum of `0`. Deducted by **10 points** in exactly two situations:
1. Admin manually rejects a pending task submission (`AdminTaskReviewController::reject`).
2. A user submits an incorrect secret code via the social-proof/dynamic-proof endpoints (i.e. the
   "auto-verify" path where a code mismatch is detected) — see §2.4.

The first time health reaches `0`, `health_depleted_at` is stamped with the current timestamp (only
once — repeated deductions while already at `0` do not refresh this timestamp).

### 3.2 Regeneration (`User::addHealth(int $amount)`)
Clamped at a maximum of `100`. Triggered by:
1. **+1 per approved task** — wired into every reward-crediting path alongside XP/referral credit:
   `TaskController::completeShortlink`, `submitSecretCode`, the auto-approve branches of
   `submitSocialProof`/`handleDynamicProof`, and `AdminTaskReviewController::approveUserTask()`
   (shared by both single `approve()` and `bulkApprove()`).
2. **+20 per day, passively, for every user** — `health:regenerate-daily` Artisan command
   (`app/Console/Commands/RegenerateUserHealth.php`), scheduled daily at 00:05 in
   `routes/console.php`. Runs regardless of activity, so even fully inactive users slowly recover.
3. **Manual admin override** — `AdminDashboardController::setHealth()` lets an admin set health to
   any value `0`–`100` directly (dispute resolution / support flow), mirroring the existing
   `setRiskScore()` pattern.

Any of the above that brings health back above `0` also clears `health_depleted_at` (reopening the
gate immediately, not just after 24h — see below).

### 3.3 Submission Gate (`User::isHealthGateActive(): bool`)
While health is at `0`, the user is temporarily blocked from submitting **new proof-based task
submissions** (`TaskController::submitSocialProof`, which also covers the `handleDynamicProof`
delegate path) — enforced at the top of `submitSocialProof()` before any other checks. This does
**not** block `shortlink` or direct `secret_code` submissions, since those are exactly the paths that
can restore health via the +1-per-approval regen (§3.2/1), so a gated user always has a way back in
without waiting.

The gate is active only within **24 hours** of `health_depleted_at`
(`now() < health_depleted_at->addHours(24)`), then automatically expires even if health is still `0`
for some reason (e.g. the daily cron didn't run) — mirrors the time-boxed pattern used by the
withdrawal cooldown (§8.1), rather than being an indefinite lock.

### 3.4 Admin Visibility
`AdminDashboardController::index()` surfaces two moderation lists to the dashboard, both rendered
through the shared `UserModerationRow.vue` component (ban toggle + inline risk-score/health editors,
posting to `admin.users.ban` / `admin.users.risk-score` / `admin.users.health`):
- **High Risk Users** (`risk_score > 60`)
- **Low Health Users** (`health <= 30`) — a separate list, since low health does not necessarily
  correlate with a high risk score and would otherwise go unnoticed by admins.

---

## 4. XP & Leveling System (`GamificationService`)

- `GamificationService::awardXp(User $user, int $xpAmount)` is the **only** sanctioned way to grant
  XP. It must always be called instead of manually incrementing `xp_points`.
- Level thresholds (fixed, do not change without updating both `User::addXp()` and
  `GamificationService::awardXp()` — **note: there are currently two parallel level-up
  implementations, see ⚠️ below**):

  | Level | XP Range |
  |---|---|
  | 1 | 0 – 99 |
  | 2 | 100 – 499 |
  | 3 | 500+ |

- Levels only ever increase (`if ($newLevel > $user->level)`), never decrease.
- **⚠️ Known inconsistency to be aware of:** `User::addXp()` (used directly by `WheelSpinController`
  and `PromoCodeController`) implements level-up logic independently and slightly differently
  (`level === 1 && xp >= 100 → level 2`, `level === 2 && xp >= 500 → level 3`, single-step only) from
  `GamificationService::awardXp()` (used by `TaskController` / `AdminTaskReviewController`, supports
  jumping directly from level 1 to level 3 if XP is high enough, `$newLevel > $user->level`). Both
  arrive at the same thresholds in practice, but only `GamificationService::awardXp()` also updates
  the **Daily Streak** (§5). **When granting XP for a new feature, prefer
  `GamificationService::awardXp()`** so streak tracking stays consistent, unless the feature
  intentionally should not count toward the daily streak (e.g. wheel spin / promo code currently do
  NOT advance the daily streak).
- User Level also gates **minimum withdrawal amount** (§8) and is displayed as a task-list filter.

---

## 5. Daily Streak System (`GamificationService::updateDailyStreak`)

Tracked per-user in the `daily_streaks` table (`streak_count`, `tasks_completed_today`,
`last_completed_date`). Triggered every time `GamificationService::awardXp()` runs (i.e. on shortlink,
secret code, social proof approval, and admin task approval — **not** on wheel spin / promo redeem).

Logic per call:
1. `last_completed_date == today` → increment `tasks_completed_today`.
2. `last_completed_date == yesterday` → reset `tasks_completed_today = 1`, advance
   `last_completed_date = today` (streak is preserved, continuing consecutively).
3. Otherwise (gap of 2+ days, or first time ever) → `tasks_completed_today = 1`,
   `last_completed_date = today`; if there was a previous date that wasn't today, **reset
   `streak_count = 0`** (streak broken).
4. Whenever `tasks_completed_today >= 3` **on the day it's evaluated**, increment `streak_count` by 1.
   (Note: because this check runs on every task completion, once the 3rd task of the day is done,
   `streak_count` increments once; further tasks that same day do NOT increment it again unless
   `tasks_completed_today` is reset and re-crosses 3, which cannot happen same-day — so effectively
   max +1 streak per calendar day.)
5. **Reward for streak:** reaching a 7-day streak grants the user a wheel spin — see §6
   (`User::canSpin()` / `spin_available_at`). The exact mechanism that sets `spin_available_at` after
   7 days is external to this service; ensure any future streak-reward automation reads
   `streak_count % 7 == 0` (or similar) to avoid double-granting spins.

---

## 6. Spin the Wheel (`WheelSpinController`)

- Gate: `User::canSpin()` → `spin_available_at` must be set AND `now() >= spin_available_at`.
- Prize table is a **fixed, weighted random** list (weights sum arbitrarily, `mt_rand` over cumulative
  weight):

  | Prize | Value | Weight (relative odds) |
  |---|---|---|
  | Try Again | 0 | 35 |
  | 10 Points | 10 | 25 |
  | 25 Points | 25 | 18 |
  | 50 Points | 50 | 12 |
  | 100 Points | 100 | 7 |
  | 200 Points | 200 | 2 |
  | 500 Points | 500 | 1 |

- On spin (always inside `DB::transaction`):
  1. Record a `WheelSpin` row (label/value/type) for audit/history, regardless of prize.
  2. If prize type is `points` and value > 0: `main_balance += value`, and grant
     `addXp(floor(value / 10))` (uses the `User::addXp()` path, **not**
     `GamificationService::awardXp()` — does not affect daily streak).
  3. **Consume the spin**: `spin_available_at = null`, `total_spins_used += 1`. A new spin is not
     available again until external streak/reward logic re-sets `spin_available_at`.

---

## 7. Screenshot / Proof Anti-Duplication (`StorageSaverService`)

Global (site-wide, cross-user) duplicate image detection is mandatory for every proof screenshot:

1. `processAndVerifyScreenshot()`:
   - Compute `sha256` hash of the uploaded file's actual bytes (`hash_file`, not filename-based).
   - Look up `ScreenshotHash.image_hash` across **all users, all time**. If a match exists, **reject
     the upload outright** with "Duplicate screenshot detected" — this stops the same
     screenshot being reused across different task submissions or by different accounts.
   - Otherwise: store file under `storage/app/public/proofs/{timestamp}_{userId}_{uniqid}.{ext}`, and
     persist a `ScreenshotHash` row (`user_id`, `user_task_id`, `image_hash`, `file_path`).
2. `deleteUserTaskScreenshots(UserTask $userTask)` — called on every admin approve/reject decision:
   - Deletes the physical file from the `public` disk.
   - **Keeps** the `ScreenshotHash` row but nulls `file_path` — this is intentional: the hash must
     survive forever to keep blocking future duplicate submissions of that exact image, even though
     disk space is reclaimed.
3. `cleanupReviewedScreenshots()` — a cron-style sweep, run daily via the `proofs:cleanup-screenshots`
   Artisan command (`app/Console/Commands/CleanupReviewedScreenshots.php`, scheduled at midnight in
   `routes/console.php`), that finds any `ScreenshotHash` still holding a `file_path` whose related
   `UserTask.status` is `approved` or `rejected`, and deletes the leftover physical file. This is a
   safety net for any file that wasn't cleaned up synchronously.

**Rule for any future proof-upload feature:** always route uploads through
`StorageSaverService::processAndVerifyScreenshot()` — never save proof images directly in a
controller.

---

## 8. Withdrawal System (`WithdrawalController`, `AdminWithdrawalController`)

### 8.1 Eligibility Rules
- **Health Gate**: A user whose Health Score is at or below `min_withdrawal_health` (default: 40%) is blocked from submitting withdrawal requests. Checked on `index()` (`isHealthTooLow`) and re-enforced in `requestWithdrawal()`.
- **24-hour cooldown**, enforced at the controller level (per Global Rules §6), keyed off
  `user->last_withdrawal_at`: a new withdrawal is blocked until `last_withdrawal_at + 24h`. This is
  checked both when displaying the withdraw page (`index()`, returns `canWithdraw` +
  `remainingSeconds` for the frontend countdown) and again on `requestWithdrawal()` (never trust the
  UI-computed value).
- **Level-based minimum withdrawal** (in coins, i.e. `main_balance` points):

  | User Level | Minimum Withdrawal |
  |---|---|
  | 1 | 1000 coins |
  | 2 | 500 coins |
  | 3+ | 200 coins |

- Additional hard floor: `amount_coins` must be `>= 100` (Laravel validation rule), which only
  matters below Level 3's 200-coin minimum in edge cases — the level-based minimum is always the
  binding constraint in practice.
- `main_balance` must be `>= amount_coins` requested.

### 8.2 Conversion Rate
- BDT payout amount = `amount_coins / conversion_rate`, rounded to 2 decimals.
- `conversion_rate` is a dynamic admin-configurable setting stored in `app_settings`
  (`AppSetting::getByKey('conversion_rate', 100)` — default 100 coins = 1 BDT if unset).

### 8.3 Withdrawal Request Flow
Inside `DB::transaction()`:
1. `user->decrement('main_balance', $coins)` — **coins are deducted immediately upon request**, not
   upon admin approval (avoids double-spending the same balance while a withdrawal is pending).
2. `user->last_withdrawal_at = now()` — cooldown timer starts immediately on request, not on
   approval.
3. Create `Withdrawal` row with `status = pending`, `amount_coins`, computed `amount_bdt`,
   `payment_method` (must be one of `bKash`, `Nagad`, `Rocket`), `account_details`.

### 8.4 Admin Withdrawal Resolution
- **Approve** → `status = paid`. No balance change (coins were already deducted at request time).
- **Reject** → only processes if currently `pending`: refunds `amount_coins` back to
  `user->main_balance`, sets `status = rejected` with required `admin_note`. **This refund logic is
  critical** — a rejected withdrawal must always return the coins, since they were pre-deducted.
- **Bulk approve** → mass-updates only rows still `status = pending` to `paid` (no refund logic
  needed here since these are approvals, not rejections).
- **CSV export** → exports only currently `pending` withdrawals for offline payment processing.

### 8.5 Payment Wallet Details
- Changing `payment_method` / `payment_number` requires the user's 4-digit `recovery_pin` for
  confirmation **if one is already set** (protects against session-hijacking-style theft of payout
  destination).

---

## 9. Referral System (`ReferralService`)

Two-sided mechanic: a **locked bonus** for the referrer that unlocks only once the referred user
proves they're a genuine, earning user (anti-fraud measure against fake referral farming).

### 9.1 On Registration (`setupNewReferral`)
Triggered in `AuthController::register()` only if a valid `ref_code` (existing user's
`referral_code`) was supplied:
1. `newUser->ref_by = referrer->id`.
2. `referrer->locked_balance += 500.00` (fixed `bonusAmount`) — **added immediately but not
   spendable**.
3. Create `ReferralTracking` row: `locked_reward = 500`, `target_amount = 1000`, `earned_so_far = 0`,
   `status = locked`.

### 9.2 On Referred User Earning (`recordReferredUserEarning`)
Called **every time** the referred user earns coins from ANY reward source that calls it (task
approval — all types, offerwall pending-balance release). Must be called consistently by every
future earning feature that should count toward referral unlocking (currently: task completion flows
in `TaskController` + `AdminTaskReviewController::approve`, and `OfferwallPostbackController::releasePendingBalances`
— **note: campaign click rewards and promo code redemptions currently do NOT call this**, so they
don't count toward unlocking a referrer's bonus; wheel spin winnings also don't count):
1. No-op if `referredUser->ref_by` is null, or no `ReferralTracking` row exists with
   `status = locked` for this referrer/referred pair.
2. `tracking.earned_so_far += earnedAmount`.
3. If `earned_so_far >= target_amount` (1000 by default): mark `status = unlocked`, then move the
   referrer's `locked_reward` (500) from `locked_balance` → `main_balance` (only if
   `referrer->locked_balance >= locked_reward`, defensive check against inconsistent state).
4. This unlock check-and-transfer happens **exactly once** — once `status` flips to `unlocked`, the
   tracking row is excluded from future lookups (`where status = locked`), so a referrer only ever
   gets the 500-coin bonus once per referred user, regardless of how much more that user later earns.

---

## 10. Promo Codes (`PromoCodeController`)

- Code lookup is **case-insensitive by normalization**: input is `strtoupper(trim())`'d before
  comparison against the stored `code` (codes are always stored uppercase).
- `PromoCode::isAvailable()` — must be `is_active = true`, AND (`expires_at` is null OR in the
  future), AND `used_count < max_uses`.
- **One redemption per user per code, ever** — enforced via a unique lookup in `PromoCodeUse`
  (`user_id` + `promo_code_id`), not a DB unique constraint, so any new redemption code path must
  replicate this existence check.
- On successful redemption (`DB::transaction`): create `PromoCodeUse` row, `promo->used_count += 1`,
  `user->main_balance += reward_points`, `user->addXp(5)` (fixed 5 XP, does not affect daily streak
  since it uses `User::addXp()` not `GamificationService`).

---

## 11. Micro-Campaigns (User-to-User Ad Exchange) (`CampaignController`)

Users can pay points to promote a URL to other users, who click it to earn points.

### 11.1 Creating a Campaign
- Fixed `cost_per_click = 1.0` point (not currently admin-configurable despite being passed as a
  "setting" to the frontend).
- `total_budget = target_clicks * cost_per_click`; `target_clicks` must be between 50 and 10,000.
- Requires `main_balance >= total_budget`; the full budget is **deducted upfront** on creation
  (`DB::transaction`), before admin approval.
- New campaigns are created with `status = pending` — **do not go live automatically**; an admin
  must approve them (`AdminDashboardController::approveCampaign` → `status = active`).

### 11.2 Admin Rejection Refund
- `AdminDashboardController::rejectCampaign`: refunds only the **unspent remainder** —
  `remaining = budget_points - (total_clicks * cost_per_click)` — back to the campaign owner's
  `main_balance`. This matters because a campaign could theoretically accumulate some clicks before
  being rejected (edge case, since normally only `active` campaigns are clickable) — the logic
  correctly avoids refunding clicks that were already paid out to clickers.

### 11.3 Clicking a Campaign (Earning Side)
Guards (in order), each returning a 403 JSON error:
1. Campaign must be `status = active`.
2. A user cannot click their own campaign (`campaign->user_id !== user->id`).
3. Campaign must not have already reached `target_clicks`.
4. A user may click a given campaign **at most once ever**
   (`CampaignClick` existence check on `campaign_id` + `user_id`).

On success (`DB::transaction`):
1. Create `CampaignClick` row (records `ip_address` too, for potential fraud analysis).
2. `campaign->total_clicks += 1`.
3. Clicker is rewarded `campaign->cost_per_click` points + `addXp(1)` (does not affect daily streak,
   does not notify `ReferralService`).
4. **Auto-completion**: if `total_clicks >= target_clicks` after this click, `status` flips to
   `completed` automatically (no admin action needed to end a fully-clicked campaign).

---

## 12. Offerwall Postback (S2S Webhook) (`OfferwallPostbackController`)

Public, unauthenticated route: `POST/GET /postback/{provider}`. **Must always respond `200` with body
`'1'`** regardless of outcome (standard offerwall S2S convention so the provider doesn't retry
indefinitely) — this is why every branch in this controller returns `response('1', 200)` even on
validation failure or "already processed" cases.

### 12.1 Accepted Parameters (flexible naming across providers)
- Sub ID (our internal user id): `subId` | `user_id` | `uid`
- Transaction ID: `transId` | `tx_id` | `transaction_id`
- Reward amount: `reward` | `amount` | `payout` (defaults to `0` if all missing)
- Status: `status` (defaults to `'1'`, meaning "completed/approved")

### 12.2 Reversal / Chargeback Handling
Triggered when `status` is `reversed`, `2`, or `chargeback`:
- Only processes if a matching `OfferwallLog` exists by `transaction_id` and it isn't already
  `reversed` (idempotency guard against duplicate reversal postbacks).
- Inside `DB::transaction`: mark the log `reversed`, then claw back the reward — preferentially from
  `pending_balance` if it still holds enough (i.e. reward hadn't been released to main balance yet),
  otherwise claw back from `main_balance` directly (i.e. the reward had already been released).
  **This means a chargeback can pull a user's `main_balance` negative** if they've already spent it
  — there is no guard against this today; that risk is accepted per Global Rules §4 ("Prevent
  negative balances unless explicitly allowed (like chargeback penalties)" — this IS that allowed
  exception).

### 12.3 New Conversion (Non-Reversal)
- Deduplicated by `transaction_id` — if a log with that ID already exists (of any status), the
  postback is a no-op (prevents double-crediting from provider retries).
- Otherwise (`DB::transaction`): create an `OfferwallLog` with `status = pending` and
  `release_time = now() + 24 hours`, and credit the reward to `user->pending_balance` (NOT
  `main_balance` yet — enforces the mandatory hold period before funds are spendable/withdrawable, to
  give time for provider-side reversals to land).

### 12.4 Releasing Held Balances (`releasePendingBalances`)
Run periodically via the `offerwall:release-pending` Artisan command
(`app/Console/Commands/ReleaseOfferwallPendingBalances.php`), scheduled hourly in
`routes/console.php`. For every `OfferwallLog` where `status = pending` AND `release_time <= now()`:
- `DB::transaction`: move the amount from `pending_balance` → `main_balance`, call
  `ReferralService::recordReferredUserEarning()` (offerwall earnings DO count toward unlocking
  referral bonuses), then mark the log `approved`.

---

## 13. Authentication & Anti-Fraud (Account Level)

### 13.1 Registration (`AuthController::register`)
- Required fields: `name`, `email` (unique), `password` (min 6, confirmed), `recovery_pin` (exactly
  4 digits), `device_hash` (required string — produced client-side, e.g. via FingerprintJS per Global
  Rules §6), optional `ref_code`.
- **1 Device = 1 Account enforcement**: if any existing user already has this exact `device_hash`,
  registration is rejected outright with "Multiple accounts are strictly forbidden" — this is a hard
  block, not just a risk-score flag.
- New users always start with `main_balance = pending_balance = locked_balance = 0`,
  `role = 'user'`, a freshly generated unique `referral_code` (`User::generateReferralCode()` —
  8-char uppercase random string, collision-checked against the DB).
- If `ref_code` resolves to a real user, `ReferralService::setupNewReferral()` runs (see §9.1).

### 13.2 Login (`AuthController::login`)
- Requires `device_hash` on every login attempt too (not just registration).
- If the authenticating user has no `device_hash` stored yet (e.g. account created before this
  field existed, or via Google OAuth which doesn't collect it), and the submitted `device_hash`
  isn't already claimed by a *different* user, it gets backfilled onto this account. If it's already
  in use by someone else, it's silently NOT attached (no error currently raised to the user — this
  is a soft-fail, worth revisiting if stricter enforcement is desired later).

### 13.3 Google OAuth (`GoogleAuthController`)
- Matches an existing user by `google_id` OR `email`. If found, links `google_id` if missing, then
  logs in. Otherwise creates a brand-new user with zero balances and `role = 'user'`.
- **Does not collect or check `device_hash`** — this is a gap in the "1 device = 1 account" rule for
  OAuth signups; be aware this path bypasses that specific anti-fraud check.

### 13.4 Account Recovery (`AuthController::recoverAccount`)
- Requires exact 4-digit `recovery_pin` match (plain string comparison against the stored value —
  note this is NOT hashed, unlike the password) plus a new confirmed password.

### 13.5 Risk Scoring & Banning (Admin-side)
- `risk_score` (`decimal:2`, 0–100) is manually set by an admin
  (`AdminDashboardController::setRiskScore`) — there is currently no automated risk-scoring
  algorithm; it's a manual admin judgment field surfaced on the admin dashboard for any user with
  `risk_score > 60` ("High Risk Users" list).
- `is_banned` is a manual toggle (`AdminDashboardController::banUser`), and it IS enforced at
  multiple layers:
  1. **Login (`AuthController::login`)** — if the authenticating user's `is_banned` is true, the
     session is immediately logged out/invalidated and the request is rejected with a banned-account
     error, before the `device_hash` backfill logic even runs.
  2. **Google OAuth login (`GoogleAuthController::handleGoogleCallback`)** — the same check runs for
     any *existing* matched user before `Auth::login()`, redirecting to the login page with a
     banned-account error. (This does not touch the `device_hash` logic, which Google OAuth
     intentionally still skips — see §13.3.)
  3. **Every authenticated request (`EnsureUserIsNotBanned` middleware, aliased `not_banned`)** —
     applied to the entire authenticated user route group in `routes/web.php` (not the `admin`
     group). This guards against a user being banned **while their session is still active**: on
     their very next request, the middleware detects `is_banned`, force-logs them out (invalidates
     the session + regenerates the CSRF token), and redirects to `/login` with an error. Any new
     authenticated route group must include `not_banned` alongside `auth` to stay protected.
- **Flagged multi-device detection**: the admin dashboard surfaces any `device_hash` shared by more
  than one user (`GROUP BY device_hash HAVING count > 1`) — since registration is supposed to block
  this, any row appearing here indicates either a pre-existing data anomaly or a bypass (e.g. Google
  OAuth users, who never had a `device_hash` recorded).

---

## 14. Dynamic System Settings (`AppSetting`)

Simple `key`/`value` string store, accessed via `AppSetting::getByKey($key, $default)` and
`AppSetting::setByKey($key, $value)`. Currently used keys:

| Key | Purpose | Default |
|---|---|---|
| `conversion_rate` | Coins-to-BDT conversion divisor for withdrawals (§8.2) | `100` |
| `happy_hour` | Boolean flag (stored as string `'true'`/`'false'`) — when active, doubles coin rewards via `AppSetting::rewardMultiplier()`. | `false` |
| `min_withdrawal_health` | Minimum Health Score % required to submit a withdrawal request (§8.1) | `40` |

Any new global, admin-tunable numeric/string setting should be added here rather than hardcoded in a
controller, following this same `AppSetting` key/value pattern.

### 14.1 Happy Hour Reward Multiplier (`AppSetting::rewardMultiplier()`)

- Returns `2.0` when `happy_hour` setting is `'true'`, otherwise `1.0`.
- Applied by multiplying `task->reward_coins` (or the offerwall postback `reward` amount) **before**
  crediting the user's balance and **before** passing the amount to
  `ReferralService::recordReferredUserEarning()` (so referral-unlock progress also benefits from
  Happy Hour, consistent with the actual amount credited).
- Wired into every coin-crediting task path:
  - `TaskController::completeShortlink`
  - `TaskController::submitSecretCode`
  - `TaskController::submitSocialProof` (auto-approved branch)
  - `TaskController::handleDynamicProof` (auto-approved branch)
  - `AdminTaskReviewController::approve`
  - `OfferwallPostbackController::handlePostback` (new-conversion credit to `pending_balance` — the
    multiplied amount is what's stored on the `OfferwallLog.amount` and later released/clawed back,
    so reversal math stays consistent)
- **Intentionally NOT applied to**: wheel spin prizes, promo code redemption, campaign click rewards,
  and the one-time welcome bonus — these are fixed-value rewards unrelated to task/offerwall earning
  and are out of scope for the Happy Hour promotion. Extend this list deliberately if product wants
  Happy Hour to also affect these.
- XP (`reward_xp`) is never multiplied — only coin rewards are affected by Happy Hour.

---

## 15. Access Control

- `AdminMiddleware` — gates all `/admin/*` routes. Requires `Auth::check()` AND
  `Auth::user()->isAdmin()` (`role === 'admin'`), otherwise `abort(403)`. There is no more granular
  admin permission system (e.g. sub-roles) — it's binary user/admin.
- All authenticated (non-admin) routes only require `auth` middleware — no additional
  `is_banned` check is layered in today (see §13.5 caveat).

---

## 16. Summary Table — Every Path That Touches `main_balance`

| Feature | Credits / Debits | Awards XP? | Counts Toward Referral Unlock? | Affects Daily Streak? |
|---|---|---|---|---|
| Shortlink task | + reward_coins | Yes (`Gamification`) | Yes | Yes |
| Secret code task (direct) | + reward_coins | Yes (`Gamification`) | Yes | Yes |
| Social/dynamic proof (auto-approved) | + reward_coins | Yes (`Gamification`) | Yes | Yes |
| Admin task approval | + reward_coins | Yes (`Gamification`) | Yes | Yes |
| Offerwall pending release (cron) | + amount (from pending_balance) | No | Yes | No |
| Welcome bonus (one-time) | + 50 | Yes (`User::addXp`, +10 XP) | No (not routed through ReferralService) | No |
| Wheel spin win | + prize value | Yes (`User::addXp`, value/10) | No | No |
| Promo code redemption | + reward_points | Yes (`User::addXp`, +5) | No | No |
| Campaign click (earning side) | + cost_per_click | Yes (`User::addXp`, +1) | No | No |
| Withdrawal request | − amount_coins (immediately) | No | N/A | No |
| Withdrawal admin reject | + amount_coins (refund) | No | N/A | No |
| Campaign creation | − target_clicks × cost_per_click (upfront) | No | N/A | No |
| Campaign admin reject | + unspent remainder (refund) | No | N/A | No |
| Offerwall chargeback/reversal | − reward (from pending or main) | No | N/A | No |

> When building a new reward feature, explicitly decide (and document here) whether it should: award
> XP, call `GamificationService` vs `User::addXp()`, count toward referral unlocking via
> `ReferralService::recordReferredUserEarning()`, and go through `pending_balance` (with a hold
> period) vs directly to `main_balance`. Do not assume — every one of the existing features made this
> a deliberate choice, and consistency here directly affects fraud exposure and referral economics.
