Postback Macros:

{userID}

This is the unique user identifier passed to us in the intergration URL



{transactionID}

The unique transaction ID for the withdraw. You should never credit the same transactionID twice.



{revenue}

This is the gross amount of USD revenue you have earned in the postback. For chargebacks, we will send postbacks with a negative amount (for example revenue=-1.35), this means you should deduct that amount from the user as a chargeback. To view chargebacks, you can go to Postback Manager and choose "Chargebacks" under the "Type" filter.



{currencyAmount}

This is the amount of currency to award your user. For chargebacks, we will send postbacks with a negative amount (for example currency=-83125), this means you should deduct that amount from the user as a chargeback.



{hash}

(optional)The SHA256 security hash. See the "Postback Security Options" section below for more details.



{ip}

(optional) The ip address of the user that completed the offer.



{type}

(optional) Indicates the lifecycle stage. Possible values: "credit" — pay the user; "chargeback" — deduct from the user; "hold" (auto-withdraw placements only) — credit is pending validation, record by transactionID but do NOT credit; "hold\_cancelled" (auto-withdraw placements only) — a previously held credit will not be paid, locate by transactionID and remove the pending record. Do NOT credit and do NOT chargeback for "hold" or "hold\_cancelled".



{withdrawid}

(optional) This is the withdraw ID that users see in their Withdraw History page on TimeWall, to help with troubleshooting.



{reason}

(optional) Empty for "credit" and "chargeback". For "hold", contains the validation rule that triggered the hold (e.g., auto\_offerwall\_limit, auto\_user\_limit, auto\_global\_daily, auto\_verisoul, auto\_id\_mutation\_fraud). For "hold\_cancelled", contains "chargeback" when the underlying task was reversed.



{offername}

(optional) Name of the offer/task the user completed. For manual-withdraw placements, this is always "TimeWall Withdrawal" (since manual withdrawals bundle multiple tasks into one postback).



{offerdetail}

(optional) Per-task detail string passed by the originating network (URL-encoded). Empty for manual-withdraw placements.



{original\_txid}

(optional) For "chargeback" postbacks, the transactionID of the original credit being reversed, so you can match the chargeback to the credit and simply mark that transaction as chargedback. Empty for other postback types.





Postback Security Options:

1\. Server IP Whitelisting

You can whitelist requests from these IPs only 18.156.132.55, 51.81.120.73, 142.111.248.18

\* We are retiring the two grayed-out IPs. Please whitelist all 3 IPs for now — you will be notified when 51.81.120.73 and 142.111.248.18 are no longer in use.

2\. Postback Hash

We will send back the following hashed value using the {hash} macro.

hash("sha256", userID . revenue . SecretKey)

Your Secret Key: 0c0796625344591cc252afc2e52be8d3

If you would like a test postback, please contact support by clicking the blue icon in the bottom right corner of the website.

