<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'is_popup',
        'action_url',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'is_popup' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function send(User|int $user, string $title, string $message, string $type = 'info', ?string $actionUrl = null, bool $isPopup = false): self
    {
        $userId = $user instanceof User ? $user->id : $user;

        return self::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_popup' => $isPopup,
            'action_url' => $actionUrl,
        ]);
    }

    public static function sendToAll(string $title, string $message, string $type = 'info', ?string $actionUrl = null, bool $isPopup = false): int
    {
        $count = 0;
        User::select('id')->chunk(500, function ($users) use ($title, $message, $type, $actionUrl, $isPopup, &$count) {
            $now = now();
            $data = [];
            foreach ($users as $u) {
                $data[] = [
                    'user_id' => $u->id,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'is_popup' => $isPopup ? 1 : 0,
                    'action_url' => $actionUrl,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            self::insert($data);
            $count += count($data);
        });

        return $count;
    }

    public static function sendToLevel(int $level, string $title, string $message, string $type = 'info', ?string $actionUrl = null, bool $isPopup = false): int
    {
        $count = 0;
        User::where('level', $level)->select('id')->chunk(500, function ($users) use ($title, $message, $type, $actionUrl, $isPopup, &$count) {
            $now = now();
            $data = [];
            foreach ($users as $u) {
                $data[] = [
                    'user_id' => $u->id,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'is_popup' => $isPopup ? 1 : 0,
                    'action_url' => $actionUrl,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            self::insert($data);
            $count += count($data);
        });

        return $count;
    }
}
