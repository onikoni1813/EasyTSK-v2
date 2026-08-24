<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'task_id',
        'campaign_id',
        'status',
        'submitted_data',
        'ip_address',
        'admin_note',
    ];

    protected $casts = [
        'submitted_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function screenshotHashes()
    {
        return $this->hasMany(ScreenshotHash::class);
    }
}
