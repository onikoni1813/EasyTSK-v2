# Capsbit Media Offerwall & Postback Integration

## 1. Overview
- **Provider Name**: Capsbit
- **Offerwall Website**: https://dashboard.capsbit.com/dashboard.php
- **Offers API URL**: `https://api.capsbit.com/[SECRET_KEY]`
- **Offerwall iFrame URL**:
  `https://offerwall.capsbit.com/{YOUR_API_KEY}/{user_id}`

## 2. Postback Setup
Configure the Postback URL in Capsbit Dashboard: **Dashboard → Placements → Edit Placement → Postback URL**:

```
https://yourdomain.com/postback/capsbit?txid={transId}&uid={user_id}&payout={payout}&reward={reward}&offer_id={offer_id}&status={status}&country={country}&sig={signature}
```

### Supported Macros
| Macro | Description | Example |
| :--- | :--- | :--- |
| `{transId}` | Unique conversion ID | `CONV_001` |
| `{user_id}` | Your user ID | `123` |
| `{payout}` | USD payout credited to publisher | `0.75` |
| `{reward}` | Reward in virtual currency | `6000` |
| `{offer_id}`| ID of the completed offer | `10021` |
| `{status}` | `approved` (1) / `rejected` (2) / `pending` (0) | `approved` |
| `{country}` | 2-letter country code | `US` |
| `{userip}` | User's IP address | `103.21.58.10` |
| `{signature}` | Verification signature | `a3f9b2...` |

## 3. Signature Formula
Capsbit calculates MD5 signature using:
```php
$signature_raw = $pub_user_id . $publisher_payout . $offer_id . $conversion_id . $secret_key;
$expected = md5($signature_raw);
```
(Easytsk v2 also checks HMAC-SHA256 for maximum compatibility).

## 4. Expected Server Response
Capsbit expects HTTP `200 OK` with body:
```
OK
```

## 5. Admin Panel Settings in Easytsk v2
- **Name**: `Capsbit`
- **Iframe URL Pattern**: `https://offerwall.capsbit.com/YOUR_API_KEY/{user_id}`
- **Reward Ratio**: `1.00` (or your preferred ratio)
- **Secret Key**: `[Your Capsbit Secret Key]`
- **Param User ID**: `uid`
- **Param Transaction ID**: `txid`
- **Param Amount**: `payout`
- **Param Status**: `status`
- **Param Secret Key**: `sig`
- **Status Chargeback Value**: `rejected`
