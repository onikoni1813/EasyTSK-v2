# Database Schema & Relationships (Easytsk V2)

> **This is the authoritative schema reference required by `Global Project Rules.md` §3:**
> *"NEVER assume database table names or columns. ALWAYS strictly follow the `database_schema.md`
> file."*
>
> This document is generated directly from the actual migrations in `database/migrations/` and the
> corresponding Eloquent models in `app/Models/` (verified against the project as of 2026-07-24).
> **If you add, rename, or drop a column/table, update this file in the same change** — it must
> never drift from the real schema.

**Database:** MySQL (production) / SQLite (local dev, `database/database.sqlite`)
**Framework:** Laravel 12.x

---

## 1. `users`
Stores all user data, balances, gamification stats, and anti-fraud/security fields.

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK, auto-increment | |
| `name` | string | |
| `email` | string, nullable, unique | |
| `email_verified_at` | timestamp, nullable | |
| `password` | string, nullable | nullable to support Google-OAuth-only accounts; hashed via `password` cast |
| `google_id` | string, nullable, unique | Google OAuth identifier |
| `device_hash` | string, nullable, unique | FingerprintJS hash — 1 device = 1 account anti-fraud check |
| `recovery_pin` | string, nullable | 4-digit PIN, plain string (not hashed), for account recovery |
| `main_balance` | decimal(10,2), default 0 | Withdrawable/spendable balance |
| `pending_balance` | decimal(10,2), default 0 | Offerwall rewards held during the 24h verification window |
| `locked_balance` | decimal(10,2), default 0 | Locked referral bonus, held on the referrer's account |
| `level` | integer, default 1 | 1–3, gamification level |
| `xp_points` | integer, default 0 | |
| `ref_by` | BigInt, nullable, FK → `users.id` (`nullOnDelete`) | Who referred this user |
| `role` | enum(`user`,`admin`), default `user` | |
| `payment_method` | string, nullable | e.g. `bKash`, `Nagad`, `Rocket` |
| `payment_number` | string, nullable | Saved payout wallet number |
| `has_claimed_welcome_bonus` | boolean, default false | |
| `last_withdrawal_at` | timestamp, nullable | Drives the 24h withdrawal cooldown |
| `referral_code` | string(10), nullable, unique | Auto-generated 8-char uppercase code |
| `risk_score` | decimal(5,2), default 0 | 0–100, manually set by admin |
| `is_banned` | boolean, default false | Enforced by `EnsureUserIsNotBanned` middleware + at login |
| `health` | integer, default 100 | Anti-fraud/quality stat (0–100), decremented on rejected tasks / wrong codes; regenerates via daily cron, per-task-approval bonus, or admin override — see `core_logics.md` §3 |
| `health_depleted_at` | timestamp, nullable | Set the moment `health` first hits 0; drives the 24h proof-submission gate (`core_logics.md` §3.3); cleared once health recovers above 0 |
| `spin_available_at` | timestamp, nullable | When set and in the past, user can spin the wheel |
| `total_spins_used` | unsigned smallint, default 0 | |
| `remember_token` | string, nullable | Laravel auth |
| `created_at`, `updated_at` | timestamps | |

**Relationships (`User` model):**
`hasMany` UserTask, OfferwallLog, Withdrawal, ReferralTracking (as `referrer_id`), WheelSpin,
Campaign, PromoCodeUse · `hasOne` DailyStreak

---

## 2. `tasks`
All available earning tasks (Shortlinks, Secret Codes, Social Proof, User Ads).

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `title` | string | |
| `description` | text, nullable | |
| `proof_requirements` | json, nullable | Dynamic array of `{id, type: text\|image, label, is_required}` for admin-configurable proof forms |
| `type` | enum(`shortlink`,`secret_code`,`social`,`user_ad`) | |
| `provider_name` | string, nullable | |
| `target_url` | string, nullable | |
| `secret_code` | string, nullable | Comma-separated if multiple codes required |
| `reward_coins` | decimal(8,2) | Base reward before Happy Hour multiplier |
| `reward_xp` | integer, default 10 | |
| `daily_ip_limit` | integer, default 1 | `0` = one-time-only task; `>0` = max completions per calendar day per user/IP |
| `status` | enum(`active`,`inactive`), default `active` | |
| `created_at`, `updated_at` | timestamps | |

**Relationships (`Task` model):** `hasMany` UserTask

---

