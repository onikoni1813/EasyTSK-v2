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
        $normalizedProvider = strtolower(str_replace([' ', '-', '_'], '', $provider));
        $offerwall = \App\Models\Offerwall::where(function ($query) use ($provider, $normalizedProvider) {
            $query->where('name', $provider)
                ->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(name, ' ', ''), '-', ''), '_', '')) = ?", [$normalizedProvider]);
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

        $subId = $request->input($paramUserId) ?? $request->input('subId') ?? $request->input('uid') ?? $request->input('userID');
        $transId = $request->input($paramTransId) ?? $request->input('tx_id') ?? $request->input('transId') ?? $request->input('transactionID');
        
        $type = strtolower((string) $request->input('type', ''));
        $status = strtolower((string) $request->input($paramStatus, $type ?: '1'));

        // Handle TimeWall lifecycle stages: 'hold' and 'hold_cancelled' (do NOT credit/debit, return 200 OK)
        if ($type === 'hold' || $type === 'hold_cancelled') {
            return response('1', 200);
        }

        $rawRevenue = (string) ($request->input('revenue') ?? '');
        $rawReward = (string) ($request->input($paramAmount) ?? $request->input('currencyAmount') ?? $request->input('reward') ?? $request->input('payout') ?? $rawRevenue);
        
        $rewardNum = (float) $rawReward;
        $revenueNum = (float) $rawRevenue;

        // Check if request is a chargeback (explicit status/type or negative amounts)
        $isChargeback = ($status === $chargebackValue || $status === '2' || $status === 'chargeback' || $type === 'chargeback' || $rewardNum < 0 || $revenueNum < 0);

        $reward = abs($rewardNum != 0 ? $rewardNum : $revenueNum);

        if (!$subId || (!$transId && !$isChargeback)) {
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
                ?? $request->input('hash') 
                ?? $request->input('signature') 
                ?? $request->input('hash_signature') 
                ?? $request->input('secret') 
                ?? $request->input('secure') 
                ?? $request->header('X-Secret-Key') 
                ?? $request->header($paramSecret);

            $isValidSecret = false;

            if ($providedSecret !== null) {
                // 1. Direct exact match
                if ($providedSecret === $offerwall->secret_key) {
                    $isValidSecret = true;
                }
                
                // 2. Query parameter 'secret' or 'secure' match
                if ($request->input('secret') === $offerwall->secret_key || $request->input('secure') === $offerwall->secret_key) {
                    $isValidSecret = true;
                }

                // 3. Dynamic SHA1 & SHA256 Hash verification for providers like TimeWall & Notik
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

                        // SHA256 hashes (Notik v1 & others)
                        hash('sha256', $pubId . $subId . $reward . $offerwall->secret_key),
                        hash('sha256', $pubId . $subId . $rawReward . $offerwall->secret_key),
                        hash('sha256', $subId . $transId . $reward . $offerwall->secret_key),
                        hash('sha256', $subId . $transId . $rawReward . $offerwall->secret_key),
                        hash('sha256', $transId . $offerwall->secret_key),
                        hash('sha256', $offerwall->secret_key . $subId . $reward),
                        hash('sha256', $offerwall->secret_key . $subId . $rawReward),
                        hash('sha256', $offerwall->secret_key),
                        hash_hmac('sha256', $subId . $reward, $offerwall->secret_key),
                        hash_hmac('sha256', $subId . $rawReward, $offerwall->secret_key),
                        hash_hmac('sha256', $subId . $transId . $reward, $offerwall->secret_key),
                        hash_hmac('sha256', $subId . $transId . $rawReward, $offerwall->secret_key),
                    ];

                    if (in_array(strtolower($providedSecret), array_map('strtolower', $possibleHashes))) {
                        $isValidSecret = true;
                    }
                }
            }

            if (!$isValidSecret) {
                return response('Unauthorized Secret', 403);
            }
        }

        $user = User::find($subId);
        if (!$user) {
            return response('1', 200);
        }

        $existingLog = OfferwallLog::where('transaction_id', $transId)->first();
        $reason = $request->input('reason') ?? $request->input('chargeback_reason') ?? 'Reversed by provider';

        if ($isChargeback) {
            if ($existingLog && $existingLog->status !== 'reversed') {
                DB::transaction(function () use ($user, $existingLog, $reason) {
                    // Lock the row to prevent double chargebacks from concurrent requests
                    $lockedLog = OfferwallLog::where('id', $existingLog->id)->lockForUpdate()->first();

                    if ($lockedLog && $lockedLog->status !== 'reversed') {
                        if ($lockedLog->status === 'pending') {
                            $user->decrement('pending_balance', $lockedLog->amount);
                        } else {
                            $user->decrement('main_balance', $lockedLog->amount);
                        }

                        $lockedLog->update([
                            'status' => 'reversed',
                            'reason' => $reason
                        ]);
                    }
                });
            }
            return response('1', 200);
        }

        if ($existingLog) {
            return response('1', 200);
        }

        try {
            DB::transaction(function () use ($user, $provider, $transId, $reward, $offerwall) {
                $pendingHours = AppSetting::offerwallPendingHours();
                $releaseTime = Carbon::now()->addHours($pendingHours);
                $conversionRate = (float) AppSetting::getByKey('conversion_rate', 100);
                $creditedAmount = $reward * ($offerwall->reward_ratio ?? 1.0) * $conversionRate * AppSetting::rewardMultiplier();
                
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
            // If unique constraint fails during concurrent requests, it will land here. 
            // We return 200 so provider stops retrying.
            return response('1', 200);
        }

        return response('1', 200);
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
                        $user->decrement('pending_balance', $lockedLog->amount);
                        $user->increment('main_balance', $lockedLog->amount);

                        $this->referralService->recordReferredUserEarning($user, (float) $lockedLog->amount);
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
