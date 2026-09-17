# MoneyRain Offerwall & Callback Integration

## 1. Overview
- **Provider Name**: MoneyRain
- **Documentation**: https://offerwall.moneyrain.top/api-docs.php
- **Integration**: Hosted link or iframe widget using `site` key and `external_uid={user_id}`

## 2. Callback Webhook Setup
Configure the Callback URL in MoneyRain Publisher Dashboard (**Publisher → Callbacks**):
```
https://yourdomain.com/postback/moneyrain
```

### Callback Request Details
- **Method**: HTTP POST
- **Content-Type**: `application/json`
- **Header**: `X-MoneyRain-Signature: sha256={HMAC_SHA256_HASH}`

### Example JSON Payload
```json
{
  "event": "reward.completed",
  "view_id": 98765,
  "external_uid": "123",
  "ad_type": "ptc",
  "reward_usdt": "0.00003000",
  "reward_currency_amount": "0.00150000",
  "reward_currency_name": "Coins",
  "reward_currency_per_usdt": "50.00000000",
  "advertiser_cost_usdt": "0.00005000",
  "status": "completed",
  "timestamp": 1782730000,
  "nonce": "random24hex"
}
```

## 3. Signature Formula
MoneyRain sends an HMAC-SHA256 hash in the `X-MoneyRain-Signature` header:
```php
$expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
hash_equals($expected, $request->header('X-MoneyRain-Signature'));
```

## 4. Expected Response
MoneyRain accepts HTTP 200/201/202/204 with:
```
OK
```

## 5. Admin Panel Settings in Easytsk v2
- **Name**: `MoneyRain`
- **Iframe URL Pattern**: `https://offerwall.moneyrain.top/s/YOUR_SITE_KEY?external_uid={user_id}`
- **Reward Ratio**: `1.00`
- **Secret Key**: `[Your MoneyRain Callback Secret]`
- **Param User ID**: `external_uid`
- **Param Transaction ID**: `view_id`
- **Param Amount**: `reward_currency_amount` (or `reward_usdt`)
- **Param Status**: `status`
- **Param Secret Key**: `signature`
- **Status Chargeback Value**: `reversed`
