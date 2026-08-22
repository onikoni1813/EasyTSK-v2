<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::with('user:id,name,email')
            ->latest()
            ->paginate(15);

        $levels = Level::orderBy('level_number', 'asc')->get(['id', 'level_number', 'xp_required']);
        $totalUsers = User::count();

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
            'levels' => $levels,
            'totalUsers' => $totalUsers,
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'target_type' => 'required|in:all,level,user',
            'target_level' => 'nullable|required_if:target_type,level|integer',
            'user_query' => 'nullable|required_if:target_type,user|string',
            'delivery_mode' => 'required|in:drawer,popup',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'type' => 'required|in:info,success,warning,danger',
            'action_url' => 'nullable|string|max:500',
        ]);

        $title = trim($request->title);
        $message = trim($request->message);
        $type = $request->type;
        $actionUrl = $request->action_url ? trim($request->action_url) : null;
        $isPopup = $request->delivery_mode === 'popup';

        if ($request->target_type === 'all') {
            $count = Notification::sendToAll($title, $message, $type, $actionUrl, $isPopup);
            return back()->with('success', "Broadcast successfully sent to all {$count} users! 🎉");
        }

        if ($request->target_type === 'level') {
            $level = (int) $request->target_level;
            $count = Notification::sendToLevel($level, $title, $message, $type, $actionUrl, $isPopup);
            return back()->with('success', "Notification successfully sent to {$count} users in Level {$level}! ⚡");
        }

        if ($request->target_type === 'user') {
            $query = trim($request->user_query);
            $user = User::where('id', $query)
                ->orWhere('email', $query)
                ->orWhere('name', 'LIKE', "%{$query}%")
                ->first();

            if (!$user) {
                return back()->with('error', "User not found with ID/Email: {$query}");
            }

            Notification::send($user, $title, $message, $type, $actionUrl, $isPopup);
            return back()->with('success', "Notification successfully sent to {$user->name} ({$user->email})! 📩");
        }

        return back()->with('error', 'Invalid target type.');
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();
        return back()->with('success', 'Notification deleted successfully.');
    }
}
