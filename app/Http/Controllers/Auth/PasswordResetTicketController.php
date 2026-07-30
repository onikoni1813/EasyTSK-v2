<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordResetTicketController extends Controller
{
    /**
     * Submit a new password reset support ticket.
     */
    public function submitTicket(Request $request)
    {
        $request->validate([
            'phone'       => 'required|string',
            'message'     => 'nullable|string|max:500',
            'device_hash' => 'nullable|string',
        ]);

        $phone = trim($request->phone);

        // Verify that user exists with this phone or email
        $user = User::where('phone', $phone)
            ->orWhere('email', $phone)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'phone' => 'No registered user found with this phone or email.',
            ]);
        }

        // Check if there is already an active pending or approved ticket for this user
        $existingTicket = PasswordResetTicket::where('phone', $user->phone)
            ->whereIn('status', ['pending', 'approved'])
            ->latest()
            ->first();

        if ($existingTicket) {
            if ($existingTicket->status === 'pending') {
                return response()->json([
                    'success'     => true,
                    'message'     => 'You already have a pending ticket.',
                    'ticket_code' => $existingTicket->ticket_code,
                    'status'      => 'pending',
                ]);
            }

            if ($existingTicket->status === 'approved') {
                // Check 24-hour expiration rule
                if ($existingTicket->approved_at && $existingTicket->approved_at->addHours(24)->isPast()) {
                    $existingTicket->update([
                        'status'     => 'rejected',
                        'admin_note' => 'Approved reset token expired after 24 hours.',
                    ]);
                } else {
                    return response()->json([
                        'success'     => true,
                        'message'     => 'Your reset request is already approved! You have 24 hours to reset your password.',
                        'ticket_code' => $existingTicket->ticket_code,
                        'status'      => 'approved',
                    ]);
                }
            }
        }

        $ticketCode = PasswordResetTicket::generateTicketCode();

        $ticket = PasswordResetTicket::create([
            'user_id'     => $user->id,
            'phone'       => $user->phone,
            'ticket_code' => $ticketCode,
            'message'     => $request->message ?? 'Forgot password and recovery PIN.',
            'status'      => 'pending',
            'ip_address'  => $request->ip(),
            'device_hash' => $request->device_hash,
        ]);

        return response()->json([
            'success'     => true,
            'message'     => 'Password reset ticket submitted successfully! Please save your ticket code.',
            'ticket_code' => $ticket->ticket_code,
            'status'      => 'pending',
        ]);
    }

    /**
     * Check ticket status by phone + ticket_code.
     */
    public function checkTicketStatus(Request $request)
    {
        $request->validate([
            'phone'       => 'required|string',
            'ticket_code' => 'required|string',
        ]);

        $phone      = trim($request->phone);
        $ticketCode = strtoupper(trim($request->ticket_code));

        $ticket = PasswordResetTicket::where('ticket_code', $ticketCode)
            ->where(function ($q) use ($phone) {
                $q->where('phone', $phone)
                  ->orWhereHas('user', function ($uq) use ($phone) {
                      $uq->where('email', $phone);
                  });
            })
            ->latest()
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'No ticket found matching this ticket code and phone/email.',
            ], 404);
        }

        // Enforce 24-hour limit after admin approval
        if ($ticket->status === 'approved' && $ticket->approved_at && $ticket->approved_at->addHours(24)->isPast()) {
            $ticket->update([
                'status'     => 'rejected',
                'admin_note' => 'Approved reset token expired after 24 hours. Please open a new ticket.',
            ]);
        }

        return response()->json([
            'success'     => true,
            'ticket_code' => $ticket->ticket_code,
            'status'      => $ticket->status,
            'admin_note'  => $ticket->admin_note,
            'reset_code'  => $ticket->status === 'approved' ? $ticket->reset_code : null,
            'created_at'  => $ticket->created_at->format('M d, Y H:i'),
        ]);
    }

    /**
     * Reset password using an approved ticket.
     */
    public function resetPasswordWithTicket(Request $request)
    {
        $request->validate([
            'phone'                     => 'required|string',
            'ticket_code'               => 'required|string',
            'reset_code'                => 'required|string',
            'new_password'              => 'required|string|min:6|confirmed',
        ]);

        $phone      = trim($request->phone);
        $ticketCode = strtoupper(trim($request->ticket_code));
        $resetCode  = trim($request->reset_code);

        $ticket = PasswordResetTicket::where('ticket_code', $ticketCode)
            ->where('status', 'approved')
            ->where(function ($q) use ($phone) {
                $q->where('phone', $phone)
                  ->orWhereHas('user', function ($uq) use ($phone) {
                      $uq->where('email', $phone);
                  });
            })
            ->first();

        if (!$ticket) {
            throw ValidationException::withMessages([
                'ticket_code' => 'Invalid or unapproved ticket code.',
            ]);
        }

        // Check 24-hour expiration limit
        if ($ticket->approved_at && $ticket->approved_at->addHours(24)->isPast()) {
            $ticket->update([
                'status'     => 'rejected',
                'admin_note' => 'Approved reset token expired after 24 hours.',
            ]);

            throw ValidationException::withMessages([
                'ticket_code' => 'The 24-hour approval window for this ticket has expired. Please submit a new ticket.',
            ]);
        }

        if ((string) $ticket->reset_code !== (string) $resetCode) {
            throw ValidationException::withMessages([
                'reset_code' => 'Invalid 6-digit Reset OTP/Code.',
            ]);
        }

        $user = User::find($ticket->user_id) ?? User::where('phone', $ticket->phone)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'phone' => 'User account not found.',
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Mark ticket as completed
        $ticket->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully! You can now sign in with your new password.',
        ]);
    }
}
