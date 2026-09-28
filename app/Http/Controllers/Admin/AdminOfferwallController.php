<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Offerwall;
use App\Models\OfferwallLog;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminOfferwallController extends Controller
{
    public function index(Request $request)
    {
        $offerwalls = Offerwall::orderBy('order')->get();

        $search   = trim((string) $request->input('search', ''));
        $status   = (string) $request->input('status', 'all');
        $provider = (string) $request->input('provider', 'all');

        $logsQuery = OfferwallLog::with('user:id,name,email,phone,pending_balance,main_balance')
            ->orderByDesc('id');

        if (in_array($status, ['pending', 'approved', 'reversed'], true)) {
            $logsQuery->where('status', $status);
        }

        if ($provider !== 'all' && $provider !== '') {
            $logsQuery->where('provider', $provider);
        }

        if ($search !== '') {
            $logsQuery->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', (int) $search)
                      ->orWhere('user_id', (int) $search);
                }
                $q->orWhere('transaction_id', 'like', "%{$search}%")
                  ->orWhere('provider', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $logsQuery->paginate(20)->withQueryString();

        $logStats = [
            'total_conversions' => OfferwallLog::count(),
            'total_amount'      => (float) OfferwallLog::sum('amount'),
            'pending_count'     => OfferwallLog::where('status', 'pending')->count(),
            'pending_amount'    => (float) OfferwallLog::where('status', 'pending')->sum('amount'),
            'approved_count'    => OfferwallLog::where('status', 'approved')->count(),
            'approved_amount'   => (float) OfferwallLog::where('status', 'approved')->sum('amount'),
            'reversed_count'    => OfferwallLog::where('status', 'reversed')->count(),
            'reversed_amount'   => (float) OfferwallLog::where('status', 'reversed')->sum('amount'),
        ];

        // Gather unique providers list
        $existingLogProviders = OfferwallLog::select('provider')->distinct()->pluck('provider')->filter()->values();
        $configuredProviders  = $offerwalls->pluck('name')->values();
        $providers = $existingLogProviders->merge($configuredProviders)->unique()->values();

        return Inertia::render('Admin/Offerwalls/Index', [
            'offerwalls' => $offerwalls,
            'logs'       => $logs,
            'logStats'   => $logStats,
            'providers'  => $providers,
            'filters'    => [
                'search'   => $search,
                'status'   => $status,
                'provider' => $provider,
            ],
            'lastCleanup' => AppSetting::getByKey('cron_last_run_offerwall:cleanup-logs', null),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                    => 'required|string|max:255',
            'iframe_url_pattern'      => 'required|string',
            'reward_ratio'            => 'required|numeric|min:0.01',
            'secret_key'              => 'nullable|string',
            'image_url'               => 'nullable|string',
            'description'             => 'nullable|string',
            'status'                  => 'boolean',
            'param_user_id'           => 'nullable|string',
            'param_amount'            => 'nullable|string',
            'param_transaction_id'    => 'nullable|string',
            'param_status'            => 'nullable|string',
            'param_secret_key'        => 'nullable|string',
            'status_chargeback_value' => 'nullable|string',
            'allowed_ips'             => 'nullable|string',
        ]);

        Offerwall::create($request->all());

        return back()->with('success', 'Offerwall created successfully!');
    }

    public function update(Request $request, Offerwall $offerwall)
    {
        $request->validate([
            'name'                    => 'required|string|max:255',
            'iframe_url_pattern'      => 'required|string',
            'reward_ratio'            => 'required|numeric|min:0.01',
            'secret_key'              => 'nullable|string',
            'image_url'               => 'nullable|string',
            'description'             => 'nullable|string',
            'status'                  => 'boolean',
            'param_user_id'           => 'nullable|string',
            'param_amount'            => 'nullable|string',
            'param_transaction_id'    => 'nullable|string',
            'param_status'            => 'nullable|string',
            'param_secret_key'        => 'nullable|string',
            'status_chargeback_value' => 'nullable|string',
            'allowed_ips'             => 'nullable|string',
        ]);

        $offerwall->update($request->all());

        return back()->with('success', 'Offerwall updated successfully!');
    }

    public function toggleStatus(Offerwall $offerwall)
    {
        $offerwall->update(['status' => !$offerwall->status]);
        return back()->with('success', 'Offerwall status updated successfully!');
    }

    public function destroy(Offerwall $offerwall)
    {
        $offerwall->delete();
        return back()->with('success', 'Offerwall deleted successfully!');
    }

    /**
     * Instantly release a specific pending offerwall hold to user's main balance.
     */
    public function releaseLog(OfferwallLog $log, ReferralService $referralService)
    {
        if ($log->status !== 'pending') {
            return back()->withErrors(['release' => 'Only pending logs can be released.']);
        }

        DB::transaction(function () use ($log, $referralService) {
            $lockedLog = OfferwallLog::where('id', $log->id)->lockForUpdate()->first();

            if ($lockedLog && $lockedLog->status === 'pending') {
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

                    $referralService->recordReferredUserEarning($user, $releaseAmount);
                }

                $lockedLog->update(['status' => 'approved']);
            }
        });

        return back()->with('success', "Transaction {$log->transaction_id} (+{$log->amount} pts) released to main balance!");
    }

    /**
     * Clean up completed (approved/reversed) logs older than specified days.
     * STRICT SAFETY: Never deletes pending logs!
     */
    public function cleanupLogs(Request $request)
    {
        $validated = $request->validate([
            'days' => 'required|integer|in:7,15,30,60,90,180,365',
        ]);

        $days = (int) $validated['days'];

        $deletedCount = OfferwallLog::whereIn('status', ['approved', 'reversed'])
            ->where('created_at', '<=', now()->subDays($days))
            ->delete();

        AppSetting::setByKey('cron_last_run_offerwall:cleanup-logs', now()->toDateTimeString());

        return back()->with('success', "Cleaned {$deletedCount} old completed/reversed offerwall log(s) older than {$days} days.");
    }
}
