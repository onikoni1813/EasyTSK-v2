EpicAds API
Audiences


GET
/adv/audiences
Audiences list


POST
/adv/audiences/create
Create audience


GET
/adv/audiences/{id}
Get audience data


PATCH
/adv/audiences/{id}
Update audience


DELETE
/adv/audiences/delete/{id}
Delete audience


POST
/adv/audiences/clone/{id}
Clone audience

Campaigns


GET
/adv/campaigns
Campaigns list


POST
/adv/campaigns/create
Create campaign


GET
/adv/campaigns/{id}
Get campaign data


PATCH
/adv/campaigns/{id}
Update campaign


DELETE
/adv/campaigns/delete/{id}
Delete campaign


POST
/adv/campaigns/restore/{id}
Restore campaign


POST
/adv/campaigns/clone/{id}
Clone campaign


POST
/adv/campaigns/start/{id}
Start campaign


POST
/adv/campaigns/stop/{id}
Stop campaign


POST
/adv/campaigns/stop-multiple
Stop multiple campaigns


PATCH
/adv/campaigns/replace-domain
Replaces domain in urls


GET
/adv/campaigns/{id}/subaccounts
Get campaign subaccounts


DELETE
/adv/campaigns/{id}/reset-subaccounts-prices
Reset subaccounts prices

Advertisements


POST
/adv/ads/create
Create advertisement

Collections


GET
/collections/campaign-categories
Campaign categories list


GET
/collections/countries
Get countries list with min prices


GET
/collections/cities
Get cities list


GET
/collections/operation-systems
Get operation systems list


GET
/collections/browsers
Get browsers list


GET
/collections/os-versions
Get os versions list


GET
/collections/languages
Get languages list


GET
/collections/traffic-channels
Get traffic channels list


GET
/collections/badges
Get badges list


GET
/collections/inpage-templates
Get inpage templates list

User


GET
/user
Get user data


GET
/user/balance
Get user balance


GET
/user/notifications
Get user notifications


GET
/user/referrals
Get user referrals


GET
/user/deposits
Get user deposits

Statistics


GET
/statistics/adv
Get advertiser statistics


GET
/statistics/volumes
Get volumes of traffic


Schemas
AdFormatstring
Enum:
Array [ 5 ]
TrafficTypestring
Enum:
Array [ 5 ]
DeviceTypestring
Device type

Enum:
Array [ 3 ]
BannerAdTypestring
example: simple
Banner type (to use selfcode and html5 contact the manager)

Enum:
Array [ 3 ]
BannerSizestring
example: 300x250
Banner size

