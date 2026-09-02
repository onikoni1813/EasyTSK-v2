<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'phone'           => 'required|string|max:20|unique:users,phone,' . $user->id,
            'role'            => 'required|in:admin,user',
            'main_balance'    => 'required|numeric|min:0',
            'pending_balance' => 'required|numeric|min:0',
            'is_banned'       => 'boolean',
            'risk_score'      => 'required|numeric|min:0|max:100',
            'health'          => 'required|integer|min:0|max:100',
        ]);

        // Prevent self-lockout
        if (Auth::id() === $user->id) {
            if ($validated['role'] !== 'admin' || !empty($validated['is_banned'])) {
                return back()->with('error', 'You cannot downgrade or ban your own admin account.');
            }
        }

        // Handle health depletion state correctly
        if ($validated['health'] > 0) {
            $validated['health_depleted_at'] = null;
        } elseif ($validated['health'] <= 0 && $user->health > 0) {
            $validated['health_depleted_at'] = now();
        }

        $user->update($validated);

        return back()->with('success', "User {$user->name} updated successfully.");
    }

    /**
     * Get detailed history (Tasks, Referrals, Withdrawals) for a specific user.
     */
    public function history(User $user)
    {
        // 1. Task History
        $tasks = \App\Models\UserTask::where('user_id', $user->id)
            ->with(['task:id,title,reward_coins,type', 'screenshotHashes'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($ut) {
                return [
                    'id'                => $ut->id,
                    'task_title'        => $ut->task ? $ut->task->title : 'Unknown Task',
                    'task_type'         => $ut->task ? ($ut->task->type ?? 'general') : 'general',
                    'reward_points'     => $ut->task ? (float) $ut->task->reward_coins : 0,
                    'status'            => $ut->status,
                    'submitted_data'    => $ut->submitted_data,
                    'screenshot_hashes' => $ut->screenshotHashes->map(fn($sh) => [
                        'id' => $sh->id,
                        'file_path' => $sh->file_path,
                    ]),
                    'admin_note'        => $ut->admin_note,
                    'created_at'        => $ut->created_at ? $ut->created_at->format('M d, Y H:i') : '',
                ];
            });

        // 2. Referral History
        $referralModels = \App\Models\ReferralTracking::where('referrer_id', $user->id)
            ->with(['referredUser:id,name,email,phone'])
            ->latest()
            ->limit(50)
            ->get();

        $referredUserIds = $referralModels->pluck('referred_user_id')->filter()->unique()->toArray();
        $tasksCompletedCounts = [];
        if (!empty($referredUserIds)) {
            $tasksCompletedCounts = \App\Models\UserTask::whereIn('user_id', $referredUserIds)
                ->where('status', 'approved')
                ->selectRaw('user_id, COUNT(*) as aggregate')
                ->groupBy('user_id')
                ->pluck('aggregate', 'user_id')
                ->toArray();
        }

        $referrals = $referralModels->map(function ($ref) use ($tasksCompletedCounts) {
            return [
                'id'              => $ref->id,
                'referred_user'   => $ref->referredUser ? [
                    'name'  => $ref->referredUser->name,
                    'phone' => $ref->referredUser->phone,
                    'email' => $ref->referredUser->email,
                ] : null,
                'status'          => $ref->status,
                'locked_reward'   => (float) $ref->locked_reward,
                'tasks_completed' => (int) ($tasksCompletedCounts[$ref->referred_user_id] ?? 0),
                'created_at'      => $ref->created_at ? $ref->created_at->format('M d, Y') : '',
            ];
        });

        // 3. Withdrawal History
        $withdrawals = \App\Models\Withdrawal::where('user_id', $user->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($w) {
                return [
                    'id'               => $w->id,
                    'amount_bdt'       => (float) $w->amount_bdt,
                    'amount_coins'     => (float) $w->amount_coins,
                    'payment_method'   => $w->payment_method,
                    'account_details'  => $w->account_details,
                    'status'           => $w->status,
                    'admin_note'       => $w->admin_note,
                    'rejection_reason' => $w->rejection_reason,
                    'transaction_id'   => $w->transaction_id,
                    'created_at'       => $w->created_at ? $w->created_at->format('M d, Y H:i') : '',
                ];
            });

        // Income breakdown for user
        $taskIncome = (float) \App\Models\UserTask::where('user_id', $user->id)
            ->where('user_tasks.status', 'approved')
            ->join('tasks', 'user_tasks.task_id', '=', 'tasks.id')
            ->sum('tasks.reward_coins');

        $referralIncome = (float) \App\Models\ReferralTracking::where('referrer_id', $user->id)
            ->whereIn('status', ['unlocked', 'claimed'])
            ->sum('locked_reward');

        $offerwallIncome = (float) \App\Models\OfferwallLog::where('user_id', $user->id)
            ->where('status', 'approved')
            ->sum('amount');

        $spinIncome = (float) \App\Models\WheelSpin::where('user_id', $user->id)
            ->where('prize_type', 'coins')
            ->sum('prize_value');

        $promoIncome = (float) \App\Models\PromoCodeUse::where('user_id', $user->id)
            ->join('promo_codes', 'promo_code_uses.promo_code_id', '=', 'promo_codes.id')
            ->sum('promo_codes.reward_points');

        $welcomeBonusIncome = $user->has_claimed_welcome_bonus ? (float) ($user->welcome_bonus_amount ?? 0) : 0;

        $totalUserIncome = $taskIncome + $referralIncome + $offerwallIncome + $spinIncome + $promoIncome + $welcomeBonusIncome;

        // Admin revenue calculation from this user
        $withdrawalCharges = (float) \App\Models\Withdrawal::where('user_id', $user->id)
            ->where('status', 'approved')
            ->sum('charge_coins');

        $campaignSpend = (float) \App\Models\Campaign::where('user_id', $user->id)
            ->sum('budget_points');

        $totalAdminRevenue = $withdrawalCharges + $campaignSpend;

        // Stats summary
        $stats = [
            'total_tasks'           => \App\Models\UserTask::where('user_id', $user->id)->count(),
            'approved_tasks'        => \App\Models\UserTask::where('user_id', $user->id)->where('status', 'approved')->count(),
            'total_referrals'       => \App\Models\ReferralTracking::where('referrer_id', $user->id)->count(),
            'total_withdrawn_bdt'   => (float) \App\Models\Withdrawal::where('user_id', $user->id)->where('status', 'approved')->sum('amount_bdt'),
            'pending_withdrawal_bdt'=> (float) \App\Models\Withdrawal::where('user_id', $user->id)->where('status', 'pending')->sum('amount_bdt'),
            'total_user_income'     => round($totalUserIncome, 2),
            'total_admin_revenue'   => round($totalAdminRevenue, 2),
            'task_income'           => round($taskIncome, 2),
            'referral_income'       => round($referralIncome, 2),
        ];

        return response()->json([
            'user'        => [
                'id'         => $user->id,
                'name'       => $user->name,
                'phone'      => $user->phone,
                'email'      => $user->email,
                'created_at' => $user->created_at ? $user->created_at->format('M d, Y') : '',
            ],
            'stats'       => $stats,
            'tasks'       => $tasks,
            'referrals'   => $referrals,
            'withdrawals' => $withdrawals,
        ]);
    }

    /**
     * Impersonate (Log in as) the specified user.
     */
    public function impersonate(Request $request, User $user)
    {
        $currentAdmin = Auth::user();

        // Prevent self-impersonation
        if ($currentAdmin && $currentAdmin->id === $user->id) {
            return back()->with('error', 'You are already logged into this admin account.');
        }

        // Store original admin ID in session
        $request->session()->put('impersonated_by_admin_id', $currentAdmin->id);

        // Login as target user
        Auth::login($user);

        // Trigger full page reload to ensure fresh CSRF token and session cookies in browser
        return Inertia::location(route('dashboard'));
    }

    /**
     * Leave impersonation and switch back to the original admin account.
     */
    public function leaveImpersonate(Request $request)
    {
        $adminId = $request->session()->pull('impersonated_by_admin_id');

        if ($adminId) {
            $admin = User::find($adminId);

            if ($admin && $admin->isAdmin()) {
                Auth::login($admin);
                // Trigger full page reload back to admin panel
                return Inertia::location(route('admin.users.index'));
            }
        }

        return Inertia::location(route('dashboard'));
    }
}

