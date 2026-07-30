<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminSupportTicketController extends Controller
{
    /**
     * Display listing of all user support tickets.
     */
    public function index(Request $request)
    {
        $search   = $request->input('search');
        $status   = $request->input('status', 'all');
        $category = $request->input('category', 'all');

        $tickets = SupportTicket::query()
            ->with(['user:id,name,email,phone,created_at,risk_score', 'latestMessage'])
            ->when($search, function ($query, $search) {
                $query->where('ticket_number', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                      });
            })
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($category !== 'all', function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total'       => SupportTicket::count(),
            'open'        => SupportTicket::where('status', 'open')->count(),
            'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
            'resolved'    => SupportTicket::where('status', 'resolved')->count(),
            'closed'      => SupportTicket::where('status', 'closed')->count(),
        ];

        return Inertia::render('Admin/SupportTickets/Index', [
            'tickets' => $tickets,
            'filters' => [
                'search'   => $search,
                'status'   => $status,
                'category' => $category,
            ],
            'stats'   => $stats,
        ]);
    }

    /**
     * Display the specified support ticket conversation for Admin.
     */
    public function show(SupportTicket $ticket)
    {
        $ticket->load([
            'user:id,name,email,phone,created_at,risk_score,is_banned,health',
            'messages.sender:id,name,role',
        ]);

        return Inertia::render('Admin/SupportTickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * Admin reply to support ticket thread.
     */
    public function reply(Request $request, SupportTicket $ticket)
    {
        $request->validate([
            'message' => 'required|string|max:3000',
            'status'  => 'nullable|in:open,in_progress,resolved,closed',
        ]);

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => Auth::id(),
            'is_admin'  => true,
            'message'   => trim($request->message),
        ]);

        $newStatus = $request->status ?? 'in_progress';

        $ticket->update([
            'status'        => $newStatus,
            'last_reply_at' => now(),
        ]);

        // Send in-app notification to the user
        Notification::create([
            'user_id' => $ticket->user_id,
            'title'   => '🎧 Support Ticket Update',
            'message' => "Admin replied to your support ticket #{$ticket->ticket_number}",
            'type'    => 'info',
            'is_read' => false,
        ]);

        return back()->with('success', 'Reply sent to user successfully.');
    }

    /**
     * Update ticket status (e.g. resolve or close ticket).
     */
    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        $ticket->update([
            'status' => $request->status,
        ]);

        return back()->with('success', "Ticket status updated to {$request->status}.");
    }
}
