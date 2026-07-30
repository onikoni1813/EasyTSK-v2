Authorization
To use available public methods via token you should pass it using X-AUTH-TOKEN header or token in GET attribute

curl -H 'Accept: application/json' -H 'X-AUTH-TOKEN: ' 'https://publishers.mybid.io/backend/api/public/stats?date1=2026-07-22&date2=2026-07-22&fields=date,impressions,clicks,money&filters=device=Desktop,device=Tablet&orderBy=-date&limit=500&offset=0'

OR
curl -H 'Accept: application/json' 'https://publishers.mybid.io/backend/api/public/stats?token=&date1=2026-07-22&date2=2026-07-22&fields=date,impressions,clicks,money&filters=adformat=Popunder;device=Desktop,device=Tablet&orderBy=-date&limit=500&offset=0'

Spots
You can get a list of available spots:

curl -H 'Accept: application/json' 'https://publishers.mybid.io/backend/api/public/user-spots?token='

Query documentation

Most popular request for statistics
Get statistics for the last 30 days desktop and tablet devices, for all country and all adformats and all spots, group by date

https://publishers.mybid.io/backend/api/public/stats?token=&date1=2026-06-22&date2=2026-07-22&fields=date,impressions,clicks,money&orderBy=-date&limit=500&offset=0

Get statistics for today, for all country and all adformats and all spots and all devices, group by adformat

https://publishers.mybid.io/backend/api/public/stats?token=&date1=2026-07-22&date2=2026-07-22&fields=adformat,impressions,clicks,money&orderBy=-adformat&limit=500&offset=0