Enum:
Array [ 14 ]
AudienceData{
name	[...]
black_sources	[...]
white_sources	[...]
black_ip	[...]
white_ip	[...]
black_subscriptions	[...]
white_subscriptions	[...]
}
Audience{
id	[...]
name	[...]
black_sources	[...]
white_sources	[...]
black_ip	[...]
white_ip	[...]
black_subscriptions	[...]
white_subscriptions	[...]
}
CampaignData{
name	[...]
url	[...]
traffic_type	AdFormat[...]
device_type	DeviceType[...]
countries_prices	{...}
example: OrderedMap { "UA": 0.04, "US": 0.05, "GB": 0.02 }
is_active	[...]
limit_spend_day	[...]
limit_spend_total	[...]
cities	[...]
exclude_cities	[...]
os	[...]
exclude_os	[...]
os_versions	[...]
exclude_os_versions	[...]
browsers	[...]
exclude_browsers	[...]
languages	[...]
exclude_languages	[...]
audience_id	[...]
category_id	[...]
banner_ad_type	BannerAdType[...]
banner_size	BannerSize[...]
disabled_hours	[...]
}
CampaignDataCreate{
name*	[...]
url*	[...]
traffic_type*	AdFormat[...]
device_type*	DeviceType[...]
countries_prices*	{...}
example: OrderedMap { "UA": 0.04, "US": 0.05, "GB": 0.02 }
is_active	[...]
limit_spend_day	[...]
limit_spend_total	[...]
cities	[...]
exclude_cities	[...]
os	[...]
exclude_os	[...]
os_versions	[...]
exclude_os_versions	[...]
browsers	[...]
exclude_browsers	[...]
languages	[...]
exclude_languages	[...]
audience_id	[...]
category_id	[...]
banner_ad_type	BannerAdType[...]
banner_size	BannerSize[...]
disabled_hours	[...]
is_adult	[...]
}
CampaignDataUpdate{
name	[...]
url	[...]
traffic_type	AdFormat[...]
device_type	DeviceType[...]
countries_prices	{...}
example: OrderedMap { "UA": 0.04, "US": 0.05, "GB": 0.02 }
is_active	[...]
limit_spend_day	[...]
limit_spend_total	[...]
cities	[...]
exclude_cities	[...]
os	[...]
exclude_os	[...]
os_versions	[...]
exclude_os_versions	[...]
browsers	[...]
exclude_browsers	[...]
languages	[...]
exclude_languages	[...]
audience_id	[...]
category_id	[...]
banner_ad_type	BannerAdType[...]
banner_size	BannerSize[...]
disabled_hours	[...]
sources_prices	{...}
example: OrderedMap { "542": 0.003, "1234": 0.004, "3123": 0.0001 }
}
AdData{
campaign_id*	[...]
title	[...]
text	[...]
icon	[...]
image	[...]
badge	[...]
is_active	[...]
is_adult	[...]
button_text1	[...]
button_text2	[...]
limit_spend_day	[...]
limit_spend_total	[...]
banner_image	[...]
selfcode	[...]
html5_zip	[...]
}
Campaign{
id	[...]
traffic_type	[...]
pay_model	[...]
name	[...]
url	[...]
moderation	[...]
ban_reason	[...]
is_active	[...]
is_trash	[...]
is_suspect	[...]
is_adult	[...]
limit_spend_day	[...]
limit_spend_total	[...]
limit_clicks_day	[...]
limit_clicks_total	[...]
click_today	[...]
click_total	[...]
spend_today	[...]
spend_total	[...]
device_type	[...]
cities	[...]
exclude_cities	[...]
exclude_regions	[...]
subscription_period	[...]
os	[...]
exclude_os	[...]
browsers	[...]
exclude_browsers	[...]
os_versions	[...]
exclude_os_versions	[...]
languages	[...]
exclude_languages	[...]
audience_id	[...]
countries_prices	{...}
example: OrderedMap { "BY": 0.04, "KZ": 0.05, "RU": 0.02 }
sources_prices	{...}
nullable: true
example: OrderedMap { "3422": "0.02", "262834": "0.021" }
stopped_at	[...]
created_at	[...]
updated_at	[...]
category_id	[...]
collect_audience	[...]
collect_audience_id	[...]
cpa_offer_id	[...]
status	[...]
moderation_start	[...]
stopped_on_schedule	[...]
disabled_hours	[...]
banner_ad_type	[...]
banner_size	[...]
}
CampaignWithStat{
id	[...]
traffic_type	[...]
pay_model	[...]
name	[...]
url	[...]
moderation	[...]
ban_reason	[...]
is_active	[...]
is_trash	[...]
is_suspect	[...]
is_adult	[...]
limit_spend_day	[...]
limit_spend_total	[...]
limit_clicks_day	[...]
limit_clicks_total	[...]
click_today	[...]
click_total	[...]
spend_today	[...]
spend_total	[...]
device_type	[...]
cities	[...]
exclude_cities	[...]
exclude_regions	[...]
subscription_period	[...]
os	[...]
exclude_os	[...]
browsers	[...]
exclude_browsers	[...]
os_versions	[...]
exclude_os_versions	[...]
languages	[...]
exclude_languages	[...]
audience_id	[...]
countries_prices	{...}
example: OrderedMap { "BY": 0.04, "KZ": 0.05, "RU": 0.02 }
sources_prices	{...}
nullable: true
example: OrderedMap { "3422": "0.02", "262834": "0.021" }
stopped_at	[...]
created_at	[...]
updated_at	[...]
category_id	[...]
collect_audience	[...]
collect_audience_id	[...]
cpa_offer_id	[...]
status	[...]
moderation_start	[...]
stopped_on_schedule	[...]
disabled_hours	[...]
banner_ad_type	[...]
banner_size	[...]
leads	[...]
profit	[...]
ads_active	[...]
ads_stopped	[...]
ads_pending	[...]
ads_rejected	[...]
shows	[...]
clicks	[...]
spend	[...]
winrate	[...]
ctr	[...]
roi	[...]
}
CampaignWithAds{
id	[...]
traffic_type	[...]
pay_model	[...]
name	[...]
url	[...]
moderation	[...]
ban_reason	[...]
is_active	[...]
is_trash	[...]
is_suspect	[...]
is_adult	[...]
limit_spend_day	[...]
limit_spend_total	[...]
limit_clicks_day	[...]
limit_clicks_total	[...]
click_today	[...]
click_total	[...]
spend_today	[...]
spend_total	[...]
device_type	[...]
cities	[...]
exclude_cities	[...]
exclude_regions	[...]
subscription_period	[...]
os	[...]
exclude_os	[...]
browsers	[...]
exclude_browsers	[...]
os_versions	[...]
exclude_os_versions	[...]
languages	[...]
exclude_languages	[...]
audience_id	[...]
countries_prices	{...}
example: OrderedMap { "BY": 0.04, "KZ": 0.05, "RU": 0.02 }
sources_prices	{...}
nullable: true
example: OrderedMap { "3422": "0.02", "262834": "0.021" }
stopped_at	[...]
created_at	[...]
updated_at	[...]
category_id	[...]
collect_audience	[...]
collect_audience_id	[...]
cpa_offer_id	[...]
status	[...]
moderation_start	[...]
stopped_on_schedule	[...]
disabled_hours	[...]
banner_ad_type	[...]
banner_size	[...]
ads	[...]
}
Ad
CountryPrices{
description:	
Country data with min prices

id	[...]
geoname_id	[...]
country_iso_code	[...]
country_name_ru	[...]
country_name_en	[...]
min_prices	[...]
}
City{
description:	
City data

geoname_id	[...]
country_iso_code	[...]
city_name_ru	[...]
city_name_en	[...]
}