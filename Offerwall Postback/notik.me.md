Whenever a user makes a conversion, we will send you a URL request, called a 'Server to Server Postback' with some information. Using this information, you can reward the user who performed the action accordingly. The postback will be sent to the global postback URL you have defined in your App Settings.



These are the parameters that we will send to you with your postback to ensure proper tracking of your leads:



Parameter	Description

pub\_id	Your Publisher ID specified in the click url.

app\_id	Your App ID specified in the click url.

user\_id	Your User ID specified in the click url.

s1	Your subID (\&s1=) specified in the click url.

amount	Virtual amount earned by your User. In your chosen conversion rate.

payout	Amount earned for conversion. In USD ($).

offer\_id	ID of completed offer.

offer\_name	Name of completed offer.

currency\_name	Virtual currency name defined in your app settings.

timestamp	Offer completed timestamp.

hash	This hash consists of a HEX encoded SHA1 HMAC. The whole URL is hashed with the secret key of the App. By checking the hash, you can be sure that the postback was actually sent by us and that it is legit.

txn\_id	Unique identifier of this transaction, make sure to store and check this for each request, so you won't credit your user multiple times.

conversion\_ip	Converting device's IP address if known, 0.0.0.0 otherwise

rewarded\_txn\_id	This will only be available for chargebacks and will contain the previously sent reward postback transaction id, in case you need to locate the original conversion.

event\_id	ID of the offer event that was credited. For non-event conversions, it will be empty.

event\_name	Name of the offer event that was credited. For non-event conversions, it will be empty.

Important note:

\- Keep in mind that in case of chargebacks, the payout and amount parameter will be negative. And the amount will be calculated based on your current conversion rate settings.

\- We use RFC 1738 as encoding\_type, which implies that spaces are encoded as plus (+) signs.

\- Please check in your application, if the parameters without values in the postback are being treated as "NULL" instead of empty value, which would result in different hash value.

\- After validating the postback request details, we will require expected response as soon as possible, to mark the postback as sent. Our max wait time is 15 seconds.



Postback Ips: All of our postbacks shall be sent from the IP: 192.53.121.112,158.69.116.45. Please whitelist these IP addresses as these are the ONLY IP we'll be sending postbacks from.



Expected Response

We expect a response with 200 status code to indicate that the postback was received successfully. In case we receive response with any other invalid status code, we will try to send the postback up to 2 times, if we still do not receive a 200 status code, we will stop sending postback calls and email you with the details, so you can process it manually.



PHP Example Code

<?php

/\*Get query parameters\*/

$pub\_id = $\_REQUEST\['pub\_id'];

$app\_id = $\_REQUEST\['app\_id'];

$user\_id = $\_REQUEST\['user\_id'];

$amount = $\_REQUEST\['amount'];

$payout = $\_REQUEST\['payout'];

$offer\_id = $\_REQUEST\['offer\_id'];

$offer\_name = $\_REQUEST\['offer\_name'];

$event\_id = $\_REQUEST\['event\_id'];

$event\_name = $\_REQUEST\['event\_name'];

$txn\_id = $\_REQUEST\['txn\_id'];

$currency\_name = $\_REQUEST\['currency\_name'];

$timestamp = $\_REQUEST\['timestamp'];

$hash = $\_REQUEST\['hash'];



/\*Check if duplicate transaction\*/

$transactionExist = false; // Search in database if current txn\_id exist. True if exist

if ($transactionExist) {

&#x20;   /\*Duplicate transaction detected. Do not reward user but send us postback received positive response\*/

&#x20;   return 1;

}



/\*Create validation hash and validate hashes\*/

$secretKey = ""; // This has to be your App's secret key that you can find in you App detail page

/\*Get the currently active http protocol\*/

$protocol = (isset($\_SERVER\["HTTPS"]) \&\& $\_SERVER\["HTTPS"] === "on") ? "https" : "http";

/\*Build the full callback URL\*/

/\*Example: https://url.com?param1=foo\&param2=bar\&hash=3171f6b78e06cadcec4c9c3b15f8588400e8738\*/

$url = "$protocol://$\_SERVER\[HTTP\_HOST]$\_SERVER\[REQUEST\_URI]";

/\*Get the callback URL without the "hash" query parameter\*/

/\*Example: https://url.com?param1=foo\&param2=bar\*/

$urlWithoutHash = substr($url, 0, -strlen("\&hash=$hash"));

/\*Generate a hash from the complete callback URL without the "hash" query parameter\*/

$generatedHash = hash\_hmac("sha1", $urlWithoutHash, $secretKey);



/\*Check if the generated hash is the same as the "hash" query parameter\*/

if ($generatedHash == $hash) {

&#x20;   /\*Validation successful. Queue your user credit functions and send us postback received positive response\*/

&#x20;   return 1;

} el
se {

&#x20;   /\*Hash not equal. Send error response.

&#x20;   Try to fix any errors found for hash validation.

&#x20;   Contact us in case the postback is from our ip and need some assistance.\*/

&#x20;   return 0;

}





