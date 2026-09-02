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
        'user_id' => 'integer',
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

        $payload = [
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'action_url' => $actionUrl,
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('notifications', 'is_popup')) {
            $payload['is_popup'] = $isPopup;
        }

        return self::create($payload);
    }

    public static function sendToAll(string $title, string $message, string $type = 'info', ?string $actionUrl = null, bool $isPopup = false): int
    {
        $hasPopupCol = \Illuminate\Support\Facades\Schema::hasColumn('notifications', 'is_popup');
        $count = 0;
        User::select('id')->chunk(500, function ($users) use ($title, $message, $type, $actionUrl, $isPopup, $hasPopupCol, &$count) {
            $now = now();
            $data = [];
            foreach ($users as $u) {
                $row = [
                    'user_id' => $u->id,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'action_url' => $actionUrl,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if ($hasPopupCol) {
                    $row['is_popup'] = $isPopup ? 1 : 0;
                }
                $data[] = $row;
            }
            self::insert($data);
            $count += count($data);
        });

        return $count;
    }

    public static function sendToLevel(int $level, string $title, string $message, string $type = 'info', ?string $actionUrl = null, bool $isPopup = false): int
    {
        $hasPopupCol = \Illuminate\Support\Facades\Schema::hasColumn('notifications', 'is_popup');
        $count = 0;
        User::where('level', $level)->select('id')->chunk(500, function ($users) use ($title, $message, $type, $actionUrl, $isPopup, $hasPopupCol, &$count) {
            $now = now();
            $data = [];
            foreach ($users as $u) {
                $row = [
                    'user_id' => $u->id,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'action_url' => $actionUrl,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if ($hasPopupCol) {
                    $row['is_popup'] = $isPopup ? 1 : 0;
                }
                $data[] = $row;
            }
            self::insert($data);
            $count += count($data);
        });

        return $count;
    }
}
