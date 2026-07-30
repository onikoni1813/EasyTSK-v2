<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    /**
     * Send Telegram notification when a user submits a new withdrawal request.
     */
    public static function sendAdminWithdrawalAlert(Withdrawal $withdrawal): bool
    {
        try {
            $enabled = AppSetting::getByKey('telegram_admin_bot_enabled', 'false') === 'true';
            $botToken = AppSetting::getByKey('telegram_admin_bot_token', '');
            $chatId = AppSetting::getByKey('telegram_admin_chat_id', '');

            if (!$enabled || empty($botToken) || empty($chatId)) {
                return false;
            }

            $user = $withdrawal->user;
            $userName = $user ? $user->name : 'N/A';
            $userEmail = $user ? $user->email : 'N/A';
            $userPhone = $user ? ($user->phone ?: 'N/A') : 'N/A';
            $userId = $user ? $user->id : 'N/A';

            $totalCoins = number_format((float)$withdrawal->amount_coins + (float)$withdrawal->charge_coins, 2);
            $chargeCoins = number_format((float)$withdrawal->charge_coins, 2);
            $payableBdt = number_format((float)$withdrawal->amount_bdt, 2);
            $accountNumber = htmlspecialchars($withdrawal->account_details);

            $message = "<b>📥 NEW WITHDRAWAL REQUEST (#{$withdrawal->id})</b>\n\n";
            $message .= "<b>👤 User:</b> {$userName} (ID: #{$userId})\n";
            $message .= "<b>📧 Email:</b> {$userEmail}\n";
            $message .= "<b>📱 Phone:</b> {$userPhone}\n\n";
            $message .= "<b>🪙 Points Requested:</b> {$totalCoins} Pts\n";
            $message .= "<b>💸 Charge Deducted:</b> {$chargeCoins} Pts\n";
            $message .= "<b>💵 Net Payable BDT:</b> <b>{$payableBdt} BDT</b>\n\n";
            $message .= "<b>🏦 Payment Method:</b> {$withdrawal->payment_method}\n";
            $message .= "<b>📋 Payment Number (Tap to copy):</b>\n";
            $message .= "<code>{$accountNumber}</code>\n\n";
            $message .= "<b>⏰ Time:</b> " . now()->format('d M Y, h:i A');

            return static::sendMessage($botToken, $chatId, $message);
        } catch (\Throwable $e) {
            Log::error('Telegram Admin Withdrawal Alert Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send Telegram notification when a withdrawal request is approved/paid.
     */
    public static function sendSuccessWithdrawalAlert(Withdrawal $withdrawal): bool
    {
        try {
            $enabled = AppSetting::getByKey('telegram_success_bot_enabled', 'false') === 'true';
            $botToken = AppSetting::getByKey('telegram_success_bot_token', '');
            $chatId = AppSetting::getByKey('telegram_success_chat_id', '');

            if (!$enabled || empty($botToken) || empty($chatId)) {
                return false;
            }

            $user = $withdrawal->user;
            $userName = $user ? $user->name : 'User';
            $maskedName = static::maskName($userName);
            $payableBdt = number_format((float)$withdrawal->amount_bdt, 2);
            $trxId = htmlspecialchars($withdrawal->transaction_id ?: 'N/A');

            $message = "<b>✅ WITHDRAWAL SUCCESSFUL!</b>\n\n";
            $message .= "<b>🎉 User:</b> {$maskedName}\n";
            $message .= "<b>💵 Amount Paid:</b> <b>{$payableBdt} BDT</b>\n";
            $message .= "<b>🏦 Payment Method:</b> {$withdrawal->payment_method}\n";
            $message .= "<b>🆔 Trx ID:</b> <code>{$trxId}</code>\n\n";
            $message .= "<b>⏰ Date:</b> " . now()->format('d M Y, h:i A') . "\n";
            $message .= "⚡ <i>Earn money daily by completing tasks on EasyTsk!</i>";

            return static::sendMessage($botToken, $chatId, $message);
        } catch (\Throwable $e) {
            Log::error('Telegram Success Withdrawal Alert Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a raw message using Telegram Bot API.
     */
    public static function sendMessage(string $botToken, string $chatId, string $message): bool
    {
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $response = Http::timeout(8)->post($url, [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);

        return $response->successful();
    }

    /**
     * Mask user name for public Telegram success channel privacy (e.g. John Doe -> John D***).
     */
    private static function maskName(string $name): string
    {
        $parts = explode(' ', trim($name));
        if (count($parts) > 1) {
            $firstName = $parts[0];
            $lastName = $parts[count($parts) - 1];
            return $firstName . ' ' . mb_substr($lastName, 0, 1) . '***';
        }
        if (mb_strlen($name) > 3) {
            return mb_substr($name, 0, 3) . '***';
        }
        return $name;
    }
}
