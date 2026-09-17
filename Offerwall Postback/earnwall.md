# EarnWall Offerwall & Postback Integration

## 1. Overview
- **Provider Name**: EarnWall
- **Documentation**: https://earnwall.net/docs/
- **Offerwall iFrame URL**:
  `https://earnwall.net/offerwall/[API_KEY]/[USER_ID]`

## 2. S2S Postback Setup
Configure the Postback URL in EarnWall Dashboard under your registered App:
```
https://yourdomain.com/postback/earnwall
```

### Parameters Sent by EarnWall (HTTP POST)
| Parameter | Description | Example |
| :--- | :--- | :--- |
| `subId` | Unique user identifier | `123` |
| `transId` | Unique transaction ID | `XX-12345678` |
| `reward` | Virtual currency amount | `1.25` |
| `payout` | Offer payout in USD | `0.100000` |
| `status` | `1` (valid reward) / `2` (chargeback) | `1` |
| `signature` | MD5 verification hash | `17b4e2a70d6efe9796dd4c5507a9f9ab` |
| `userIp` | User IP address | `192.168.1.0` |
| `country` | Country code | `US` |

## 3. Postback Security Formula
EarnWall generates an MD5 hash using:
```php
$expected = md5($subId . $transId . $reward . $secret);
```

## 4. Expected Response
> **CRITICAL**: EarnWall requires your server to respond with the exact string:
```
ok
```
If anything else is returned (even `1` or `true`), EarnWall marks the postback delivery as failed.

## 5. Admin Panel Settings in Easytsk v2
- **Name**: `EarnWall`
- **Iframe URL Pattern**: `https://earnwall.net/offerwall/YOUR_API_KEY/{user_id}`
- **Reward Ratio**: `1.00`
- **Secret Key**: `[Your EarnWall Secret Key]`
- **Param User ID**: `subId`
- **Param Transaction ID**: `transId`
- **Param Amount**: `reward` (or `payout`)
- **Param Status**: `status`
- **Param Secret Key**: `signature`
- **Status Chargeback Value**: `2`
