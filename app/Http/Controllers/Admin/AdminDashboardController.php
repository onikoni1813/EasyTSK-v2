<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\PasswordResetTicket;
use App\Models\PromoCode;
use App\Models\ReferralContest;
use App\Models\SupportTicket;
use App\Models\Task;
use App\Models\User;
use App\Models\UserTask;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalUsers                  = User::where('role', 'user')->count();
        $newUsersThisWeek            = User::where('role', 'user')->where('created_at', '>=', now()->subDays(7))->count();
        $bannedUsersCount            = User::where('is_banned', true)->count();
        $totalMainLiability          = (float) User::sum('main_balance');
        $totalPendingLiability       = (float) User::sum('pending_balance');
        $totalPaidOut                = (float) Withdrawal::where('status', 'approved')->sum('amount_bdt');

        $pendingReviewsCount         = UserTask::where('status', 'pending')->count();
        $pendingWithdrawalsCount     = Withdrawal::where('status', 'pending')->count();
        $pendingCampaigns            = Campaign::where('status', 'pending')->count();
        $pendingPasswordTicketsCount = PasswordResetTicket::where('status', 'pending')->count();
        $openSupportTicketsCount     = SupportTicket::where('status', 'open')->count();
        $activeTasksCount            = Task::where('status', 'active')->count();
        $activeContestsCount         = ReferralContest::where('status', 'active')->count();

        // Flagged multi-account devices
        $flaggedDevices = User::select('device_hash', DB::raw('count(*) as count'))
            ->whereNotNull('device_hash')
            ->groupBy('device_hash')
            ->having('count', '>', 1)
            ->get();

        // High risk users (risk_score > 60)
        $highRiskUsers = User::where('risk_score', '>', 60)
            ->select('id', 'name', 'email', 'phone', 'main_balance', 'pending_balance', 'risk_score', 'health', 'is_banned', 'created_at')
            ->orderByDesc('risk_score')
            ->take(10)
            ->get()
            ->map(fn($u) => [
                'id'              => $u->id,
                'name'            => $u->name,
                'email'           => $u->email,
                'phone'           => $u->phone,
                'main_balance'    => (float) $u->main_balance,
                'pending_balance' => (float) $u->pending_balance,
                'risk_score'      => (float) $u->risk_score,
                'health'          => (int) $u->health,
                'is_banned'       => (bool) $u->is_banned,
                'created_at'      => $u->created_at ? $u->created_at->format('M d, Y') : '',
            ]);

        // Low health users (health <= 30) — may not yet be flagged as high risk, but worth admin attention
        $lowHealthUsers = User::where('health', '<=', 30)
            ->where('role', 'user')
            ->select('id', 'name', 'email', 'phone', 'main_balance', 'pending_balance', 'risk_score', 'health', 'is_banned', 'created_at')
            ->orderBy('health')
            ->take(10)
            ->get()
            ->map(fn($u) => [
                'id'              => $u->id,
                'name'            => $u->name,
                'email'           => $u->email,
                'phone'           => $u->phone,
                'main_balance'    => (float) $u->main_balance,
                'pending_balance' => (float) $u->pending_balance,
                'risk_score'      => (float) $u->risk_score,
                'health'          => (int) $u->health,
                'is_banned'       => (bool) $u->is_banned,
                'created_at'      => $u->created_at ? $u->created_at->format('M d, Y') : '',
            ]);

        // Active promo codes logic fixed with closure grouping and max_uses check
        $activeCodes = PromoCode::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereColumn('used_count', '<', 'max_uses')
            ->count();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalUsers'                  => $totalUsers,
                'newUsersThisWeek'            => $newUsersThisWeek,
                'bannedUsersCount'            => $bannedUsersCount,
                'totalMainLiability'          => $totalMainLiability,
                'totalPendingLiability'       => $totalPendingLiability,
                'totalPaidOut'                => $totalPaidOut,
                'pendingReviewsCount'         => $pendingReviewsCount,
                'pendingWithdrawalsCount'     => $pendingWithdrawalsCount,
                'pendingCampaigns'            => $pendingCampaigns,
                'pendingPasswordTicketsCount' => $pendingPasswordTicketsCount,
                'openSupportTicketsCount'     => $openSupportTicketsCount,
                'activeTasksCount'            => $activeTasksCount,
                'activeContestsCount'         => $activeContestsCount,
                'flaggedDevicesCount'         => $flaggedDevices->count(),
                'activeCodes'                => $activeCodes,
            ],
            'highRiskUsers'  => $highRiskUsers,
            'lowHealthUsers' => $lowHealthUsers,
            'growthChart'    => $this->buildGrowthChart(),
        ]);
    }

    /**
     * Build a 7-day trend of new user signups and approved task completions,
     * used to render the Growth Trend chart on the admin dashboard.
     */
    private function buildGrowthChart(): array
    {
        $days = collect(range(6, 0))->map(fn ($daysAgo) => Carbon::today()->subDays($daysAgo));

        $newUsersByDay = User::where('role', 'user')
            ->where('created_at', '>=', Carbon::today()->subDays(6))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $completedTasksByDay = UserTask::where('status', 'approved')
            ->where('updated_at', '>=', Carbon::today()->subDays(6))
            ->selectRaw('DATE(updated_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        return [
            'labels'         => $days->map(fn ($day) => $day->format('M d'))->values()->all(),
            'newUsers'       => $days->map(fn ($day) => (int) $newUsersByDay->get($day->format('Y-m-d'), 0))->values()->all(),
            'completedTasks' => $days->map(fn ($day) => (int) $completedTasksByDay->get($day->format('Y-m-d'), 0))->values()->all(),
        ];
    }

    // ── User Management ──────────────────────────────────────────────────

    public function banUser(Request $request, User $user)
    {
        if (Auth::id() === $user->id) {
            return back()->with('error', 'You cannot ban your own admin account.');
        }

        $user->update(['is_banned' => !$user->is_banned]);
        return back()->with('success', $user->is_banned ? "User {$user->name} banned." : "User {$user->name} unbanned.");
    }

    public function setRiskScore(Request $request, User $user)
    {
        $request->validate(['risk_score' => 'required|numeric|min:0|max:100']);
        $user->update(['risk_score' => $request->risk_score]);
        return back()->with('success', 'Risk score updated.');
    }

    public function setHealth(Request $request, User $user)
    {
        $request->validate(['health' => 'required|integer|min:0|max:' . User::MAX_HEALTH]);

        $attributes = ['health' => $request->health];
        if ($request->health > 0) {
            $attributes['health_depleted_at'] = null;
        }

        $user->update($attributes);

        return back()->with('success', "Health manually set to {$request->health} for {$user->name}.");
    }

    // ── Campaign Management ───────────────────────────────────────────────

    public function campaigns()
    {
        $campaigns = Campaign::with('user:id,name,email')
            ->latest()
            ->get()
            ->map(fn(Campaign $c) => [
                'id'           => $c->id,
                'user'         => ['name' => $c->user->name, 'email' => $c->user->email],
                'title'        => $c->title,
                'type'         => $c->type,
                'target_url'   => $c->target_url,
                'budget_points'=> (float) $c->budget_points,
                'total_clicks' => $c->total_clicks,
                'target_clicks'=> $c->target_clicks,
                'status'       => $c->status,
                'progress'     => $c->progressPercent(),
                'created_at'   => $c->created_at->diffForHumans(),
            ]);

        return Inertia::render('Admin/Campaigns', ['campaigns' => $campaigns]);
    }

    public function approveCampaign(Campaign $campaign)
    {
        if (!in_array($campaign->status, ['pending', 'paused'])) {
            return back()->with('error', 'Only pending or paused campaigns can be approved.');
        }

        $campaign->update(['status' => 'active']);
        return back()->with('success', 'Campaign approved and now live!');
    }

    public function rejectCampaign(Request $request, Campaign $campaign)
    {
        $request->validate(['admin_note' => 'required|string|max:200']);

        if (in_array($campaign->status, ['completed', 'rejected'])) {
            return back()->with('error', 'Campaign is already completed or rejected.');
        }

        $success = DB::transaction(function () use ($campaign, $request) {
            $lockedCampaign = Campaign::where('id', $campaign->id)->lockForUpdate()->first();
            
            if (in_array($lockedCampaign->status, ['completed', 'rejected'])) {
                return false;
            }

            // No refunds are given for rejected or completed campaigns as per business rules
            
            $lockedCampaign->update([
                'status'     => 'rejected',
                'admin_note' => $request->admin_note,
            ]);

            return true;
        });

        if (!$success) {
            return back()->with('error', 'Campaign is already completed or rejected concurrently.');
        }

        return back()->with('success', 'Campaign rejected and refund issued.');
    }

    public function deleteCampaign(Campaign $campaign)
    {
        if (in_array($campaign->status, ['completed', 'rejected'])) {
            $campaign->delete();
            return back()->with('success', 'Campaign deleted permanently.');
        }

        return back()->with('error', 'Only completed or rejected campaigns can be deleted.');
    }

    public function exportCampaigns(Request $request)
    {
        $campaigns = Campaign::with('user')->get();

        $csvHeader = ['ID', 'User Name', 'Email', 'Title', 'Target URL', 'Type', 'Budget Points', 'Cost Per Click', 'Total Clicks', 'Target Clicks', 'Status', 'Admin Note', 'Created At'];

        $callback = function () use ($campaigns, $csvHeader) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $csvHeader);

            foreach ($campaigns as $c) {
                fputcsv($file, [
                    $c->id,
                    $c->user ? $c->user->name : 'N/A',
                    $c->user ? $c->user->email : 'N/A',
                    $c->title,
                    $c->target_url,
                    $c->type,
                    $c->budget_points,
                    $c->cost_per_click,
                    $c->total_clicks,
                    $c->target_clicks,
                    $c->status,
                    $c->admin_note,
                    $c->created_at,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="campaigns_backup_' . date('Y-m-d') . '.csv"',
        ]);
    }

    // ── Promo Code Management ─────────────────────────────────────────────

    public function promoCodes()
    {
        $codes = PromoCode::latest()->get()->map(fn($c) => [
            'id'            => $c->id,
            'code'          => $c->code,
            'description'   => $c->description,
            'reward_points' => (float) $c->reward_points,
            'max_uses'      => $c->max_uses,
            'used_count'    => $c->used_count,
            'expires_at'    => $c->expires_at?->format('Y-m-d'),
            'is_active'     => $c->is_active,
        ]);

        return Inertia::render('Admin/PromoCodes', ['codes' => $codes]);
    }

    public function storePromoCode(Request $request)
    {
        if ($request->filled('code')) {
            $request->merge(['code' => strtoupper(trim($request->code))]);
        } else {
            $request->merge(['code' => null]);
        }

        if (!$request->filled('expires_at')) {
            $request->merge(['expires_at' => null]);
        }

        $request->validate([
            'code'          => 'nullable|string|max:20|unique:promo_codes,code',
            'description'   => 'nullable|string|max:100',
            'reward_points' => 'required|numeric|min:1|max:10000',
            'max_uses'      => 'required|integer|min:1',
            'expires_at'    => 'nullable|date|after_or_equal:today',
        ], [
            'code.unique'                => 'This promo code already exists.',
            'expires_at.after_or_equal' => 'The expiration date must be today or a future date.',
        ]);

        PromoCode::create([
            'code'          => $request->code ?: strtoupper(Str::random(8)),
            'description'   => $request->description,
            'reward_points' => $request->reward_points,
            'max_uses'      => $request->max_uses,
            'expires_at'    => $request->expires_at ? Carbon::parse($request->expires_at)->endOfDay() : null,
            'is_active'     => true,
        ]);

        return back()->with('success', 'Promo code created successfully!');
    }

    public function togglePromoCode(PromoCode $promoCode)
    {
        $promoCode->update(['is_active' => !$promoCode->is_active]);
        return back()->with('success', 'Promo code status toggled.');
    }

    public function deletePromoCode(PromoCode $promoCode)
    {
        $promoCode->delete();
        return back()->with('success', 'Promo code deleted successfully!');
    }
}
