<?php

namespace Database\Seeders;

use App\Models\AdPlacement;
use App\Models\Author;
use App\Models\Category;
use App\Models\Post;
use App\Models\Site;
use App\Models\SitePage;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Central Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@easytsk.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('admin123456'),
            ]
        );

        // 2. Demo Tenant Sites (Blog 01 to Blog 03)
        $demoSites = [
            [
                'name' => 'CryptoPulse Insights',
                'slug' => 'blog1',
                'subdomain' => 'blog1',
                'niche' => 'Cryptocurrency & DeFi',
                'tagline' => 'Decentralized Intelligence & Crypto Market Dynamics',
                'description' => 'CryptoPulse is a premier digital publication dedicated to cutting-edge decentralized finance, blockchain protocols, crypto trading indicators, and Web3 trends.',
                'theme_color' => '#10b981', // Emerald
                'theme_layout' => 'modern',
            ],
            [
                'name' => 'TechVibe AI',
                'slug' => 'blog2',
                'subdomain' => 'blog2',
                'niche' => 'Artificial Intelligence & Tech',
                'tagline' => 'Next-Generation Tech Breakthroughs & AI Tools',
                'description' => 'Exploring the frontiers of generative artificial intelligence, automation, developer productivity, and consumer hardware.',
                'theme_color' => '#3b82f6', // Blue
                'theme_layout' => 'bold',
            ],
            [
                'name' => 'HealthPulse Living',
                'slug' => 'blog3',
                'subdomain' => 'blog3',
                'niche' => 'Health & Longevity',
                'tagline' => 'Science-Backed Biohacking & Wellness Guides',
                'description' => 'Daily actionable guides on longevity, mental clarity, fitness protocols, and nutritional science.',
                'theme_color' => '#8b5cf6', // Purple
                'theme_layout' => 'minimal',
            ],
        ];

        foreach ($demoSites as $siteData) {
            $site = Site::firstOrCreate(
                ['subdomain' => $siteData['subdomain']],
                [
                    'name' => $siteData['name'],
                    'slug' => $siteData['slug'],
                    'niche' => $siteData['niche'],
                    'tagline' => $siteData['tagline'],
                    'description' => $siteData['description'],
                    'theme_color' => $siteData['theme_color'],
                    'theme_layout' => $siteData['theme_layout'],
                    'is_active' => true,
                    'seo_defaults' => [
                        'meta_title' => $siteData['name'] . ' - ' . $siteData['tagline'],
                        'meta_description' => $siteData['description'],
                        'keywords' => strtolower($siteData['niche']) . ', news, trends, tutorials',
                    ],
                ]
            );

            // 3. Authors
            $author = Author::firstOrCreate(
                ['name' => 'Alex Rivera', 'site_id' => $site->id],
                [
                    'slug' => 'alex-rivera',
                    'email' => 'alex@' . $site->subdomain . '.easytsk.com',
                    'bio' => 'Senior Editor with extensive background in financial analysis and digital media.',
                ]
            );

            // 4. Categories per Site
            $categories = [];
            if ($site->subdomain === 'blog1') {
                $catNames = ['Bitcoin', 'DeFi Protocols', 'Market Analysis', 'Web3 Tutorials'];
            } elseif ($site->subdomain === 'blog2') {
                $catNames = ['Generative AI', 'Developer Tools', 'Robotics', 'Cybersecurity'];
            } else {
                $catNames = ['Nutrition', 'Biohacking', 'Sleep & Recovery', 'Fitness Workouts'];
            }

            foreach ($catNames as $idx => $cName) {
                $categories[] = Category::firstOrCreate(
                    ['site_id' => $site->id, 'slug' => Str::slug($cName)],
                    ['name' => $cName, 'sort_order' => $idx]
                );
            }

            // 5. Tags
            $tags = [];
            $tagNames = ['Trends', 'Guide2026', 'HighYield', 'Strategy'];
            foreach ($tagNames as $tName) {
                $tags[] = Tag::firstOrCreate(
                    ['site_id' => $site->id, 'slug' => Str::slug($tName)],
                    ['name' => $tName]
                );
            }

            // 6. Sample Articles with Rich Paragraphs for In-Content Ad testing
            for ($i = 1; $i <= 3; $i++) {
                $title = "Comprehensive 2026 Guide to Master {$site->niche} (Part {$i})";
                $slug = Str::slug($title);

                $content = "<p>The rapid acceleration of {$site->niche} has created unprecedented opportunities for proactive investors, developers, and enthusiasts worldwide. In this comprehensive guide, we dissect the foundational pillars driving this momentum.</p>"
                    . "<p>Understanding the underlying mechanics is critical before committing capital or engineering resources. Market participants frequently make the mistake of chasing hype without verifying fundamental sustainability and historical benchmarks.</p>"
                    . "<h2>Key Strategies and Actionable Frameworks</h2>"
                    . "<p>Our research team has tested dozens of methodologies over the preceding twelve months. Three distinct frameworks emerged as top performers in terms of risk-adjusted yield and long-term durability.</p>"
                    . "<p>First, strict risk management and position sizing must take precedence. Never allocate more than 5% of total liquidity to speculative micro-cap assets or unverified protocols.</p>"
                    . "<p>Second, prioritize automated compounding routines. Modern programmatic smart contracts and portfolio rebalancing bots can capture micro-inefficiencies across volatile market cycles.</p>"
                    . "<h2>Risk Mitigation and Future Outlook</h2>"
                    . "<p>As regulatory scrutiny sharpens globally, compliance and verifiable proof-of-reserves are becoming non-negotiable standards for top-tier platforms.</p>"
                    . "<p>In conclusion, keeping a long-term time horizon combined with agile tactical positioning provides the highest probability of consistent growth.</p>";

                $post = Post::firstOrCreate(
                    ['site_id' => $site->id, 'slug' => $slug],
                    [
                        'author_id' => $author->id,
                        'title' => $title,
                        'excerpt' => "Explore the core fundamentals and high-yield strategic models for {$site->niche} in this detailed breakdown.",
                        'content' => $content,
                        'status' => 'published',
                        'published_at' => now()->subHours($i * 6),
                        'views_count' => rand(150, 2400),
                        'is_featured' => ($i === 1),
                        'is_trending' => ($i === 2),
                        'reading_time' => 4,
                        'meta_title' => $title,
                        'meta_description' => "Detailed guide on {$site->niche}.",
                    ]
                );

                $post->categories()->sync([$categories[0]->id, $categories[1]->id]);
                $post->tags()->sync([$tags[0]->id, $tags[1]->id]);
            }

            // 7. Legal Pages (Privacy, Terms, About, Contact)
            $pages = [
                [
                    'title' => 'Privacy Policy',
                    'slug' => 'privacy-policy',
                    'content' => "<h2>Privacy Policy for {$site->name}</h2><p>At {$site->name}, accessible from {$site->url}, one of our main priorities is the privacy of our visitors. This document details how information is handled.</p><h3>Log Files & Ad Partners</h3><p>{$site->name} uses standard log files and partners with trusted ad networks including Adsterra and Monetag.</p>",
                ],
                [
                    'title' => 'Terms of Service',
                    'slug' => 'terms-of-service',
                    'content' => "<h2>Terms of Service</h2><p>By accessing {$site->name}, you agree to comply with our editorial terms and conditions.</p>",
                ],
                [
                    'title' => 'About Us',
                    'slug' => 'about-us',
                    'content' => "<h2>About {$site->name}</h2><p>Welcome to {$site->name}, your definitive portal for {$site->niche} intelligence, guides, and updates.</p>",
                ],
                [
                    'title' => 'Contact Us',
                    'slug' => 'contact',
                    'content' => "<h2>Contact Editorial Team</h2><p>For press inquiries, editorial contributions, or advertising partnerships, email us at: editorial@{$site->subdomain}.easytsk.com</p>",
                ],
            ];

            foreach ($pages as $p) {
                SitePage::firstOrCreate(
                    ['site_id' => $site->id, 'slug' => $p['slug']],
                    [
                        'title' => $p['title'],
                        'content' => $p['content'],
                        'is_published' => true,
                    ]
                );
            }
        }
    }
}
