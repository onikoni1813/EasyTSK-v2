<?php

namespace App\Services;

use App\Models\ScreenshotHash;
use App\Models\UserTask;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class StorageSaverService
{
    /**
     * Hash screenshot and check for duplicates globally across the entire site
     * Returns array ['success' => bool, 'hash' => string, 'path' => string, 'message' => string]
     */
    public function processAndVerifyScreenshot(UploadedFile $file, int $userId, int $userTaskId = null): array
    {
        $hash = hash_file('sha256', $file->getRealPath());

        // Check if hash already exists in screenshot_hashes table
        $existing = ScreenshotHash::where('image_hash', $hash)->first();

        if ($existing) {
            return [
                'success' => false,
                'message' => 'Duplicate screenshot detected! This image has already been submitted on Easytsk V2.',
            ];
        }

        // Save file locally
        $filename = time() . '_' . $userId . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('proofs', $filename, 'public');

        // Record hash in database
        $screenshotHash = ScreenshotHash::create([
            'user_id' => $userId,
            'user_task_id' => $userTaskId,
            'image_hash' => $hash,
            'file_path' => $path,
        ]);

        return [
            'success' => true,
            'hash' => $hash,
            'path' => $path,
            'id' => $screenshotHash->id,
            'message' => 'Screenshot uploaded and validated successfully.',
        ];
    }

    /**
     * Auto-delete screenshots associated with a UserTask after review
     */
    public function deleteUserTaskScreenshots(UserTask $userTask): void
    {
        $hashes = ScreenshotHash::where('user_task_id', $userTask->id)->get();

        foreach ($hashes as $hash) {
            if ($hash->file_path && Storage::disk('public')->exists($hash->file_path)) {
                Storage::disk('public')->delete($hash->file_path);
            }
            // Keep the hash row or delete file path to save disk space while retaining duplicate prevention hash
            $hash->update(['file_path' => null]);
        }
    }

    /**
     * Daily Cron job cleanup for processed tasks screenshots
     */
    public function cleanupReviewedScreenshots(): int
    {
        $deletedCount = 0;
        $hashes = ScreenshotHash::whereNotNull('file_path')
            ->whereHas('userTask', function ($query) {
                $query->whereIn('status', ['approved', 'rejected'])
                      ->where('updated_at', '<=', now()->subHours(24));
            })->get();

        foreach ($hashes as $hash) {
            if (Storage::disk('public')->exists($hash->file_path)) {
                Storage::disk('public')->delete($hash->file_path);
                $deletedCount++;
            }
            $hash->update(['file_path' => null]);
        }

        return $deletedCount;
    }
}
