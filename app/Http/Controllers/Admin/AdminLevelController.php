<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Level;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminLevelController extends Controller
{
    public function index()
    {
        $levels = Level::orderBy('level_number', 'asc')->get();
        return Inertia::render('Admin/Levels/Index', [
            'levels' => $levels
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'level_number' => 'required|integer|min:1|unique:levels,level_number',
            'xp_required'  => 'required|integer|min:0',
            'bonus_reward' => 'required|numeric|min:0',
        ]);

        Level::create($validated);

        return back()->with('success', 'Level added successfully.');
    }

    public function update(Request $request, Level $level)
    {
        $validated = $request->validate([
            'level_number' => 'required|integer|min:1|unique:levels,level_number,' . $level->id,
            'xp_required'  => 'required|integer|min:0',
            'bonus_reward' => 'required|numeric|min:0',
        ]);

        $level->update($validated);

        return back()->with('success', 'Level updated successfully.');
    }

    public function destroy(Level $level)
    {
        if ($level->level_number === 1) {
            return back()->with('error', 'Cannot delete Level 1.');
        }

        $level->delete();

        return back()->with('success', 'Level deleted successfully.');
    }
}
