<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignService extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform',
        'action',
        'clicker_reward',
        'creator_cost',
        'requires_proof',
        'is_active',
    ];

    protected $casts = [
        'clicker_reward' => 'decimal:2',
        'creator_cost' => 'decimal:2',
        'requires_proof' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
}
