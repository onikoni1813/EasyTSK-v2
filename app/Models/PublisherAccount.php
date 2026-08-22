<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublisherAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'network',
        'account_name',
        'account_id_or_email',
        'payout_method',
        'min_payout_amount',
        'status',
    ];

    protected $casts = [
        'min_payout_amount' => 'decimal:2',
    ];

    public function revenueLogs(): HasMany
    {
        return $this->hasMany(SiteRevenueLog::class, 'publisher_account_id');
    }
}
