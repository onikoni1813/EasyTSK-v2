<?php
use App\Models\User;
use App\Models\Task;
use App\Models\UserTask;
use App\Models\ScreenshotHash;

$users = User::inRandomOrder()->take(3)->get();
$tasks = Task::inRandomOrder()->take(2)->get();

if ($users->isEmpty() || $tasks->isEmpty()) {
    echo "Need at least 1 user and 1 task.";
    exit;
}

// Ensure the storage directory exists
$dummyImagePath = storage_path('app/public/proofs');
if (!file_exists($dummyImagePath)) {
    mkdir($dummyImagePath, 0777, true);
}

// Create a dummy image file (1x1 transparent png)
$dummyImageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
$dummyFileName1 = 'proofs/demo_proof_' . time() . '_1.png';
$dummyFileName2 = 'proofs/demo_proof_' . time() . '_2.png';
file_put_contents(storage_path('app/public/' . $dummyFileName1), $dummyImageContent);
file_put_contents(storage_path('app/public/' . $dummyFileName2), $dummyImageContent);


foreach ($users as $index => $user) {
    $task = $tasks->random();
    
    // Dynamic proof
    $submittedData = [
        'req_1' => [
            'type' => 'text',
            'label' => 'Username used',
            'value' => 'demo_user_' . rand(100, 999)
        ],
        'req_2' => [
            'type' => 'image',
            'label' => 'Screenshot of completion',
            'value' => ''
        ]
    ];

    if ($index == 2) {
        // Legacy proof for one
        $submittedData = [
            'text_proof' => 'I completed the task as requested. Username: demo_legacy',
            'screenshot_hash' => 'dummy_hash_legacy'
        ];
    }

    $userTask = UserTask::create([
        'user_id' => $user->id,
        'task_id' => $task->id,
        'status' => 'pending',
        'submitted_data' => $submittedData,
        'ip_address' => '127.0.0.1'
    ]);

    // Attach screenshots
    ScreenshotHash::create([
        'user_task_id' => $userTask->id,
        'user_id' => $user->id,
        'image_hash' => md5(time() . rand()),
        'file_path' => $index % 2 == 0 ? $dummyFileName1 : $dummyFileName2
    ]);

    echo "Created demo review for user {$user->name} on task {$task->title}\n";
}

echo "Demo reviews seeded successfully!";
