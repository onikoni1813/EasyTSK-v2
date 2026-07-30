<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offerwall;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminOfferwallController extends Controller
{
    public function index()
    {
        $offerwalls = Offerwall::orderBy('order')->get();
        return Inertia::render('Admin/Offerwalls/Index', [
            'offerwalls' => $offerwalls,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'iframe_url_pattern' => 'required|string',
            'reward_ratio' => 'required|numeric|min:0.01',
            'secret_key' => 'nullable|string',
            'image_url' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'boolean',
            'param_user_id' => 'nullable|string',
            'param_amount' => 'nullable|string',
            'param_transaction_id' => 'nullable|string',
            'param_status' => 'nullable|string',
            'param_secret_key' => 'nullable|string',
            'status_chargeback_value' => 'nullable|string',
            'allowed_ips' => 'nullable|string',
        ]);

        Offerwall::create($request->all());

        return back()->with('success', 'Offerwall created successfully!');
    }

    public function update(Request $request, Offerwall $offerwall)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'iframe_url_pattern' => 'required|string',
            'reward_ratio' => 'required|numeric|min:0.01',
            'secret_key' => 'nullable|string',
            'image_url' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'boolean',
            'param_user_id' => 'nullable|string',
            'param_amount' => 'nullable|string',
            'param_transaction_id' => 'nullable|string',
            'param_status' => 'nullable|string',
            'param_secret_key' => 'nullable|string',
            'status_chargeback_value' => 'nullable|string',
            'allowed_ips' => 'nullable|string',
        ]);

        $offerwall->update($request->all());

        return back()->with('success', 'Offerwall updated successfully!');
    }

    public function destroy(Offerwall $offerwall)
    {
        $offerwall->delete();
        return back()->with('success', 'Offerwall deleted successfully!');
    }
}
