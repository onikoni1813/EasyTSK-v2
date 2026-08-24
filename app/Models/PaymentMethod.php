<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'currency',
        'currency_symbol',
        'min_points',
        'conversion_rate',
        'fixed_charge',
        'charge_percent',
        'account_placeholder',
        'instructions',
        'icon',
        'is_active',
        'order',
    ];

    protected $casts = [
        'min_points'      => 'integer',
        'conversion_rate' => 'float',
        'fixed_charge'    => 'float',
        'charge_percent'  => 'float',
        'is_active'       => 'boolean',
        'order'           => 'integer',
    ];

    /**
     * Scope for only active payment methods.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
