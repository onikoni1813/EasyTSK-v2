<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\OfferwallLog;
use App\Models\User;
use App\Services\ReferralService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfferwallPostbackController extends Controller
{
    protected ReferralService $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    public function handlePostback(Request $request, string $provider)
    {
        $normalizedProvider = strtolower(str_replace([' ', '-', '_', '.'], '', $provider));
        $offerwall = \App\Models\Offerwall::where(function ($query) use ($provider, $normalizedProvider) {
            $query->where('name', $provider)
                ->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(name, ' ', ''), '-', ''), '_', ''), '.', '')) = ?", [$normalizedProvider]);
        })->first();

        if (!$offerwall) {
            return response('Provider not found', 404);
        }

        $paramUserId = $offerwall->param_user_id ?: 'user_id';
        $paramTransId = $offerwall->param_transaction_id ?: 'transaction_id';
        $paramAmount = $offerwall->param_amount ?: 'amount';
        $paramStatus = $offerwall->param_status ?: 'status';
        $paramSecret = $offerwall->param_secret_key ?: 'secure';
        $chargebackValue = strtolower($offerwall->status_chargeback_value ?: 'reversed');

        // Helper closure for provider-specific acknowledgment responses
        $respondSuccess = function () use ($normalizedProvider) {
            if ($normalizedProvider === 'earnwall' || $normalizedProvider === 'offerwallme' || $normalizedProvider === 'offerwall') {
                return response('ok', 200)->header('Content-Type', 'text/plain');
            }
            if ($normalizedProvider === 'moneyrain' || $normalizedProvider === 'capsbit') {
                return response('OK', 200)->header('Content-Type', 'text/plain');
            }
            return response('1', 200)->header('Content-Type', 'text/plain');
        };

        // Extract parameters with support for GET, POST Form, and Raw JSON (MoneyRain)
        $subId = $request->input($paramUserId) 
            ?? $request->input('external_uid') 
            ?? $request->input('subId') 
            ?? $request->input('uid') 
            ?? $request->input('userID') 
            ?? $request->input('userId') 
            ?? $request->input('external_user_id');

        $transId = $request->input($paramTransId) 
            ?? $request->input('view_id') 
            ?? $request->input('txid') 
            ?? $request->input('tx_id') 
            ?? $request->input('transId') 
            ?? $request->input('transactionID') 
            ?? $request->input('withdrawId') 
            ?? $request->input('withdraw_id') 
            ?? $request->input('conversion_id');
        
        $type = strtolower((string) $request->input('type', ''));
        $status = strtolower((string) $request->input($paramStatus, $request->input('status', $type ?: '1')));

        \Log::info("Offerwall Postback hit ({$provider}):", [
            'all_inputs' => $request->all(),
            'subId' => $subId,
            'transId' => $transId,
            'status' => $status,
            'type' => $type,
            'ip' => $request->ip()
        ]);

        // Handle TimeWall lifecycle stages: 'hold' and 'hold_cancelled' (do NOT credit/debit, return 200 OK)
        if ($type === 'hold' || $type === 'hold_cancelled') {
            return $respondSuccess();
        }

        // Handle Capsbit pending status (0 = Pending: Received, under review. Wait - do not credit)
        if ($normalizedProvider === 'capsbit' && ($status === '0' || $status === 'pending')) {
            \Log::info("Capsbit Postback: Status is pending ({$status}). Acknowledged without crediting.");
            return $respondSuccess();
        }

        // Handle MoneyRain non-completion events
        if ($normalizedProvider === 'moneyrain') {
            $event = (string) $request->input('event', '');
            if ($event !== '' && $event !== 'reward.completed' && $event !== 'hourly_reward.completed') {
                \Log::info("MoneyRain Postback: Non-reward event received ({$event}). Acknowledged.");
                return $respondSuccess();
            }
        }

        $rawRevenue = (string) ($request->input('revenue') ?? $request->input('payout') ?? $request->input('reward_usdt') ?? '');
        $rawReward = (string) ($request->input($paramAmount) 
            ?? $request->input('currencyAmount') 
            ?? $request->input('reward_currency_amount') 
            ?? $request->input('reward') 
            ?? $request->input('payout') 
            ?? $rawRevenue);
        
        $rewardNum = (float) $rawReward;
        $revenueNum = (float) $rawRevenue;

        // Check if request is a chargeback (explicit status/type, rejected, or negative amounts)
        $isChargeback = ($status === $chargebackValue 
            || $status === '2' 
            || $status === 'chargeback' 
            || $status === 'rejected' 
            || $type === 'chargeback' 
            || $rewardNum < 0 
            || $revenueNum < 0);

        $reward = abs($rewardNum != 0 ? $rewardNum : $revenueNum);

        if (!$subId || (!$transId && !$isChargeback)) {
            \Log::warning("Offerwall Postback missing parameters: subId={$subId}, transId={$transId}");
            return response('Missing parameters', 400);
        }

        if ($reward <= 0 && !$isChargeback) {
            return response('Invalid reward amount', 400);
        }

        // Validate Allowed IPs
        if (!empty($offerwall->allowed_ips)) {
            $allowedIps = array_map('trim', explode(',', $offerwall->allowed_ips));
            if (!in_array($request->ip(), $allowedIps)) {
                return response('Unauthorized IP', 403);
            }
        }
        
        // Validate Provider Secret Key (if configured)
        if (!empty($offerwall->secret_key)) {
            $providedSecret = $request->input($paramSecret) 
                ?? $request->input('sig') 
                ?? $request->input('hash') 
                ?? $request->input('signature') 
                ?? $request->input('hash_signature') 
                ?? $request->input('secret') 
                ?? $request->input('secure') 
                ?? $request->header('X-Secret-Key') 
                ?? $request->header($paramSecret);

            $isValidSecret = false;

            // 1. Check MoneyRain raw JSON HMAC-SHA256 signature in header
            $moneyRainHeader = $request->header('X-MoneyRain-Signature') 
                ?? $request->header('HTTP_X_MONEYRAIN_SIGNATURE') 
                ?? $request->server('HTTP_X_MONEYRAIN_SIGNATURE', '');

            if (!empty($moneyRainHeader)) {
                $rawBody = $request->getContent();
                $expectedHmac = hash_hmac('sha256', $rawBody, $offerwall->secret_key);
                $cleanHeader = trim((string) $moneyRainHeader);
                if (hash_equals('sha256=' . $expectedHmac, $cleanHeader) || hash_equals($expectedHmac, $cleanHeader)) {
                    $isValidSecret = true;
                }
            }

            if ($providedSecret !== null) {
                $cleanProvidedSecret = strtolower(trim((string) $providedSecret));

                // 2. Direct exact match
                if ($providedSecret === $offerwall->secret_key || $request->input('secret') === $offerwall->secret_key || $request->input('secure') === $offerwall->secret_key) {
                    $isValidSecret = true;
                }

                // 3. Capsbit Signature Formula: md5(uid . payout . offer_id . txid . secret_key)
                if (!$isValidSecret && $normalizedProvider === 'capsbit') {
                    $offerId = (string) ($request->input('offer_id') ?? $request->input('offerId') ?? '');
                    $payoutVal = (string) ($request->input('payout') ?? $request->input('revenue') ?? $rawReward);
                    $capsbitRaw = $subId . $payoutVal . $offerId . $transId . $offerwall->secret_key;
                    
                    if (hash_equals(md5($capsbitRaw), $cleanProvidedSecret) || 
                        hash_equals(hash_hmac('sha256', $subId . $payoutVal . $offerId . $transId, $offerwall->secret_key), $cleanProvidedSecret)) {
                        $isValidSecret = true;
                    }
                }

                // 4. EarnWall & Offerwall.me Signature Formula: md5(subId . transId . reward . secret_key)
                if (!$isValidSecret && in_array($normalizedProvider, ['earnwall', 'offerwallme', 'offerwall'])) {
                    $postbackReward = (string) ($request->input('reward') ?? $rawReward);
                    $offerwallMeRaw = $subId . $transId . $postbackReward . $offerwall->secret_key;
                    if (hash_equals(md5($offerwallMeRaw), $cleanProvidedSecret) || 
                        hash_equals(md5($subId . $transId . $reward . $offerwall->secret_key), $cleanProvidedSecret)) {
                        $isValidSecret = true;
                    }
                }

                // 5. MoneyRain Signature Formula if passed via payload/param: sha256={HMAC} or {HMAC}
                if (!$isValidSecret && $normalizedProvider === 'moneyrain') {
                    $rawBody = $request->getContent();
                    $expectedHmac = hash_hmac('sha256', $rawBody, $offerwall->secret_key);
                    if (hash_equals($expectedHmac, $cleanProvidedSecret) || hash_equals('sha256=' . $expectedHmac, $cleanProvidedSecret)) {
                        $isValidSecret = true;
                    }
                }

                // 6. Dynamic SHA1 & SHA256 Hash verification for providers like TimeWall, Notik, etc.
                if (!$isValidSecret) {
                    $pubId = $request->input('pub_id', '');
                    $possibleHashes = [
                        // TimeWall standard: hash("sha256", userID . revenue . SecretKey)
                        hash('sha256', $subId . $rawRevenue . $offerwall->secret_key),
                        hash('sha256', $subId . $rawReward . $offerwall->secret_key),
                        hash('sha256', $subId . $reward . $offerwall->secret_key),

                        // SHA1 hashes
                        sha1($subId . $reward . $offerwall->secret_key),
                        sha1($subId . $rawReward . $offerwall->secret_key),
                        sha1($pubId . $subId . $reward . $offerwall->secret_key),
                        sha1($pubId . $subId . $rawReward . $offerwall->secret_key),
                        sha1($subId . $transId . $reward . $offerwall->secret_key),
                        sha1($subId . $transId . $rawReward . $offerwall->secret_key),
                        sha1($transId . $offerwall->secret_key),
                        sha1($offerwall->secret_key . $subId . $reward),
                        sha1($offerwall->secret_key . $subId . $rawReward),
                        sha1($offerwall->secret_key),

                        // MD5 hashes (Notik, AdGate, CPALead, etc.)
                        md5($subId . $reward . $offerwall->secret_key),
                        md5($subId . $rawReward . $offerwall->secret_key),
                        md5($subId . $rawRevenue . $offerwall->secret_key),
                        md5($subId . $transId . $reward . $offerwall->secret_key),
                        md5($subId . $transId . $rawReward . $offerwall->secret_key),
                        md5($subId . $transId . $rawRevenue . $offerwall->secret_key),
                        md5($pubId . $subId . $reward . $offerwall->secret_key),
                        md5($pubId . $subId . $rawReward . $offerwall->secret_key),
                        md5($transId . $offerwall->secret_key),
                        md5($offerwall->secret_key . $subId . $reward),
                        md5($offerwall->secret_key . $subId . $rawReward),
                        md5($offerwall->secret_key . $subId . $transId),
                        md5($offerwall->secret_key),

                        // SHA256 hashes (Notik v1 & others)
                        hash('sha256', $pubId . $subId . $reward . $offerwall->secret_key),
                        hash('sha256', $pubId . $subId . $rawReward . $offerwall->secret_key),
                        hash('sha256', $subId . $transId . $reward . $offerwall->secret_key),
                        hash('sha256', $subId . $transId . $rawReward . $offerwall->secret_key),
                        hash('sha256', $subId . $transId . $rawRevenue . $offerwall->secret_key),
                        hash('sha256', $transId . $offerwall->secret_key),
                        hash('sha256', $offerwall->secret_key . $subId . $reward),
                        hash('sha256', $offerwall->secret_key . $subId . $rawReward),
                        hash('sha256', $offerwall->secret_key),
                        hash_hmac('sha256', $subId . $reward, $offerwall->secret_key),
                        hash_hmac('sha256', $subId . $rawReward, $offerwall->secret_key),
                        hash_hmac('sha256', $subId . $rawRevenue, $offerwall->secret_key),
                        hash_hmac('sha256', $subId . $transId . $reward, $offerwall->secret_key),
                        hash_hmac('sha256', $subId . $transId . $rawReward, $offerwall->secret_key),
                        hash_hmac('sha256', $subId . $transId . $rawRevenue, $offerwall->secret_key),
                    ];

                    if (in_array($cleanProvidedSecret, array_map('strtolower', $possibleHashes))) {
                        $isValidSecret = true;
                    }
                }
            }

            if (!$isValidSecret) {
                \Log::warning("Offerwall Postback: Unauthorized Secret for provider {$provider}. Provided: {$providedSecret}");
                return response('Unauthorized Secret', 403);
            }
        }

        $user = User::find($subId);
        if (!$user) {
            \Log::warning("Offerwall Postback: User ID {$subId} not found in database.");
            return $respondSuccess();
        }

        $existingLog = OfferwallLog::where('transaction_id', $transId)->first();
        $reason = $request->input('reason') ?? $request->input('chargeback_reason') ?? 'Reversed by provider';

        if ($isChargeback) {
            if ($existingLog && $existingLog->status !== 'reversed') {
                DB::transaction(function () use ($user, $existingLog, $reason) {
                    // Lock the row to prevent double chargebacks from concurrent requests
                    $lockedLog = OfferwallLog::where('id', $existingLog->id)->lockForUpdate()->first();

                    if ($lockedLog && $lockedLog->status !== 'reversed') {
                        $chargebackAmount = (float) $lockedLog->amount;

                        if ($lockedLog->status === 'pending') {
                            $deductPending = min((float) $user->pending_balance, $chargebackAmount);
                            if ($deductPending > 0) {
                                $user->decrement('pending_balance', $deductPending);
                            }
                            $remainingChargeback = $chargebackAmount - $deductPending;
                            if ($remainingChargeback > 0) {
                                $user->decrement('main_balance', min((float) $user->main_balance, $remainingChargeback));
                            }
                        } else {
                            $user->decrement('main_balance', min((float) $user->main_balance, $chargebackAmount));
                        }

                        // Ensure balances strictly never become negative
                        if ($user->pending_balance < 0) {
                            $user->update(['pending_balance' => 0]);
                        }
                        if ($user->main_balance < 0) {
                            $user->update(['main_balance' => 0]);
                        }

                        $lockedLog->update([
                            'status' => 'reversed',
                            'reason' => $reason
                        ]);
                    }
                });
            }
            return $respondSuccess();
        }

        if ($existingLog) {
            \Log::info("Offerwall Postback: Transaction {$transId} already processed previously. Log ID: {$existingLog->id}, Status: {$existingLog->status}, Amount: {$existingLog->amount}");
            return $respondSuccess();
        }

        try {
            DB::transaction(function () use ($user, $provider, $normalizedProvider, $transId, $reward, $offerwall, $request) {
                $pendingHours = AppSetting::offerwallPendingHours();
                $releaseTime = Carbon::now()->addHours($pendingHours);
                
                $currencyAmount = $request->input('currencyAmount');
                $conversionRate = (float) AppSetting::getByKey('conversion_rate', 100);

                if ($normalizedProvider === 'moneyrain' && $request->filled('reward_currency_amount')) {
                    $creditedAmount = (float) $request->input('reward_currency_amount') * ($offerwall->reward_ratio ?? 1.0) * AppSetting::rewardMultiplier();
                } else {
                    $creditedAmount = $reward * ($offerwall->reward_ratio ?? 1.0) * $conversionRate * AppSetting::rewardMultiplier();
                }
                
                $initialStatus = $pendingHours > 0 ? 'pending' : 'approved';

                // Prevent race conditions using firstOrCreate inside transaction
                $log = OfferwallLog::firstOrCreate(
                    ['transaction_id' => $transId],
                    [
                        'user_id' => $user->id,
                        'provider' => ucfirst($provider),
                        'amount' => $creditedAmount,
                        'status' => $initialStatus,
                        'release_time' => $releaseTime,
                    ]
                );

                \Log::info("Offerwall Postback: Crediting user #{$user->id}", [
                    'creditedAmount' => $creditedAmount,
                    'initialStatus' => $initialStatus,
                    'wasRecentlyCreated' => $log->wasRecentlyCreated,
                    'log_id' => $log->id
                ]);

                // Only increment if it was actually created just now
                if ($log->wasRecentlyCreated) {
                    if ($initialStatus === 'pending') {
                        $user->increment('pending_balance', $creditedAmount);
                    } else {
                        $user->increment('main_balance', $creditedAmount);
                        // Record referral earning instantly if it's instantly approved
                        $this->referralService->recordReferredUserEarning($user, (float) $creditedAmount);
                    }
                }
            });
        } catch (\Exception $e) {
            \Log::error("Offerwall Postback DB Exception: " . $e->getMessage());
            return $respondSuccess();
        }

        return $respondSuccess();
    }

    public function releasePendingBalances(): int
    {
        $dueLogIds = OfferwallLog::where('status', 'pending')
            ->where('release_time', '<=', Carbon::now())
            ->pluck('id');

        $releasedCount = 0;

        foreach ($dueLogIds as $id) {
            $processed = DB::transaction(function () use ($id) {
                // Lock the specific log row to prevent overlapping cron jobs from double-crediting
                $lockedLog = OfferwallLog::where('id', $id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if ($lockedLog) {
                    $user = User::find($lockedLog->user_id);
                    if ($user) {
                        $releaseAmount = (float) $lockedLog->amount;
                        $deductPending = min((float) $user->pending_balance, $releaseAmount);
                        if ($deductPending > 0) {
                            $user->decrement('pending_balance', $deductPending);
                        }
                        $user->increment('main_balance', $releaseAmount);

                        if ($user->pending_balance < 0) {
                            $user->update(['pending_balance' => 0]);
                        }

                        $this->referralService->recordReferredUserEarning($user, $releaseAmount);
                    }
                    $lockedLog->update(['status' => 'approved']);
                    return true;
                }
                return false;
            });
            
            if ($processed) {
                $releasedCount++;
            }
        }

        return $releasedCount;
    }
}