## 3. `user_tasks` (Task Ledger & Submissions)
Tracks every task completion/submission attempt, including proofs and review outcome.

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `user_id` | BigInt, FK → `users.id` (cascade) | |
| `task_id` | BigInt, FK → `tasks.id` (cascade) | |
| `status` | enum(`pending`,`approved`,`rejected`), default `pending` | |
| `submitted_data` | json, nullable | Secret codes, text proofs, and/or `screenshot_hash` references |
| `ip_address` | string, nullable | Used for the rotation/anti-farm limit |
| `admin_note` | text, nullable | Required reason on rejection |
| `created_at`, `updated_at` | timestamps | |

**Relationships (`UserTask` model):** `belongsTo` User, Task · `hasMany` ScreenshotHash

---

## 4. `offerwall_logs` (Postback Tracking)
Stores S2S postbacks from providers (Notik, Timewall, etc.) to prevent duplicate processing.

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `user_id` | BigInt, FK → `users.id` (cascade) | |
| `provider` | string | e.g. `Notik` |
| `transaction_id` | string, unique | Provider's transaction ID — dedup key |
| `amount` | decimal(8,2) | Already includes the Happy Hour multiplier, if it was active at credit time |
| `status` | enum(`pending`,`approved`,`reversed`), default `pending` | |
| `release_time` | timestamp, nullable | 24h from creation — when `pending_balance` moves to `main_balance` |
| `created_at`, `updated_at` | timestamps | |

**Relationships (`OfferwallLog` model):** `belongsTo` User

---

## 5. `referral_trackings`
Tracks referral bonus progress and locked-balance unlocking per referred user.

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `referrer_id` | BigInt, FK → `users.id` (cascade) | |
| `referred_user_id` | BigInt, FK → `users.id` (cascade) | |
| `locked_reward` | decimal(8,2), default 500 | Amount added to referrer's `locked_balance` at signup |
| `target_amount` | decimal(8,2), default 1000 | Referred user's cumulative earnings needed to unlock |
| `earned_so_far` | decimal(8,2), default 0 | |
| `status` | enum(`locked`,`unlocked`,`claimed`), default `locked` | Code only ever transitions `locked → unlocked`; `claimed` is defined but not currently set by any code path |
| `created_at`, `updated_at` | timestamps | |

**Relationships (`ReferralTracking` model):** `belongsTo` User (as `referrer`), User (as
`referredUser`)

---

## 6. `withdrawals`
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `user_id` | BigInt, FK → `users.id` (cascade) | |
| `amount_coins` | decimal(10,2) | Deducted from `main_balance` immediately on request |
| `amount_bdt` | decimal(10,2) | `amount_coins / conversion_rate`, rounded to 2 decimals |
| `payment_method` | string | `bKash`, `Nagad`, or `Rocket` |
| `account_details` | string | |
| `status` | enum(`pending`,`paid`,`rejected`), default `pending` | |
| `admin_note` | text, nullable | Required reason on rejection |
| `created_at`, `updated_at` | timestamps | |

**Relationships (`Withdrawal` model):** `belongsTo` User

---

## 7. `screenshot_hashes`
Global, site-wide duplicate-image detection registry for proof uploads.

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `user_id` | BigInt, FK → `users.id` (cascade) | |
| `user_task_id` | BigInt, nullable, FK → `user_tasks.id` (cascade) | |
| `image_hash` | string, unique | SHA-256 hash of the uploaded file's bytes |
| `file_path` | string, nullable | Physical path on the `public` disk; nulled (not row-deleted) once reviewed/cleaned up, so the hash keeps blocking future duplicates forever |
| `created_at`, `updated_at` | timestamps | |

**Relationships (`ScreenshotHash` model):** `belongsTo` User, UserTask

---

## 8. `app_settings`
Simple global key/value store for admin-tunable runtime settings.

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `key` | string, unique | e.g. `conversion_rate`, `happy_hour` |
| `value` | text, nullable | Always stored as a string (e.g. `'100'`, `'true'`/`'false'`) |
| `created_at`, `updated_at` | timestamps | |

---

## 9. `daily_streaks`
One row per user, tracking consecutive-day task-completion streaks.

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `user_id` | BigInt, FK → `users.id` (cascade) | |
| `streak_count` | integer, default 0 | Increments by 1 per calendar day once 3+ tasks are completed that day |
| `tasks_completed_today` | integer, default 0 | Resets daily |
| `last_completed_date` | date, nullable | |
| `created_at`, `updated_at` | timestamps | |

**Relationships (`DailyStreak` model):** `belongsTo` User

---

## 10. `wheel_spins`
Audit log of every "Spin the Wheel" attempt and its prize outcome.

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `user_id` | BigInt, FK → `users.id` (cascade), indexed | |
| `prize_label` | string | e.g. `"50 Points"`, `"Try Again"` |
| `prize_value` | decimal(10,2), default 0 | `0` for "Try Again" |
| `prize_type` | string, default `points` | Currently only `points` or `none` are produced |
| `created_at`, `updated_at` | timestamps | |

