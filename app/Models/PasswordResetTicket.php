<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PasswordResetTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'ticket_code',
        'message',
        'status',
        'reset_code',
        'admin_note',
        'ip_address',
        'device_hash',
        'approved_at',
        'completed_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function generateTicketCode(): string
    {
        do {
            $code = 'PR-' . strtoupper(Str::random(6));
        } while (self::where('ticket_code', $code)->exists());

        return $code;
    }

    public static function generateResetCode(): string
    {
        return (string) random_int(100000, 999999);
    }
}
