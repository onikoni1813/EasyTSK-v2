<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ticket_number',
        'category',
        'subject',
        'status',
        'priority',
        'last_reply_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(SupportTicketMessage::class, 'ticket_id')->latestOfMany();
    }

    public static function generateTicketNumber(): string
    {
        do {
            $number = 'ST-' . random_int(10000, 99999);
        } while (self::where('ticket_number', $number)->exists());

        return $number;
    }
}
