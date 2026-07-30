For developers, we prepared an API that returns responses in JSON or TEXT formats. Currently, there is one method that can be used to shorten links on behalf of your account. all you have to do is to send a GET request with your API token and URL Like the following
1
Example request
Build the request URL by replacing the highlighted values with your own.

https://shrtfly.com/api?api=a544689d14efd1e28111b5455f24b90a&type=1&url=https://example.com&alias=CustomAlias&format=json
2
Getting started
Copy a sample request in PHP or JavaScript to get started quickly.

PHP
JavaScript
$long_url  = urlencode('https://example.com');
$api_token = 'a544689d14efd1e28111b5455f24b90a';
$ad_type   = 1; //optimal
$alias     = 'CustomAlias'; //optimal
$api_url   = "https://shrtfly.com/api?api={$api_token}&url={$long_url}&alias={$alias}&type={$ad_type}&format=json";
$result    = @json_decode(file_get_contents($api_url), TRUE);
if($result["status"] === 'error') {
    echo $result["result"];
} else {
    echo $result["result"]["shorten_url"];
}
3
Responses
What you'll receive back from the endpoint, in your chosen format.

JSON Response
{
  "status": "success",
  "result": {
    "original_url": "https://example.com",
    "shorten_url": "https://shrtfly.com/CustomAlias",
    "stats_url": "https://shrtfly.com/s/CustomAlias"
  }
}
JSON Error
{
  "status": "error",
  "result": "Please enter a valid URL."
}
TEXT Response
https://shrtfly.com/CustomAlias
TEXT Error
error|Please enter a valid URL.
4
Parameters
Query parameters supported by the endpoint.

Parameter	Description	Status
api	API Key	required
url	Your long URL	required
type	Ad preferences
1 Mainstream · 2 Adult
optimal
alias	Your custom alias	optimal
format	Response format
json · text
optimal

https://shrtfly.com/api