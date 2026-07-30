<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Offerwall;

class DemoOfferwallSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Offerwall::truncate();

        Offerwall::create([
            'name' => 'Timewall',
            'description' => 'Complete tasks and surveys to earn rewards. Timewall supports chargebacks and custom hashing.',
            'image_url' => 'https://timewall.io/images/logo.png',
            'iframe_url_pattern' => 'https://timewall.io/offerwall?user={user_id}',
            'secret_key' => 'demosecret123',
            'reward_ratio' => 1.00,
            'status' => true,
            'param_user_id' => 'userID',
            'param_amount' => 'currencyAmount',
            'param_transaction_id' => 'transactionID',
            'param_status' => 'type',
            'param_secret_key' => 'hash',
            'status_chargeback_value' => 'chargeback',
        ]);

        Offerwall::create([
            'name' => 'Notik',
            'description' => 'High paying offers and app installs. Postbacks are verified via a unique SHA1 hash.',
            'image_url' => 'https://notik.me/assets/img/logo.png',
            'iframe_url_pattern' => 'https://notik.me/offerwall?pub_id=123&user_id={user_id}',
            'secret_key' => 'notiksecret456',
            'reward_ratio' => 0.85,
            'status' => true,
            'param_user_id' => 'user_id',
            'param_amount' => 'payout',
            'param_transaction_id' => 'txn_id',
            'param_status' => 'status',
            'param_secret_key' => 'hash',
            'status_chargeback_value' => '2',
        ]);

        Offerwall::create([
            'name' => 'AdMaven',
            'description' => 'Content locker and shortlinks integration. Easy way to monetize traffic.',
            'image_url' => 'https://ad-maven.com/wp-content/uploads/2021/08/Admaven-Logo.svg',
            'iframe_url_pattern' => 'https://publishers.ad-maven.com/locker?uid={user_id}',
            'secret_key' => null,
            'reward_ratio' => 1.50,
            'status' => true,
            'param_user_id' => 'subId',
            'param_amount' => 'reward',
            'param_transaction_id' => 'transId',
            'param_status' => 'status',
            'param_secret_key' => 'secure',
            'status_chargeback_value' => 'reversed',
        ]);
    }
}
