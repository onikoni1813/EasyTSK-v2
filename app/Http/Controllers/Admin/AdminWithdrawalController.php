<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminWithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $search = trim($request->query('search', ''));

        $query = Withdrawal::with('user')->latest();

        if (in_array($status, ['pending', 'paid', 'rejected'])) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', (int)$search);
                }
                $q->orWhere('account_details', 'like', "%{$search}%")
                  ->orWhere('payment_method', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      if (is_numeric($search)) {
                          $u->where('id', (int)$search);
                      }
                      $u->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $withdrawals = $query->paginate(20)->withQueryString();

        $counts = [
            'all' => Withdrawal::count(),
            'pending' => Withdrawal::where('status', 'pending')->count(),
            'paid' => Withdrawal::where('status', 'paid')->count(),
            'rejected' => Withdrawal::where('status', 'rejected')->count(),
        ];

        return Inertia::render('Admin/Withdrawals/Index', [
            'withdrawals' => $withdrawals,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'counts' => $counts,
        ]);
    }

    public function approve(Request $request, Withdrawal $withdrawal)
    {
        $request->validate([
            'transaction_id' => 'required|string',
        ]);

        if ($withdrawal->status === 'pending') {
            $success = DB::transaction(function () use ($withdrawal, $request) {
                $lockedWithdrawal = Withdrawal::where('id', $withdrawal->id)->lockForUpdate()->first();
                if ($lockedWithdrawal->status === 'pending') {
                    $lockedWithdrawal->update([
                        'status' => 'paid',
                        'transaction_id' => $request->transaction_id,
                    ]);
                    
                    if ($lockedWithdrawal->user) {
                        \App\Models\Notification::send(
                            $lockedWithdrawal->user,
                            'Withdrawal Paid! ✅',
                            "Your payout request of {$lockedWithdrawal->amount_bdt} BDT via {$lockedWithdrawal->payment_method} has been processed. Trx ID: {$request->transaction_id}",
                            'success',
                            '/withdraw-history',
                            true
                        );
                    }
                    
                    return true;
                }
                return false;
            });
            
            if (!$success) {
                return back()->with('error', 'Withdrawal is no longer pending.');
            }

            // Send Telegram Withdrawal Success Alert
            \App\Services\TelegramService::sendSuccessWithdrawalAlert($withdrawal->fresh());
            
            return back()->with('success', 'Withdrawal marked as Paid.');
        }

        return back()->with('error', 'Withdrawal is not pending.');
    }

    public function reject(Request $request, Withdrawal $withdrawal)
    {
        $request->validate([
            'admin_note' => 'required|string',
        ]);

        if ($withdrawal->status === 'pending') {
            $success = DB::transaction(function () use ($withdrawal, $request) {
                $lockedWithdrawal = Withdrawal::where('id', $withdrawal->id)->lockForUpdate()->first();
                if ($lockedWithdrawal->status === 'pending') {
                    $user = $lockedWithdrawal->user;
                    $refundAmount = (float) $lockedWithdrawal->amount_coins + (float) $lockedWithdrawal->charge_coins;
                    $user->increment('main_balance', $refundAmount);

                    \App\Models\Transaction::log($user, 'credit', $refundAmount, "Refund for Rejected Withdrawal (#{$lockedWithdrawal->id})", 'withdrawal_refund', (string)$lockedWithdrawal->id);
                    \App\Models\Notification::send($user, 'Withdrawal Rejected ❌', "Your payout request of {$lockedWithdrawal->amount_bdt} BDT was rejected. Reason: {$request->admin_note}. Points refunded: {$refundAmount}", 'danger', '/withdraw-history');

                    $lockedWithdrawal->update([
                        'status' => 'rejected',
                        'admin_note' => $request->admin_note,
                        'rejection_reason' => $request->admin_note,
                    ]);
                    return true;
                }
                return false;
            });
            
            if (!$success) {
                return back()->with('error', 'Withdrawal is no longer pending.');
            }
            
            return back()->with('success', 'Withdrawal rejected and points refunded to user main balance.');
        }

        return back()->with('error', 'Withdrawal is not pending.');
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:withdrawals,id',
        ]);

        $approvedWithdrawals = [];
        DB::transaction(function () use ($request, &$approvedWithdrawals) {
            $pendingList = Withdrawal::whereIn('id', $request->ids)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->get();

            foreach ($pendingList as $w) {
                $w->update(['status' => 'paid']);
                $approvedWithdrawals[] = $w;
            }
        });

        foreach ($approvedWithdrawals as $w) {
            \App\Services\TelegramService::sendSuccessWithdrawalAlert($w);
        }

        return back()->with('success', count($request->ids) . ' withdrawals marked as Paid.');
    }

    public function exportCsv(Request $request)
    {
        $status = $request->query('status', 'all');
        $method = $request->query('method', 'all');
        $dateRange = $request->query('date_range', 'all');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $search = trim($request->query('search', ''));

        $query = Withdrawal::with('user')->latest();

        if (in_array($status, ['pending', 'paid', 'rejected'])) {
            $query->where('status', $status);
        }

        if ($method && $method !== 'all') {
            $query->where('payment_method', 'like', "%{$method}%");
        }

        if ($dateRange === 'today') {
            $query->whereDate('created_at', now()->today());
        } elseif ($dateRange === '7days') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($dateRange === '30days') {
            $query->where('created_at', '>=', now()->subDays(30));
        } elseif ($dateRange === 'custom' && $startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('account_details', 'like', "%{$search}%")
                  ->orWhere('payment_method', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $withdrawals = $query->get();

        $csvHeader = ['ID', 'User Name', 'Email', 'Phone', 'Payment Method', 'Account Details', 'Amount Coins', 'Amount BDT', 'Status', 'Transaction ID', 'Admin Note', 'Created At'];

        $callback = function () use ($withdrawals, $csvHeader) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $csvHeader);

            foreach ($withdrawals as $w) {
                fputcsv($file, [
                    $w->id,
                    $w->user ? $w->user->name : 'N/A',
                    $w->user ? $w->user->email : 'N/A',
                    $w->user ? $w->user->phone : 'N/A',
                    $w->payment_method,
                    $w->account_details,
                    $w->amount_coins,
                    $w->amount_bdt,
                    $w->status,
                    $w->transaction_id ?? '',
                    $w->admin_note ?? '',
                    $w->created_at,
                ]);
            }
            fclose($file);
        };

        $filename = "withdrawals_backup_" . date('Y-m-d_H-i') . ".csv";

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function destroy(Withdrawal $withdrawal)
    {
        $withdrawal->delete();

        return back()->with('success', 'Withdrawal history record deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:withdrawals,id',
        ]);

        $count = count($request->ids);
        Withdrawal::whereIn('id', $request->ids)->delete();

        return back()->with('success', "{$count} withdrawal records deleted successfully.");
    }

    public function cleanup(Request $request)
    {
        $request->validate([
            'status' => 'required|in:paid,rejected,all_completed,all',
            'days' => 'nullable|integer|min:0',
        ]);

        $query = Withdrawal::query();

        if ($request->status === 'paid') {
            $query->where('status', 'paid');
        } elseif ($request->status === 'rejected') {
            $query->where('status', 'rejected');
        } elseif ($request->status === 'all_completed') {
            $query->whereIn('status', ['paid', 'rejected']);
        } elseif ($request->status === 'all') {
            // Delete all statuses
        }

        if ($request->filled('days') && (int) $request->days > 0) {
            $query->where('created_at', '<', now()->subDays((int) $request->days));
        }

        $count = $query->delete();

        return back()->with('success', "{$count} withdrawal history records cleaned up successfully.");
    }
}