**Relationships (`WheelSpin` model):** `belongsTo` User

---

## 11. `promo_codes`
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `code` | string(20), unique | Always stored/compared uppercase |
| `description` | string, nullable | |
| `reward_points` | decimal(10,2), default 0 | |
| `max_uses` | unsigned int, default 1 | Total redemptions allowed across all users |
| `used_count` | unsigned int, default 0 | |
| `expires_at` | timestamp, nullable | |
| `is_active` | boolean, default true | |
| `created_at`, `updated_at` | timestamps | |

**Relationships (`PromoCode` model):** `hasMany` PromoCodeUse

## 11a. `promo_code_uses`
Join table enforcing **one redemption per user per code, ever**.

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `user_id` | BigInt, FK → `users.id` (cascade) | |
| `promo_code_id` | BigInt, FK → `promo_codes.id` (cascade) | |
| `created_at`, `updated_at` | timestamps | |
| — | unique(`user_id`, `promo_code_id`) | DB-level enforcement of one-time redemption |

**Relationships (`PromoCodeUse` model):** `belongsTo` User, PromoCode

---

## 12. `campaigns`
User-funded micro-campaigns (pay-per-click ad exchange between users).

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `user_id` | BigInt, FK → `users.id` (cascade) | Campaign owner/advertiser |
| `title` | string | |
| `description` | text, nullable | |
| `target_url` | string | |
| `type` | string, default `website` | `website`, `telegram`, `youtube`, or `other` |
| `budget_points` | decimal(10,2) | Total budget, deducted upfront from owner's `main_balance` |
| `cost_per_click` | decimal(6,2), default 1.00 | Points paid to each clicker |
| `total_clicks` | unsigned int, default 0 | |
| `target_clicks` | unsigned int | Must be 50–10,000 at creation |
| `status` | string, default `pending` | `pending`, `active`, `paused`, `completed`, `rejected` |
| `admin_note` | string, nullable | |
| `created_at`, `updated_at` | timestamps | |
| — | index(`status`, `created_at`) | |

**Relationships (`Campaign` model):** `belongsTo` User · `hasMany` CampaignClick

## 12a. `campaign_clicks`
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `campaign_id` | BigInt, FK → `campaigns.id` (cascade) | |
| `user_id` | BigInt, FK → `users.id` (cascade) | The clicker (never the campaign owner) |
| `ip_address` | string(45), nullable | |
| `created_at`, `updated_at` | timestamps | |
| — | unique(`campaign_id`, `user_id`) | One click per user per campaign, DB-enforced |

**Relationships (`CampaignClick` model):** `belongsTo` Campaign, User

---

## 13. `offerwalls`
Admin-managed catalogue of offerwall providers displayed to users.

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `name` | string | |
| `description` | text, nullable | |
| `image_url` | string, nullable | |
| `iframe_url_pattern` | string | e.g. `https://timewall.com/offerwall?id=...&uid={user_id}` |
| `secret_key` | string, nullable | Provider's postback signing/verification secret |
| `reward_ratio` | decimal(8,2), default 1.00 | Provider-currency-to-main-balance conversion ratio |
| `status` | boolean, default true | Whether it's shown to users |
| `is_api` | boolean, default false | If true, integration is API-based rather than iframe-based |
| `order` | integer, default 0 | Display sort order |
| `created_at`, `updated_at` | timestamps | |

*(No dedicated Eloquent relationships defined — `Offerwall` rows are looked up directly by the
`TaskController` for display and by `AdminOfferwallController` for CRUD.)*

---

## 15. `password_reset_tickets`
Support ticket system for users who forgot their password/PIN on login.

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `user_id` | BigInt, FK → `users.id` (nullOnDelete) | Nullable if user lookup by phone happens dynamically |
| `phone` | string | User's registered phone number |
| `ticket_code` | string, unique | e.g. `PR-8X92K4` |
| `message` | text, nullable | User's note or explanation |
| `status` | enum(`pending`,`approved`,`rejected`,`completed`), default `pending` | |
| `reset_code` | string, nullable | 6-digit OTP generated upon admin approval |
| `admin_note` | text, nullable | Note left by admin on approval/rejection |
| `ip_address` | string, nullable | |
| `device_hash` | string, nullable | |
| `approved_at` | timestamp, nullable | |
| `completed_at` | timestamp, nullable | |
| `created_at`, `updated_at` | timestamps | |

