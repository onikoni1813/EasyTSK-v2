<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\User;
use App\Models\UserTask;
use App\Models\Withdrawal;
use App\Services\SiteContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Public homepage — redirect authenticated users to dashboard on main platform,
     * or delegate to ExternalSiteController on external domains.
     */
    public function index(Request $request)
    {
        if (SiteContext::isExternal()) {
            return app(ExternalSiteController::class)->index($request);
        }

        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $stats = $this->getPublicStats();

        return Inertia::render('Welcome', [
            'stats' => $stats,
        ]);
    }

    /**
     * About Us page.
     */
    public function about(Request $request)
    {
        if (SiteContext::isExternal()) {
            return app(ExternalSiteController::class)->page($request, 'about');
        }

        return Inertia::render('Legal/About');
    }

    /**
     * Contact Us page.
     */
    public function contact(Request $request)
    {
        if (SiteContext::isExternal()) {
            return app(ExternalSiteController::class)->page($request, 'contact');
        }

        return Inertia::render('Legal/Contact');
    }

    /**
     * Terms of Service page.
     */
    public function terms(Request $request)
    {
        if (SiteContext::isExternal()) {
            return app(ExternalSiteController::class)->page($request, 'terms');
        }

        return Inertia::render('Legal/Terms');
    }

    /**
     * Privacy Policy page.
     */
    public function privacy(Request $request)
    {
        if (SiteContext::isExternal()) {
            return app(ExternalSiteController::class)->page($request, 'privacy');
        }

        return Inertia::render('Legal/Privacy');
    }

    /**
     * Cookie Policy page.
     */
    public function cookiePolicy(Request $request)
    {
        if (SiteContext::isExternal()) {
            return app(ExternalSiteController::class)->page($request, 'cookie-policy');
        }

        return Inertia::render('Legal/CookiePolicy');
    }

    /**
     * Get aggregated public platform stats (Live Data + Admin Demo Data).
     */
    private function getPublicStats(): array
    {
        try {
            $demoUsers   = (int) AppSetting::getByKey('demo_users', '1200');
            $demoTasks   = (int) AppSetting::getByKey('demo_tasks', '45000');
            $demoPayouts = (float) AppSetting::getByKey('demo_payouts', '280000');

            $liveUsers   = User::count();
            $liveTasks   = UserTask::where('status', 'approved')->count();
            $livePayouts = Withdrawal::where('status', 'approved')->sum('amount_bdt');

            return [
                'total_users'     => $liveUsers + $demoUsers,
                'tasks_completed' => $liveTasks + $demoTasks,
                'total_payouts'   => (float) ($livePayouts + $demoPayouts),
            ];
        } catch (\Exception $e) {
            return [
                'total_users'     => 1200,
                'tasks_completed' => 45000,
                'total_payouts'   => 280000.0,
            ];
        }
    }
}
