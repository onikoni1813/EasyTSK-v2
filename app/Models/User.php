<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'google_id',
        'device_hash',
        'recovery_pin',
        'main_balance',
        'pending_balance',
        'locked_balance',
        'level',
        'xp_points',
        'ref_by',
        'referral_code',
        'role',
        'payment_method',
        'payment_number',
        'has_claimed_welcome_bonus',
        'welcome_bonus_amount',
        'last_withdrawal_at',
        'risk_score',
        'health',
        'health_depleted_at',
        'is_banned',
        'spin_available_at',
        'total_spins_used',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'recovery_pin',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'         => 'datetime',
            'password'                  => 'hashed',
            'main_balance'              => 'decimal:2',
            'pending_balance'           => 'decimal:2',
            'locked_balance'            => 'decimal:2',
            'level'                     => 'integer',
            'xp_points'                 => 'integer',
            'has_claimed_welcome_bonus' => 'boolean',
            'welcome_bonus_amount'      => 'decimal:2',
            'last_withdrawal_at'        => 'datetime',
            'risk_score'                => 'decimal:2',
            'health'                    => 'integer',
            'health_depleted_at'        => 'datetime',
            'is_banned'                 => 'boolean',
            'spin_available_at'         => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function userTasks()
    {
        return $this->hasMany(UserTask::class);
    }

    public function offerwallLogs()
    {
        return $this->hasMany(OfferwallLog::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function referralTrackings()
    {
        return $this->hasMany(ReferralTracking::class, 'referrer_id');
    }

    public function dailyStreak()
    {
        return $this->hasOne(DailyStreak::class);
    }

    public function wheelSpins()
    {
        return $this->hasMany(WheelSpin::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function promoCodeUses()
    {
        return $this->hasMany(PromoCodeUse::class);
    }

    // ─── Balance Helpers ──────────────────────────────────────────────────────

    /**
     * Accessor for balance attribute as an alias for main_balance
     */
    public function getBalanceAttribute(): float
    {
        return (float) ($this->attributes['main_balance'] ?? 0);
    }

    /**
     * Add reward points to main balance safely within DB transaction
     */
    public function addMainBalance(float $amount): void
    {
        DB::transaction(function () use ($amount) {
            $this->increment('main_balance', $amount);
        });
    }

    /**
     * Deduct points from main balance safely within DB transaction
     */
    public function deductMainBalance(float $amount): bool
    {
        return DB::transaction(function () use ($amount) {
            /** @var self $fresh */
            $fresh = self::where('id', $this->id)->lockForUpdate()->first();
            if ($fresh && $fresh->main_balance >= $amount) {
                $fresh->decrement('main_balance', $amount);
                $this->setAttribute('main_balance', $fresh->main_balance - $amount);
                return true;
            }
            return false;
        });
    }

    /**
     * Maximum health value a user can hold/regenerate up to.
     */
    public const MAX_HEALTH = 100;

    /**
     * Deduct health points and ensure it doesn't go below zero.
     * Records the moment health first hits zero, so the 24h submission
     * gate (see isHealthGateActive()) has a fixed expiry to count down from.
     */
    public function deductHealth(int $amount): void
    {
        DB::transaction(function () use ($amount) {
            $lockedUser = self::where('id', $this->id)->lockForUpdate()->first();
            $newHealth = max(0, $lockedUser->health - $amount);
            $attributes = ['health' => $newHealth];

            if ($newHealth <= 0 && $lockedUser->health_depleted_at === null) {
                $attributes['health_depleted_at'] = now();
            }

            $lockedUser->update($attributes);
        });
        $this->refresh();
    }

    /**
     * Add health points, capped at MAX_HEALTH. Clears the depletion gate
     * once health recovers above zero.
     */
    public function addHealth(int $amount): void
    {
        DB::transaction(function () use ($amount) {
            $lockedUser = self::where('id', $this->id)->lockForUpdate()->first();
            $newHealth = min(self::MAX_HEALTH, $lockedUser->health + $amount);
            $attributes = ['health' => $newHealth];

            if ($newHealth > 0 && $lockedUser->health_depleted_at !== null) {
                $attributes['health_depleted_at'] = null;
            }

            $lockedUser->update($attributes);
        });
        $this->refresh();
    }

    /**
     * True if the user is currently blocked from submitting new proof/secret-code
     * tasks because their health hit zero within the last 24 hours.
     */
    public function isHealthGateActive(): bool
    {
        if ($this->health > 0 || !$this->health_depleted_at) {
            return false;
        }

        return now()->lessThan($this->health_depleted_at->addHours(24));
    }

    // ─── Gamification ────────────────────────────────────────────────────────

    /**
     * Add XP and auto-upgrade level
     */
    public function addXp(int $xp): void
    {
        // Delegate to GamificationService but skip streak update,
        // preserving original behavior where User::addXp doesn't touch streaks.
        app(\App\Services\GamificationService::class)->awardXp($this, $xp, false);
    }

    /**
     * Check if this user can spin the wheel right now
     */
    public function canSpin(): bool
    {
        if (!$this->spin_available_at) return false;
        return now()->greaterThanOrEqualTo($this->spin_available_at);
    }

    // ─── Referral ─────────────────────────────────────────────────────────────

    /**
     * Generate a unique referral code for the user
     */
    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('referral_code', $code)->exists());
        return $code;
    }

    // ─── Role ─────────────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
