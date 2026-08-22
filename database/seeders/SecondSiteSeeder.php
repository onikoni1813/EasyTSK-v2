<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteDomain;
use App\Models\SitePost;
use App\Models\SiteType;
use Illuminate\Database\Seeder;

class SecondSiteSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Site Type
        $siteType = SiteType::firstOrCreate(
            ['slug' => 'guides'],
            ['name' => 'Guides & Content Hub', 'description' => 'Technical blog, documentation, and tutorial publishing site.']
        );

        // 2. Create Property Site
        $site = Site::firstOrCreate(
            ['subdomain' => 'guides'],
            [
                'site_type_id' => $siteType->id,
                'name' => 'TechGuides Hub',
                'slug' => 'tech-guides',
                'primary_domain' => 'guides.easytsk.com',
                'status' => 'active',
                'theme' => 'default',
                'default_language' => 'en',
                'meta_title' => 'TechGuides Hub — In-Depth Web Development & Cloud Tutorials',
                'meta_description' => 'Comprehensive technical guides, web development tutorials, cybersecurity best practices, DevOps automation, and AI workflow insights.',
            ]
        );

        // 3. Create Domain Entry
        SiteDomain::firstOrCreate(
            ['domain_name' => 'guides.easytsk.com'],
            [
                'site_id' => $site->id,
                'is_primary' => true,
                'is_verified' => true,
                'ssl_status' => 'active',
            ]
        );

        // 4. Configure Site Settings
        $site->setSetting('primary_color', '#3B82F6');
        $site->setSetting('logo_url', '/images/logo-guides.png');
        $site->setSetting('header_nav', [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'All Guides', 'url' => '/articles'],
            ['label' => 'About', 'url' => '/about'],
        ]);
        $site->setSetting('footer_links', [
            ['label' => 'Privacy Policy', 'url' => '/privacy'],
            ['label' => 'Terms of Service', 'url' => '/terms'],
            ['label' => 'Contact Us', 'url' => '/contact'],
        ]);

        // 5. Seed Content Categories
        $catWeb = SiteCategory::firstOrCreate(
            ['site_id' => $site->id, 'slug' => 'web-development'],
            ['name' => 'Web Development', 'description' => 'Frontend, Backend, APIs, and modern JavaScript frameworks.']
        );
        $catSecurity = SiteCategory::firstOrCreate(
            ['site_id' => $site->id, 'slug' => 'cybersecurity'],
            ['name' => 'Cybersecurity', 'description' => 'Security protocols, vulnerability patch management, and authentication.']
        );
        $catDevOps = SiteCategory::firstOrCreate(
            ['site_id' => $site->id, 'slug' => 'devops-cloud'],
            ['name' => 'DevOps & Cloud', 'description' => 'Docker, Kubernetes, CI/CD pipelines, and cloud hosting architecture.']
        );
        $catAI = SiteCategory::firstOrCreate(
            ['site_id' => $site->id, 'slug' => 'ai-automation'],
            ['name' => 'AI & Automation', 'description' => 'Large language models, automated workflows, and prompt engineering.']
        );

        // 6. Seed 10 Technical Tutorials
        $tutorials = [
            [
                'site_id' => $site->id,
                'category_id' => $catWeb->id,
                'title' => 'Mastering Modern Vue 3 Composition API & Script Setup',
                'slug' => 'mastering-vue-3-composition-api',
                'summary' => 'Complete guide to building scalable, type-safe Vue 3 applications using script setup syntax.',
                'content' => "Vue 3 introduced the Composition API as an alternative to the Options API, providing better code organization, reusability, and TypeScript support.\n\nKey Concepts:\n1. Ref vs Reactive state management.\n2. Computed properties and watch effects.\n3. Dynamic component loading with Inertia.js.\n\nBy leveraging script setup syntax, developers write cleaner code with zero boilerplate.",
                'reading_time_minutes' => 6,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $catWeb->id,
                'title' => 'Building High-Performance Laravel 11 REST APIs with Sanctum',
                'slug' => 'building-laravel-11-rest-apis',
                'summary' => 'Step-by-step tutorial on architecting lightweight, secure RESTful APIs using Laravel 11.',
                'content' => "Laravel 11 simplifies API routing and middleware configuration while providing token authentication out of the box with Sanctum.\n\nArchitecture Steps:\n- Defining API resource transformers.\n- Rate limiting requests using throttle middleware.\n- Handling standard HTTP responses and error exceptions cleanly.",
                'reading_time_minutes' => 7,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $catWeb->id,
                'title' => 'Understanding Tailwind CSS v4 & Modern Utility-First Design',
                'slug' => 'understanding-tailwind-css-v4',
                'summary' => 'Explore the fast new Rust-based Tailwind compiler, container queries, and CSS variables.',
                'content' => "Tailwind CSS v4 brings dramatic build speed improvements powered by Oxide engine. It removes complex configuration files in favor of native CSS variables and standard directives.",
                'reading_time_minutes' => 5,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $catSecurity->id,
                'title' => 'OWASP Top 10 Web Application Vulnerabilities & Mitigations',
                'slug' => 'owasp-top-10-vulnerabilities',
                'summary' => 'Comprehensive security guide covering SQL injection, XSS, CSRF, and broken access controls.',
                'content' => "Securing web applications requires proactive defense against known attack vectors.\n\nTop Security Checklist:\n- Input sanitization and parameterized SQL queries.\n- Strict Content Security Policy (CSP) headers.\n- Enforcing CSRF token verification on all POST/PUT routes.",
                'reading_time_minutes' => 8,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $catSecurity->id,
                'title' => 'Implementing OAuth 2.0 & OpenID Connect in Web Applications',
                'slug' => 'implementing-oauth2-openid-connect',
                'summary' => 'Learn how single sign-on (SSO) works with Google, GitHub, and custom authorization servers.',
                'content' => "OAuth 2.0 delegates authentication to trusted third-party providers. By incorporating OpenID Connect, applications receive verified user profile tokens safely without storing passwords.",
                'reading_time_minutes' => 6,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $catDevOps->id,
                'title' => 'Docker & Docker Compose Containerization for PHP & Nginx',
                'slug' => 'docker-containerization-php-nginx',
                'summary' => 'Containerize PHP-FPM, Nginx, and MySQL applications for identical dev and production environments.',
                'content' => "Docker ensures consistent application runtime environments across local development and cloud servers. Learn how to write optimized multi-stage Dockerfiles for Laravel and Vite.",
                'reading_time_minutes' => 7,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $catDevOps->id,
                'title' => 'Automating CI/CD Deployments with GitHub Actions & SSH',
                'slug' => 'github-actions-cicd-deployments',
                'summary' => 'Set up automated test suites, asset compilation, and zero-downtime server deployments on git push.',
                'content' => "Continuous Integration and Continuous Deployment (CI/CD) automates testing and deployment pipelines. On every code push to main, GitHub Actions runs PHPUnit tests, builds Vite assets, and triggers server deployment.",
                'reading_time_minutes' => 6,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $catDevOps->id,
                'title' => 'Nginx Reverse Proxy & Cloudflare SSL Configuration Guide',
                'slug' => 'nginx-reverse-proxy-cloudflare-ssl',
                'summary' => 'Configure multi-domain Nginx server blocks, SSL termination, and Cloudflare CDN proxying.',
                'content' => "Efficient domain routing requires custom Nginx server configuration blocks. Learn how to handle multi-tenant domain aliases, enforce HTTPS redirection, and optimize caching headers.",
                'reading_time_minutes' => 5,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $catAI->id,
                'title' => 'Integrating LLM APIs & Vector Databases into Web Applications',
                'slug' => 'integrating-llm-apis-vector-databases',
                'summary' => 'Build AI-powered search, retrieval-augmented generation (RAG), and semantic search tools.',
                'content' => "Combine web applications with Large Language Model APIs to provide smart semantic search and automated user assistants. Learn how vector embeddings improve search accuracy.",
                'reading_time_minutes' => 7,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'site_id' => $site->id,
                'category_id' => $catAI->id,
                'title' => 'Automating Developer Workflows with AI Agents & Tools',
                'slug' => 'automating-developer-workflows-ai-agents',
                'summary' => 'Leverage autonomous coding agents, static analysis tools, and automated code review workflows.',
                'content' => "AI-assisted pair programming boosts developer velocity. Learn how to integrate automated code linting, static analysis, and regression test suites with AI agent workflows.",
                'reading_time_minutes' => 6,
                'is_published' => true,
                'published_at' => now(),
            ],
        ];

        foreach ($tutorials as $tutData) {
            SitePost::firstOrCreate(
                ['site_id' => $site->id, 'slug' => $tutData['slug']],
                $tutData
            );
        }
    }
}