**Relationships (`PasswordResetTicket` model):** `belongsTo` User

---

## 17. `support_tickets`
General user support ticket hub.

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `user_id` | BigInt, FK → `users.id` (cascade) | |
| `ticket_number` | string, unique | e.g. `ST-89214` |
| `category` | enum(`withdrawal`,`task`,`account`,`general`), default `general` | |
| `subject` | string | Ticket topic |
| `status` | enum(`open`,`in_progress`,`resolved`,`closed`), default `open` | |
| `priority` | enum(`low`,`medium`,`high`), default `medium` | |
| `last_reply_at` | timestamp, nullable | |
| `created_at`, `updated_at` | timestamps | |

**Relationships (`SupportTicket` model):** `belongsTo` User · `hasMany` SupportTicketMessage

---

## 18. `support_ticket_messages`
Conversation thread messages for support tickets.

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `ticket_id` | BigInt, FK → `support_tickets.id` (cascade) | |
| `sender_id` | BigInt, FK → `users.id` (cascade) | |
| `is_admin` | boolean, default false | True if sent by admin |
| `message` | text | Content of message |
| `created_at`, `updated_at` | timestamps | |

**Relationships (`SupportTicketMessage` model):** `belongsTo` SupportTicket, User (as `sender`)

---

## 20. `referral_contests`
Stores Top Referrer Contest schedules, minimum unlocked requirements, prize pool structure, and distribution status.

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `title` | string | e.g. `Weekly Top Referrer Contest #1` |
| `start_date` | datetime | |
| `end_date` | datetime | |
| `min_unlocked_required` | unsigned int, default 1 | Minimum unlocked referrals needed to qualify |
| `prizes` | json | JSON array of `{rank: int, reward: float}` |
| `status` | enum(`active`,`completed`,`cancelled`), default `active` | |
| `distributed_at` | timestamp, nullable | Set when rewards are distributed |
| `created_at`, `updated_at` | timestamps | |

**Relationships (`ReferralContest` model):** `hasMany` ReferralContestWinner

---

## 21. `referral_contest_winners`
Audit log and payout ledger for contest champions.

| Column | Type | Notes |
|---|---|---|
| `id` | BigInt, PK | |
| `contest_id` | BigInt, FK → `referral_contests.id` (cascade) | |
| `user_id` | BigInt, FK → `users.id` (cascade) | Winner user |
| `rank` | unsigned int | |
| `unlocked_count` | unsigned int | Unlocked referrals at payout time |
| `reward_amount` | decimal(10,2) | Coins credited to `main_balance` |
| `created_at`, `updated_at` | timestamps | |
| — | unique(`contest_id`, `rank`) | DB-level guard against duplicate rank payouts |
| — | unique(`contest_id`, `user_id`) | DB-level guard against duplicate user payouts |

**Relationships (`ReferralContestWinner` model):** `belongsTo` ReferralContest, User

---

## 19. Laravel Framework Tables (not application-specific)
These exist from Laravel's default scaffolding and are used as-is:

| Table | Purpose |
|---|---|
| `password_reset_tokens` | Laravel's built-in password reset flow (app currently uses its own PIN-based `recoverAccount` flow instead) |
| `sessions` | Session driver storage |
| `cache`, `cache_locks` | Cache driver storage |
| `jobs`, `job_batches`, `failed_jobs` | Queue driver storage |

---

## Core Relationships Summary (Laravel Models)

- `User` `hasMany` UserTask, OfferwallLog, Withdrawal, WheelSpin, Campaign, PromoCodeUse, ReferralContestWinner
- `User` `hasMany` ReferralTracking (as `referrer_id`)
- `User` `hasOne` DailyStreak
- `Task` `hasMany` UserTask
- `UserTask` `belongsTo` User, Task; `hasMany` ScreenshotHash
- `ScreenshotHash` `belongsTo` User, UserTask
- `ReferralTracking` `belongsTo` User (as `referrer`), User (as `referredUser`)
- `ReferralContest` `hasMany` ReferralContestWinner
- `ReferralContestWinner` `belongsTo` ReferralContest, User
- `Campaign` `belongsTo` User; `hasMany` CampaignClick
- `CampaignClick` `belongsTo` Campaign, User
- `PromoCode` `hasMany` PromoCodeUse
- `PromoCodeUse` `belongsTo` User, PromoCode
- `WheelSpin`, `OfferwallLog`, `Withdrawal`, `DailyStreak` — each `belongsTo` User

For the business logic that governs how these tables and columns are mutated (reward calculations,
balance transfers, anti-fraud checks, etc.), see `core_logics.md`.
