<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    /**
     * Display listing of user's support tickets.
     */
    public function index()
    {
        $user = Auth::user();

        $tickets = SupportTicket::where('user_id', $user->id)
            ->with(['latestMessage'])
            ->orderByDesc('updated_at')
            ->paginate(15);

        return Inertia::render('Support/Index', [
            'tickets' => $tickets,
        ]);
    }

    /**
     * Store a newly created support ticket in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|in:withdrawal,task,account,general',
            'subject'  => 'required|string|max:255',
            'message'  => 'required|string|max:2000',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        $user = Auth::user();

        $ticket = SupportTicket::create([
            'user_id'       => $user->id,
            'ticket_number' => SupportTicket::generateTicketNumber(),
            'category'      => $request->category,
            'subject'       => trim($request->subject),
            'status'        => 'open',
            'priority'      => $request->priority ?? 'medium',
            'last_reply_at' => now(),
        ]);

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => $user->id,
            'is_admin'  => false,
            'message'   => trim($request->message),
        ]);

        return redirect()->route('support.show', $ticket->id)
            ->with('success', "Support ticket {$ticket->ticket_number} created successfully.");
    }

    /**
     * Display the specified support ticket conversation.
     */
    public function show(SupportTicket $ticket)
    {
        if ($ticket->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized access to this support ticket.');
        }

        $ticket->load([
            'user:id,name,email,phone',
            'messages.sender:id,name,role',
        ]);

        return Inertia::render('Support/Show', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * Post a reply message to the support ticket thread.
     */
    public function reply(Request $request, SupportTicket $ticket)
    {
        if ($ticket->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized access to reply to this ticket.');
        }

        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => Auth::id(),
            'is_admin'  => false,
            'message'   => trim($request->message),
        ]);

        // If ticket was closed or resolved, reopen it to 'open' status upon user reply
        $ticket->update([
            'status'        => 'open',
            'last_reply_at' => now(),
        ]);

        return back()->with('success', 'Message sent successfully.');
    }
}
