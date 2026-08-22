<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteDomain;
use App\Models\SitePage;
use App\Models\SitePost;
use App\Models\SiteType;
use App\Models\Tool;
use App\Models\ToolCategory;
use Illuminate\Database\Seeder;

class FirstSiteSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Site Type
        $siteType = SiteType::firstOrCreate(
            ['slug' => 'tools'],
            ['name' => 'Tools & Utilities', 'description' => 'Online utility tools and productivity applications.']
        );

        // 2. Create Property Site
        $site = Site::firstOrCreate(
            ['subdomain' => 'tools'],
            [
                'site_type_id' => $siteType->id,
                'name' => 'EasyTools Hub',
                'slug' => 'easy-tools',
                'primary_domain' => 'tools.easytsk.com',
                'status' => 'active',
                'theme' => 'default',
                'default_language' => 'en',
                'meta_title' => 'EasyTools Hub — Free Fast Online Developer & Productivity Tools',
                'meta_description' => 'Free, secure, and privacy-first online tools running directly in your web browser. Word counter, JSON formatter, Base64 encoder, password generator & QR code generator.',
            ]
        );

        // 3. Create Domain Entry
        SiteDomain::firstOrCreate(
            ['domain_name' => 'tools.easytsk.com'],
            [
                'site_id' => $site->id,
                'is_primary' => true,
                'is_verified' => true,
                'ssl_status' => 'active',
            ]
        );

        // 4. Configure Site Settings
        $site->setSetting('primary_color', '#10B981');
        $site->setSetting('logo_url', '/images/logo-tools.png');
        $site->setSetting('header_nav', [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Tools', 'url' => '/tools'],
            ['label' => 'Guides', 'url' => '/articles'],
            ['label' => 'About', 'url' => '/about'],
        ]);
        $site->setSetting('footer_links', [
            ['label' => 'Privacy Policy', 'url' => '/privacy'],
            ['label' => 'Terms of Service', 'url' => '/terms'],
            ['label' => 'Contact Us', 'url' => '/contact'],
            ['label' => 'FAQ', 'url' => '/p/faq'],
        ]);

        // 5. Seed Tool Categories
        $catText = ToolCategory::firstOrCreate(
            ['slug' => 'text-utilities'],
            ['name' => 'Text & Formatting', 'description' => 'Word counters, text converters, and character tools.']
        );
        $catDev = ToolCategory::firstOrCreate(
            ['slug' => 'developer-tools'],
            ['name' => 'Developer Utilities', 'description' => 'JSON formatters, Base64 encoders, and URL tools.']
        );
        $catSecurity = ToolCategory::firstOrCreate(
            ['slug' => 'security-generators'],
            ['name' => 'Security & Generators', 'description' => 'Password generators, QR code tools, and secrets.']
        );

        // 6. Seed Master Tools Suite (7 Tools)
        $toolsData = [
            [
                'name' => 'Word & Character Counter',
                'slug' => 'word-counter',
                'category_id' => $catText->id,
                'component_name' => 'WordCounter',
                'summary' => 'Count words, characters, sentences, paragraphs, and estimated reading time in real-time.',
                'description' => 'Word Counter is a free, privacy-first online tool designed for content writers, students, and editors. Compute exact word counts, character limits (with and without spaces), sentence structures, and reading duration instantly.',
                'meta_title' => 'Word Counter — Free Online Word & Character Count Tool',
                'meta_description' => 'Free online word counter and character count tool. Analyze word count, character count, sentence length, and reading time in real-time.',
                'execution_type' => 'client_side',
            ],
            [
                'name' => 'JSON Formatter & Validator',
                'slug' => 'json-formatter',
                'category_id' => $catDev->id,
                'component_name' => 'JsonFormatter',
                'summary' => 'Format, beautify, validate, and minify raw JSON payloads instantly.',
                'description' => 'JSON Formatter is an indispensable tool for web developers and API engineers. Paste raw JSON code to validate syntax, format nested trees with clean indentation, or minify JSON for production payloads.',
                'meta_title' => 'JSON Formatter & Validator — Free Online JSON Beautifier',
                'meta_description' => 'Format, validate, beautify, and minify JSON data online. Free developer tool for API payload inspection.',
                'execution_type' => 'client_side',
            ],
            [
                'name' => 'Base64 Encoder & Decoder',
                'slug' => 'base64-tool',
                'category_id' => $catDev->id,
                'component_name' => 'Base64Tool',
                'summary' => 'Encode text strings to Base64 or decode Base64 data strings.',
                'description' => 'Convert text or binary strings into standard Base64 encoding format or decode Base64 strings back to plain UTF-8 text.',
                'meta_title' => 'Base64 Encoder & Decoder — Free Online Conversion Tool',
                'meta_description' => 'Fast online Base64 encoder and decoder tool. Convert plain text to Base64 format and decode Base64 strings instantly.',
                'execution_type' => 'client_side',
            ],
            [
                'name' => 'Strong Password Generator',
                'slug' => 'password-generator',
                'category_id' => $catSecurity->id,
                'component_name' => 'PasswordGenerator',
                'summary' => 'Generate secure, cryptographically random passwords with custom complexity.',
                'description' => 'Protect your online accounts with cryptographically strong passwords. Customize length (8 to 64 characters), uppercase letters, numbers, and special symbols.',
                'meta_title' => 'Strong Password Generator — Free Random Secret Generator',
                'meta_description' => 'Generate random, cryptographically secure passwords online. Customize length, uppercase, numbers, and symbols.',
                'execution_type' => 'client_side',
            ],
            [
                'name' => 'QR Code Generator',
                'slug' => 'qr-code-generator',
                'category_id' => $catSecurity->id,
                'component_name' => 'QrCodeGenerator',
                'summary' => 'Create QR codes for website URLs, text, and contact links instantly.',
                'description' => 'Generate high-resolution QR codes directly on HTML5 Canvas. Ideal for website links, contact cards, and text notes.',
                'meta_title' => 'QR Code Generator — Free Online QR Code Creator',
                'meta_description' => 'Free online QR code generator. Create instant QR codes for website links, URLs, and text.',
                'execution_type' => 'client_side',
            ],
            [
                'name' => 'URL Encoder & Decoder',
                'slug' => 'url-encoder',
                'category_id' => $catDev->id,
                'component_name' => 'UrlEncoder',
                'summary' => 'Encode URI components or decode percent-encoded URLs.',
                'description' => 'Safely convert special characters in URLs using standard percent-encoding (percent-escaped characters).',
                'meta_title' => 'URL Encoder & Decoder — Free Online Link Converter',
                'meta_description' => 'Encode and decode URLs online. Convert special characters to percent-encoded URI strings.',
                'execution_type' => 'client_side',
            ],
            [
                'name' => 'Text Case Converter',
                'slug' => 'case-converter',
                'category_id' => $catText->id,
                'component_name' => 'CaseConverter',
                'summary' => 'Convert text to UPPERCASE, lowercase, Title Case, camelCase, or snake_case.',
                'description' => 'Transform text formatting instantly between UPPERCASE, lowercase, Title Case, camelCase, snake_case, and kebab-case.',
                'meta_title' => 'Text Case Converter — Free Online String Converter',
                'meta_description' => 'Convert text case online. Transform text to UPPERCASE, lowercase, Title Case, camelCase, or snake_case.',
                'execution_type' => 'client_side',
            ],
        ];

        foreach ($toolsData as $tData) {
            $tool = Tool::firstOrCreate(
                ['slug' => $tData['slug']],
                $tData
            );

            if (!$site->tools()->where('tool_id', $tool->id)->exists()) {
                $site->tools()->attach($tool->id, ['is_featured' => true]);
            }
        }

        // 7. Seed Supporting Articles & Guides
        $siteContentCat = SiteCategory::firstOrCreate(
            ['site_id' => $site->id, 'slug' => 'guides'],
            ['name' => 'Productivity Guides', 'description' => 'Tutorials, tips, and best practices.']
        );

        $articles = [
            [
                'site_id' => $site->id,
                'category_id' => $siteContentCat->id,
                'title' => 'How to Efficiently Use Word Counter for SEO Copywriting',
                'slug' => 'how-to-use-word-counter',
                'summary' => 'Learn how tracking word counts, paragraph length, and reading time improves SEO performance.',
                'content' => "Word count plays a crucial role in content optimization and reader engagement. Search engines prioritize comprehensive, well-structured content that answers searcher intent effectively.\n\nKey Copywriting Rules:\n1. Keep introduction concise (50-100 words).\n2. Use short paragraphs (2-3 sentences max) to improve readability on mobile screens.\n3. Aim for optimal reading speed (200 words per minute).\n\nUse our free Word Counter tool to inspect character limits, word frequency, and estimated reading time before publishing.",
                'reading_time_minutes' => 4,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $siteContentCat->id,
                'title' => 'JSON Formatting Best Practices for Web Developers',
                'slug' => 'json-formatting-best-practices',
                'summary' => 'Avoid common syntax errors, validate nested objects, and optimize JSON payloads for APIs.',
                'content' => "JavaScript Object Notation (JSON) is the universal data format for modern REST APIs and web services. Following standard formatting rules ensures clean server-side parsing.\n\nBest Practices:\n- Always use double quotes around keys and string values.\n- Remove trailing commas at the end of arrays or objects.\n- Minify JSON for production API transfers to reduce bandwidth.",
                'reading_time_minutes' => 5,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $siteContentCat->id,
                'title' => 'Generating Secure Passwords: Complete Cybersecurity Guide',
                'slug' => 'generating-secure-passwords',
                'summary' => 'Understand password entropy, why length matters more than complexity, and how to stay safe.',
                'content' => "Password security is your first line of defense against cyber threats. Using short or predictable passwords makes accounts vulnerable to automated brute-force attacks.\n\nCybersecurity Guidelines:\n- Always use at least 16 characters.\n- Combine uppercase, lowercase, numbers, and symbols.\n- Never reuse passwords across multiple websites.\n\nGenerate strong passwords instantly with our client-side Strong Password Generator tool.",
                'reading_time_minutes' => 4,
                'is_published' => true,
                'published_at' => now(),
            ],
        ];

        foreach ($articles as $artData) {
            SitePost::firstOrCreate(
                ['site_id' => $site->id, 'slug' => $artData['slug']],
                $artData
            );
        }

        // 8. Seed Legal & Info Pages
        $pages = [
            [
                'site_id' => $site->id,
                'title' => 'Frequently Asked Questions (FAQ)',
                'slug' => 'faq',
                'content' => "Q: Are these online tools free to use?\nA: Yes, 100% free with no registration required.\n\nQ: Is my data stored on your server?\nA: No. All tool operations (Word Counting, JSON Formatting, Base64 conversion, Password Generation) execute entirely client-side inside your browser for complete privacy.\n\nQ: Can I use these tools on mobile devices?\nA: Absolutely! EasyTools Hub is optimized for mobile, tablet, and desktop screens.",
                'meta_title' => 'FAQ — EasyTools Hub Frequently Asked Questions',
                'meta_description' => 'Find answers to common questions about EasyTools Hub free online tools and privacy policy.',
                'is_published' => true,
                'published_at' => now(),
            ],
        ];

        foreach ($pages as $pData) {
            SitePage::firstOrCreate(
                ['site_id' => $site->id, 'slug' => $pData['slug']],
                $pData
            );
        }
    }
}
