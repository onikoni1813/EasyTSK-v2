<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetTicket;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminPasswordTicketController extends Controller
{
    /**
     * Display listing of password reset tickets.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'all');

        $tickets = PasswordResetTicket::query()
            ->with(['user:id,name,email,phone,created_at,risk_score,is_banned'])
            ->when($search, function ($query, $search) {
                $query->where('phone', 'like', "%{$search}%")
                      ->orWhere('ticket_code', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                      });
            })
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total'     => PasswordResetTicket::count(),
            'pending'   => PasswordResetTicket::where('status', 'pending')->count(),
            'approved'  => PasswordResetTicket::where('status', 'approved')->count(),
            'completed' => PasswordResetTicket::where('status', 'completed')->count(),
            'rejected'  => PasswordResetTicket::where('status', 'rejected')->count(),
        ];

        return Inertia::render('Admin/PasswordTickets/Index', [
            'tickets' => $tickets,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'stats'   => $stats,
        ]);
    }

    /**
     * Approve a password reset ticket and generate reset code.
     */
    public function approve(Request $request, PasswordResetTicket $ticket)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        if ($ticket->status !== 'pending') {
            return back()->with('error', "Only pending tickets can be approved. Current status: {$ticket->status}");
        }

        $resetCode = PasswordResetTicket::generateResetCode();

        $ticket->update([
            'status'      => 'approved',
            'reset_code'  => $resetCode,
            'admin_note'  => $request->admin_note ?? 'Ticket approved by admin. Reset code generated.',
            'approved_at' => now(),
        ]);

        return back()->with('success', "Ticket {$ticket->ticket_code} approved! Reset Code: {$resetCode}");
    }

    /**
     * Reject a password reset ticket.
     */
    public function reject(Request $request, PasswordResetTicket $ticket)
    {
        $request->validate([
            'admin_note' => 'required|string|max:500',
        ]);

        if ($ticket->status === 'completed') {
            return back()->with('error', "Cannot reject a ticket that has already been completed.");
        }

        $ticket->update([
            'status'     => 'rejected',
            'admin_note' => $request->admin_note,
        ]);

        return back()->with('success', "Ticket {$ticket->ticket_code} rejected.");
    }
}
