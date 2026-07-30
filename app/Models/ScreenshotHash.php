<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScreenshotHash extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_task_id',
        'image_hash',
        'file_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userTask()
    {
        return $this->belongsTo(UserTask::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($hash) {
            if ($hash->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($hash->file_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($hash->file_path);
            }
        });
    }
}
