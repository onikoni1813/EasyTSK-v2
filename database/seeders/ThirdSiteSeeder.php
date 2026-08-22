<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteDomain;
use App\Models\SitePost;
use App\Models\SiteType;
use Illuminate\Database\Seeder;

class ThirdSiteSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Site Type
        $siteType = SiteType::firstOrCreate(
            ['slug' => 'promos'],
            ['name' => 'Promos & Deals', 'description' => 'Discounts, coupon codes, and promo deals aggregator property.']
        );

        // 2. Create Property Site
        $site = Site::firstOrCreate(
            ['subdomain' => 'promos'],
            [
                'site_type_id' => $siteType->id,
                'name' => 'EasyPromos Hub',
                'slug' => 'easy-promos',
                'primary_domain' => 'promos.easytsk.com',
                'status' => 'active',
                'theme' => 'default',
                'default_language' => 'en',
                'meta_title' => 'EasyPromos Hub — Verified Developer & Cloud Coupon Codes',
                'meta_description' => 'Discover verified promo codes, cloud hosting discounts, software deals, and developer tool coupons updated daily.',
            ]
        );

        // 3. Create Domain Entry
        SiteDomain::firstOrCreate(
            ['domain_name' => 'promos.easytsk.com'],
            [
                'site_id' => $site->id,
                'is_primary' => true,
                'is_verified' => true,
                'ssl_status' => 'active',
            ]
        );

        // 4. Configure Site Settings
        $site->setSetting('primary_color', '#8B5CF6');
        $site->setSetting('logo_url', '/images/logo-promos.png');
        $site->setSetting('header_nav', [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'All Deals', 'url' => '/promos'],
            ['label' => 'About', 'url' => '/about'],
        ]);
        $site->setSetting('footer_links', [
            ['label' => 'Privacy Policy', 'url' => '/privacy'],
            ['label' => 'Terms of Service', 'url' => '/terms'],
            ['label' => 'Contact Us', 'url' => '/contact'],
        ]);

        // 5. Seed Content Categories
        $catHosting = SiteCategory::firstOrCreate(
            ['site_id' => $site->id, 'slug' => 'hosting-cloud'],
            ['name' => 'Hosting & Cloud', 'description' => 'VPS, cloud servers, and domain registrar discounts.']
        );
        $catDev = SiteCategory::firstOrCreate(
            ['site_id' => $site->id, 'slug' => 'developer-tools'],
            ['name' => 'Developer Tools', 'description' => 'IDE licenses, API subscriptions, and dev utility deals.']
        );
        $catSaaS = SiteCategory::firstOrCreate(
            ['site_id' => $site->id, 'slug' => 'saas-software'],
            ['name' => 'SaaS & Software', 'description' => 'Productivity apps, marketing software, and design subscriptions.']
        );
        $catVPN = SiteCategory::firstOrCreate(
            ['site_id' => $site->id, 'slug' => 'vpn-security'],
            ['name' => 'VPN & Security', 'description' => 'Encrypted VPNs, password managers, and SSL certificates.']
        );

        // 6. Seed 8 Promo Deals & Coupons
        $deals = [
            [
                'site_id' => $site->id,
                'category_id' => $catHosting->id,
                'title' => 'Cloud Hosting Special Offer — 50% Off First Year',
                'slug' => 'cloud-hosting-50-off',
                'summary' => 'Get 50% discount on high-speed NVMe cloud VPS servers with unlimited bandwidth.',
                'content' => "PROMO CODE: HOSTING50\nDiscount: 50% OFF\nExpiry: Limited Time\nTarget Link: https://easytsk.com\n\nClaim 50% off on managed NVMe SSD Cloud VPS instances. Features free SSL certificate, automatic daily backups, and 99.99% uptime SLA.",
                'reading_time_minutes' => 1,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $catHosting->id,
                'title' => 'Free .COM Domain with Annual Managed Hosting Plan',
                'slug' => 'free-com-domain-annual-plan',
                'summary' => 'Register a free .COM domain name when purchasing any annual web hosting package.',
                'content' => "PROMO CODE: FREEDOMAIN26\nDiscount: 100% OFF Domain\nExpiry: Active\nTarget Link: https://easytsk.com\n\nReceive a complimentary 1-year .COM domain registration with WHOIS privacy protection when ordering annual web hosting.",
                'reading_time_minutes' => 1,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $catDev->id,
                'title' => 'Developer IDE Subscription — 30% Student & Pro Discount',
                'slug' => 'developer-ide-30-off',
                'summary' => 'Save 30% on annual developer licenses for professional code editors and tools.',
                'content' => "PROMO CODE: DEVPRO30\nDiscount: 30% OFF\nExpiry: Verified\nTarget Link: https://easytsk.com\n\nUpgrade your developer workstation. 30% instant discount applied at checkout for professional code editor suites.",
                'reading_time_minutes' => 1,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $catDev->id,
                'title' => 'API Gateway & Microservices Credits — $100 Free Credit',
                'slug' => 'api-gateway-100-credit',
                'summary' => 'Deploy microservices and REST APIs with $100 free cloud execution credits.',
                'content' => "PROMO CODE: APICREDIT100\nDiscount: $100 Free Credit\nExpiry: Active\nTarget Link: https://easytsk.com\n\nTest serverless functions, database queries, and REST endpoints with $100 free cloud credit.",
                'reading_time_minutes' => 1,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $catSaaS->id,
                'title' => 'All-in-One SEO & Analytics Suite — 40% Lifetime Deal',
                'slug' => 'seo-analytics-suite-40-off',
                'summary' => 'Track search engine rankings, audit site performance, and analyze keyword metrics.',
                'content' => "PROMO CODE: SEOPRO40\nDiscount: 40% OFF Lifetime\nExpiry: Verified\nTarget Link: https://easytsk.com\n\nUnlock keyword research, backlink monitoring, and technical SEO audits for up to 10 websites.",
                'reading_time_minutes' => 1,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $catSaaS->id,
                'title' => 'Email Marketing & Automation Platform — 25% Off Monthly',
                'slug' => 'email-marketing-25-off',
                'summary' => 'Automate customer email sequences, newsletters, and lead generation workflows.',
                'content' => "PROMO CODE: EMAIL25\nDiscount: 25% OFF\nExpiry: Active\nTarget Link: https://easytsk.com\n\nBuild high-converting email sequences and send automated newsletters with 25% recurring monthly savings.",
                'reading_time_minutes' => 1,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $catVPN->id,
                'title' => 'Secure High-Speed VPN — 70% Off 2-Year Security Plan',
                'slug' => 'secure-vpn-70-off',
                'summary' => 'Protect your online privacy with AES-256 encryption across 50+ server locations.',
                'content' => "PROMO CODE: VPNSAFE70\nDiscount: 70% OFF\nExpiry: Limited Time\nTarget Link: https://easytsk.com\n\nEncrypt your web traffic and protect public Wi-Fi connections with 70% discount plus 3 extra free months.",
                'reading_time_minutes' => 1,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $catVPN->id,
                'title' => 'Enterprise SSL Certificate — 60% Discount on Multi-Domain SSL',
                'slug' => 'enterprise-ssl-60-off',
                'summary' => 'Secure your main domain and unlimited subdomains with 256-bit encryption.',
                'content' => "PROMO CODE: SECURESSL60\nDiscount: 60% OFF\nExpiry: Active\nTarget Link: https://easytsk.com\n\nEnsure visitor trust and HTTPS browser security with enterprise-grade SSL certificates.",
                'reading_time_minutes' => 1,
                'is_published' => true,
                'published_at' => now(),
            ],
        ];

        foreach ($deals as $dealData) {
            SitePost::firstOrCreate(
                ['site_id' => $site->id, 'slug' => $dealData['slug']],
                $dealData
            );
        }
    }
}
