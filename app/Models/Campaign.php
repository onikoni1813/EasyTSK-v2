<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'user_id',
        'campaign_service_id',
        'title',
        'description',
        'target_url',
        'type',
        'action',
        'proof_type',
        'proof_instruction',
        'secret_code',
        'budget_points',
        'cost_per_click',
        'total_clicks',
        'target_clicks',
        'status',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'budget_points'  => 'decimal:2',
            'cost_per_click' => 'decimal:2',
            'total_clicks'   => 'integer',
            'target_clicks'  => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(CampaignService::class, 'campaign_service_id');
    }

    public function clicks()
    {
        return $this->hasMany(CampaignClick::class);
    }

    public function userTasks()
    {
        return $this->hasMany(UserTask::class);
    }

    public function submissions()
    {
        return $this->hasMany(UserTask::class);
    }

    public function progressPercent(): float
    {
        if ($this->target_clicks === 0) return 0;
        return min(100, round(($this->total_clicks / $this->target_clicks) * 100, 1));
    }
}
