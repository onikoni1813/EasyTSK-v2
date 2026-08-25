<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminPaymentMethodController extends Controller
{
    public function index()
    {
        $methods = PaymentMethod::orderBy('order')->orderBy('id')->get()->map(function (PaymentMethod $m) {
            return [
                'id'                  => $m->id,
                'name'                => $m->name,
                'code'                => $m->code,
                'type'                => $m->type,
                'currency'            => $m->currency ?? 'BDT',
                'currency_symbol'     => $m->currency_symbol ?? '৳',
                'min_points'          => $m->min_points,
                'conversion_rate'     => $m->conversion_rate !== null ? (float) $m->conversion_rate : null,
                'fixed_charge'        => (float) $m->fixed_charge,
                'charge_percent'      => (float) $m->charge_percent,
                'account_placeholder' => $m->account_placeholder,
                'instructions'        => $m->instructions,
                'icon'                => $m->icon,
                'is_active'           => (bool) $m->is_active,
                'order'               => (int) $m->order,
                'withdrawals_count'   => Withdrawal::where('payment_method', $m->code)->orWhere('payment_method', $m->name)->count(),
                'created_at'          => $m->created_at ? $m->created_at->format('M d, Y') : null,
            ];
        });

        $stats = [
            'total'    => PaymentMethod::count(),
            'active'   => PaymentMethod::where('is_active', true)->count(),
            'inactive' => PaymentMethod::where('is_active', false)->count(),
        ];

        return Inertia::render('Admin/PaymentMethods/Index', [
            'methods' => $methods,
            'stats'   => $stats,
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->filled('code') && $request->filled('name')) {
            $request->merge(['code' => \Illuminate\Support\Str::slug($request->name, '_')]);
        }

        $validated = $request->validate([
            'name'                => 'required|string|max:100',
            'code'                => 'required|string|max:50|unique:payment_methods,code',
            'type'                => 'required|in:mobile_banking,recharge,crypto,bank,other',
            'currency'            => 'required|string|max:10',
            'currency_symbol'     => 'required|string|max:10',
            'min_points'          => 'nullable|integer|min:0|max:10000000',
            'conversion_rate'     => 'nullable|numeric|min:0.01|max:1000000',
            'fixed_charge'        => 'nullable|numeric|min:0|max:10000',
            'charge_percent'      => 'nullable|numeric|min:0|max:100',
            'account_placeholder' => 'required|string|max:100',
            'instructions'        => 'nullable|string|max:500',
            'icon'                => 'nullable|string|max:50',
            'is_active'           => 'boolean',
            'order'               => 'integer|min:0|max:999',
        ]);

        $validated['fixed_charge'] = $validated['fixed_charge'] ?? 0;
        $validated['charge_percent'] = $validated['charge_percent'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['order'] = $validated['order'] ?? 0;

        PaymentMethod::create($validated);

        return back()->with('success', "Payment method '{$validated['name']}' created successfully!");
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        if (!$request->filled('code') && $request->filled('name')) {
            $request->merge(['code' => \Illuminate\Support\Str::slug($request->name, '_')]);
        }

        $validated = $request->validate([
            'name'                => 'required|string|max:100',
            'code'                => 'required|string|max:50|unique:payment_methods,code,' . $paymentMethod->id,
            'type'                => 'required|in:mobile_banking,recharge,crypto,bank,other',
            'currency'            => 'required|string|max:10',
            'currency_symbol'     => 'required|string|max:10',
            'min_points'          => 'nullable|integer|min:0|max:10000000',
            'conversion_rate'     => 'nullable|numeric|min:0.01|max:1000000',
            'fixed_charge'        => 'nullable|numeric|min:0|max:10000',
            'charge_percent'      => 'nullable|numeric|min:0|max:100',
            'account_placeholder' => 'required|string|max:100',
            'instructions'        => 'nullable|string|max:500',
            'icon'                => 'nullable|string|max:50',
            'is_active'           => 'boolean',
            'order'               => 'integer|min:0|max:999',
        ]);

        $validated['fixed_charge'] = $validated['fixed_charge'] ?? 0;
        $validated['charge_percent'] = $validated['charge_percent'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);

        $paymentMethod->update($validated);

        return back()->with('success', "Payment method '{$paymentMethod->name}' updated successfully!");
    }

    public function toggle(PaymentMethod $paymentMethod)
    {
        $paymentMethod->update([
            'is_active' => !$paymentMethod->is_active,
        ]);

        $statusStr = $paymentMethod->is_active ? 'Enabled' : 'Disabled';
        return back()->with('success', "Payment method '{$paymentMethod->name}' is now {$statusStr}.");
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $hasPending = Withdrawal::where(function ($q) use ($paymentMethod) {
            $q->where('payment_method', $paymentMethod->code)
              ->orWhere('payment_method', $paymentMethod->name);
        })->where('status', 'pending')->exists();

        if ($hasPending) {
            return back()->withErrors(['message' => "Cannot delete '{$paymentMethod->name}' because there are pending withdrawal requests waiting for review. Please approve/reject pending requests or disable the method instead."]);
        }

        $name = $paymentMethod->name;
        $paymentMethod->delete();

        return back()->with('success', "Payment method '{$name}' deleted successfully.");
    }
}
