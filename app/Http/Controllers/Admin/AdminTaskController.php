<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdminTaskController extends Controller
{
    public function index()
    {
        $tasks = Task::latest()->get()->map(fn(Task $t) => [
            'id'                 => $t->id,
            'title'              => $t->title,
            'description'        => $t->description,
            'type'               => $t->type,
            'provider_name'      => $t->provider_name,
            'target_url'         => $t->target_url,
            'secret_code'        => $t->secret_code,
            'proof_requirements' => $t->proof_requirements ?? [],
            'reward_coins'       => (float) $t->reward_coins,
            'reward_xp'          => (int) $t->reward_xp,
            'cooldown_hours'     => (int) $t->cooldown_hours,
            'status'             => $t->status,
            'submissions_count'  => $t->userTasks()->count(),
            'image_url'          => $t->image_url,
            'created_at'         => $t->created_at->diffForHumans(),
        ]);

        $pendingReviewsCount = \App\Models\UserTask::where('status', 'pending')->count();

        return Inertia::render('Admin/Tasks/Index', [
            'tasks' => $tasks,
            'pendingReviewsCount' => $pendingReviewsCount,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'type'               => 'required|in:shortlink,secret_code,social,user_ad',
            'provider_name'      => 'nullable|string|max:255',
            'target_url'         => 'required|url',
            'secret_code'        => 'required_if:type,secret_code|nullable|string|max:255',
            'reward_coins'       => 'required|numeric|min:1|max:10000',
            'reward_xp'          => 'required|integer|min:1|max:5000',
            'cooldown_hours'     => 'required|integer|min:0|max:720',
            'description'        => 'nullable|string|max:5000',
            'proof_requirements' => 'nullable|array|max:5',
            'proof_requirements.*.id' => 'required|string|max:50',
            'proof_requirements.*.type' => 'required|in:text,image',
            'proof_requirements.*.label' => 'required|string|max:255',
            'proof_requirements.*.is_required' => 'nullable|boolean',
            'image'              => 'nullable|image|max:5120',
        ]);

        if (isset($validated['description'])) {
            // Strip dangerous tags (like <script>, <iframe>) but allow basic formatting and images
            $validated['description'] = strip_tags($validated['description'], '<p><br><b><strong><i><em><u><ul><ol><li><a><span><h1><h2><h3><h4><h5><h6><blockquote><img>');
        }

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('tasks', 'public');
        }

        Task::create($validated);

        return back()->with('success', '✅ নতুন টাস্ক তৈরি হয়েছে!');
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'type'               => 'required|in:shortlink,secret_code,social,user_ad',
            'provider_name'      => 'nullable|string|max:255',
            'target_url'         => 'required|url',
            'secret_code'        => 'required_if:type,secret_code|nullable|string|max:255',
            'reward_coins'       => 'required|numeric|min:1|max:10000',
            'reward_xp'          => 'required|integer|min:1|max:5000',
            'cooldown_hours'     => 'required|integer|min:0|max:720',
            'description'        => 'nullable|string|max:5000',
            'proof_requirements' => 'nullable|array|max:5',
            'proof_requirements.*.id' => 'required|string|max:50',
            'proof_requirements.*.type' => 'required|in:text,image',
            'proof_requirements.*.label' => 'required|string|max:255',
            'proof_requirements.*.is_required' => 'nullable|boolean',
            'image'              => 'nullable|image|max:5120',
        ]);

        if (isset($validated['description'])) {
            // Strip dangerous tags (like <script>, <iframe>) but allow basic formatting and images
            $validated['description'] = strip_tags($validated['description'], '<p><br><b><strong><i><em><u><ul><ol><li><a><span><h1><h2><h3><h4><h5><h6><blockquote><img>');
        }

        if ($request->hasFile('image')) {
            if ($task->image_path) {
                Storage::disk('public')->delete($task->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('tasks', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($task->image_path) {
                Storage::disk('public')->delete($task->image_path);
            }
            $validated['image_path'] = null;
        }

        $task->update($validated);

        return back()->with('success', '✅ টাস্ক আপডেট হয়েছে!');
    }

    public function toggleStatus(Task $task)
    {
        $newStatus = $task->status === 'active' ? 'inactive' : 'active';
        $task->update(['status' => $newStatus]);

        return back()->with('success', 'টাস্ক ' . ($newStatus === 'active' ? 'চালু' : 'বন্ধ') . ' করা হয়েছে।');
    }

    public function destroy(Task $task)
    {
        // Clean up task's own image
        if ($task->image_path) {
            Storage::disk('public')->delete($task->image_path);
        }

        // Clean up all physical screenshot files submitted by users for this task to prevent orphaned files
        $hashes = \App\Models\ScreenshotHash::whereHas('userTask', function ($q) use ($task) {
            $q->where('task_id', $task->id);
        })->get();

        foreach ($hashes as $hash) {
            if ($hash->file_path) {
                Storage::disk('public')->delete($hash->file_path);
            }
        }

        // The database's ON DELETE CASCADE will automatically wipe the related user_tasks and screenshot_hashes records
        $task->delete();
        
        return back()->with('success', '🗑️ টাস্ক এবং এর সকল ডেটা সফলভাবে মুছে ফেলা হয়েছে!');
    }
}
