<?php

namespace Database\Seeders;

class LocalImageSeeder
{
    public static function generateAll(): void
    {
        $dir = public_path('images/posts');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $configs = [
            // Site 1: Crypto
            'crypto-1' => [
                'bg1' => '#022c22', 'bg2' => '#0f172a', 'accent' => '#10b981', 'accent2' => '#34d399',
                'badge' => 'CRYPTO &amp; BITCOIN', 'title' => 'Bitcoin Layer-2 &amp; Staking', 'icon' => 'BTC',
                'pattern' => 'blocks'
            ],
            'crypto-2' => [
                'bg1' => '#064e3b', 'bg2' => '#022c22', 'accent' => '#10b981', 'accent2' => '#6ee7b7',
                'badge' => 'DEFI &amp; LIQUIDITY', 'title' => 'Real Yield DeFi Protocols', 'icon' => 'ETH',
                'pattern' => 'chart'
            ],
            'crypto-3' => [
                'bg1' => '#042f2e', 'bg2' => '#0f172a', 'accent' => '#14b8a6', 'accent2' => '#2dd4bf',
                'badge' => 'SMART CONTRACTS', 'title' => 'Web3 Code Security &amp; Audits', 'icon' => 'SEC',
                'pattern' => 'matrix'
            ],
            'crypto-4' => [
                'bg1' => '#065f46', 'bg2' => '#0f172a', 'accent' => '#34d399', 'accent2' => '#10b981',
                'badge' => 'MARKET CYCLES', 'title' => 'Macro Crypto Liquidity &amp; ETFs', 'icon' => 'MKT',
                'pattern' => 'waves'
            ],

            // Site 2: AI Tech
            'ai-1' => [
                'bg1' => '#1e1b4b', 'bg2' => '#0f172a', 'accent' => '#3b82f6', 'accent2' => '#60a5fa',
                'badge' => 'GENERATIVE AI', 'title' => 'Autonomous AI Coding Agents', 'icon' => 'AI',
                'pattern' => 'neural'
            ],
            'ai-2' => [
                'bg1' => '#172554', 'bg2' => '#0f172a', 'accent' => '#2563eb', 'accent2' => '#93c5fd',
                'badge' => 'DEVELOPER TOOLS', 'title' => 'Top 10 Generative AI Tools', 'icon' => 'DEV',
                'pattern' => 'grid'
            ],
            'ai-3' => [
                'bg1' => '#1e293b', 'bg2' => '#1e1b4b', 'accent' => '#60a5fa', 'accent2' => '#3b82f6',
                'badge' => 'AI ARCHITECTURE', 'title' => 'Building Scalable RAG Pipelines', 'icon' => 'RAG',
                'pattern' => 'neural'
            ],
            'ai-4' => [
                'bg1' => '#0f172a', 'bg2' => '#172554', 'accent' => '#38bdf8', 'accent2' => '#818cf8',
                'badge' => 'OPEN SOURCE', 'title' => 'Open-Source LLMs vs Cloud APIs', 'icon' => 'LLM',
                'pattern' => 'matrix'
            ],

            // Site 3: Finance
            'finance-1' => [
                'bg1' => '#451a03', 'bg2' => '#0f172a', 'accent' => '#f59e0b', 'accent2' => '#fbbf24',
                'badge' => 'PERSONAL FINANCE', 'title' => 'The 50/30/20 Budgeting Rule', 'icon' => 'FIN',
                'pattern' => 'chart'
            ],
            'finance-2' => [
                'bg1' => '#78350f', 'bg2' => '#1e293b', 'accent' => '#fbbf24', 'accent2' => '#f59e0b',
                'badge' => 'SAVINGS &amp; YIELD', 'title' => 'High-Yield Savings &amp; T-Bills', 'icon' => 'USD',
                'pattern' => 'blocks'
            ],
            'finance-3' => [
                'bg1' => '#451a03', 'bg2' => '#1c1917', 'accent' => '#f59e0b', 'accent2' => '#fde68a',
                'badge' => 'PASSIVE INCOME', 'title' => 'Dividend Growth Portfolio', 'icon' => 'DIV',
                'pattern' => 'waves'
            ],
            'finance-4' => [
                'bg1' => '#292524', 'bg2' => '#451a03', 'accent' => '#fbbf24', 'accent2' => '#f59e0b',
                'badge' => 'TRAVEL REWARDS', 'title' => 'Credit Card Points Mastery', 'icon' => 'PTS',
                'pattern' => 'grid'
            ],

            // Site 4: Health
            'health-1' => [
                'bg1' => '#2e1065', 'bg2' => '#0f172a', 'accent' => '#8b5cf6', 'accent2' => '#c084fc',
                'badge' => 'LONGEVITY &amp; SLEEP', 'title' => 'Circadian Sleep Architecture', 'icon' => 'BIO',
                'pattern' => 'waves'
            ],
            'health-2' => [
                'bg1' => '#3b0764', 'bg2' => '#1e1b4b', 'accent' => '#a855f7', 'accent2' => '#e9d5ff',
                'badge' => 'FASTING &amp; AUTOPHAGY', 'title' => 'Intermittent Fasting Trials', 'icon' => 'CEL',
                'pattern' => 'blocks'
            ],
            'health-3' => [
                'bg1' => '#4c1d95', 'bg2' => '#0f172a', 'accent' => '#c084fc', 'accent2' => '#8b5cf6',
                'badge' => 'NOOTROPICS', 'title' => 'Nootropics for Cognitive Focus', 'icon' => 'MED',
                'pattern' => 'neural'
            ],
            'health-4' => [
                'bg1' => '#1e1b4b', 'bg2' => '#2e1065', 'accent' => '#a855f7', 'accent2' => '#8b5cf6',
                'badge' => 'ZONE 2 CARDIO', 'title' => 'Aerobic Base &amp; Healthspan', 'icon' => 'FIT',
                'pattern' => 'chart'
            ],

            // Site 5: Cyber
            'cyber-1' => [
                'bg1' => '#1e1b4b', 'bg2' => '#020617', 'accent' => '#6366f1', 'accent2' => '#818cf8',
                'badge' => 'CYBERSECURITY', 'title' => 'Zero-Trust Architecture', 'icon' => 'ZTA',
                'pattern' => 'matrix'
            ],
            'cyber-2' => [
                'bg1' => '#0f172a', 'bg2' => '#1e1b4b', 'accent' => '#818cf8', 'accent2' => '#38bdf8',
                'badge' => 'ONLINE PRIVACY', 'title' => 'Ultimate Digital Privacy 2026', 'icon' => 'VPN',
                'pattern' => 'grid'
            ],
            'cyber-3' => [
                'bg1' => '#1e1b4b', 'bg2' => '#0f172a', 'accent' => '#6366f1', 'accent2' => '#a5b4fc',
                'badge' => 'THREAT DEFENSE', 'title' => 'Defending Against AI Phishing', 'icon' => 'SOC',
                'pattern' => 'matrix'
            ],
            'cyber-4' => [
                'bg1' => '#020617', 'bg2' => '#1e1b4b', 'accent' => '#818cf8', 'accent2' => '#6366f1',
                'badge' => 'SOVEREIGN CLOUD', 'title' => 'Self-Hosted Cloud Solutions', 'icon' => 'OPS',
                'pattern' => 'blocks'
            ],

            // Site 6: Marketing
            'marketing-1' => [
                'bg1' => '#4c0519', 'bg2' => '#0f172a', 'accent' => '#f43f5e', 'accent2' => '#fb7185',
                'badge' => 'AFFILIATE GROWTH', 'title' => 'Niche Affiliate Site Roadmap', 'icon' => 'GROW',
                'pattern' => 'chart'
            ],
            'marketing-2' => [
                'bg1' => '#881337', 'bg2' => '#1e293b', 'accent' => '#fb7185', 'accent2' => '#f43f5e',
                'badge' => 'E-COMMERCE FUNNELS', 'title' => 'High-Converting Sales Funnels', 'icon' => 'ECOM',
                'pattern' => 'waves'
            ],
            'marketing-3' => [
                'bg1' => '#4c0519', 'bg2' => '#1c1917', 'accent' => '#f43f5e', 'accent2' => '#fda4af',
                'badge' => 'SEO STRATEGIES', 'title' => 'E-E-A-T Modern SEO Tactics', 'icon' => 'SEO',
                'pattern' => 'grid'
            ],
            'marketing-4' => [
                'bg1' => '#1c1917', 'bg2' => '#4c0519', 'accent' => '#fb7185', 'accent2' => '#f43f5e',
                'badge' => 'MICRO-SAAS', 'title' => 'Building Scalable Micro-SaaS', 'icon' => 'SAAS',
                'pattern' => 'blocks'
            ],

            // Site 7: Gaming
            'gaming-1' => [
                'bg1' => '#083344', 'bg2' => '#0f172a', 'accent' => '#06b6d4', 'accent2' => '#22d3ee',
                'badge' => 'PC HARDWARE', 'title' => 'Ultimate 1440p Gaming Rig', 'icon' => 'RIG',
                'pattern' => 'grid'
            ],
            'gaming-2' => [
                'bg1' => '#164e63', 'bg2' => '#0f172a', 'accent' => '#22d3ee', 'accent2' => '#67e8f9',
                'badge' => 'GPU BENCHMARKS', 'title' => 'DLSS &amp; Ray Tracing Architecture', 'icon' => 'GPU',
                'pattern' => 'waves'
            ],
            'gaming-3' => [
                'bg1' => '#083344', 'bg2' => '#1e293b', 'accent' => '#06b6d4', 'accent2' => '#a5f3fc',
                'badge' => 'PERIPHERALS', 'title' => 'Magnetic Hall Effect Switches', 'icon' => 'KB',
                'pattern' => 'matrix'
            ],
            'gaming-4' => [
                'bg1' => '#0f172a', 'bg2' => '#083344', 'accent' => '#22d3ee', 'accent2' => '#06b6d4',
                'badge' => 'ESPORTS META', 'title' => 'Max FPS &amp; Latency Optimization', 'icon' => 'FPS',
                'pattern' => 'chart'
            ],
        ];

        foreach ($configs as $key => $c) {
            $svg = self::renderSvg($c);
            file_put_contents("{$dir}/{$key}.svg", $svg);
        }
    }

    private static function renderSvg(array $c): string
    {
        $bg1 = $c['bg1'];
        $bg2 = $c['bg2'];
        $accent = $c['accent'];
        $accent2 = $c['accent2'];
        $badge = $c['badge'];
        $title = $c['title'];
        $icon = htmlspecialchars($c['icon']);
        $pattern = $c['pattern'];

        $patternSvg = '';
        if ($pattern === 'neural') {
            $patternSvg = '
            <circle cx="850" cy="200" r="8" fill="' . $accent . '" opacity="0.8"/>
            <circle cx="1000" cy="150" r="6" fill="' . $accent2 . '" opacity="0.7"/>
            <circle cx="950" cy="320" r="10" fill="' . $accent . '" opacity="0.9"/>
            <circle cx="1080" cy="280" r="7" fill="' . $accent2 . '" opacity="0.6"/>
            <circle cx="820" cy="400" r="9" fill="' . $accent . '" opacity="0.7"/>
            <circle cx="1020" cy="450" r="8" fill="' . $accent2 . '" opacity="0.8"/>
            <line x1="850" y1="200" x2="1000" y2="150" stroke="' . $accent . '" stroke-width="2" opacity="0.4"/>
            <line x1="850" y1="200" x2="950" y2="320" stroke="' . $accent . '" stroke-width="2" opacity="0.4"/>
            <line x1="1000" y1="150" x2="1080" y2="280" stroke="' . $accent2 . '" stroke-width="2" opacity="0.4"/>
            <line x1="950" y1="320" x2="1080" y2="280" stroke="' . $accent . '" stroke-width="2" opacity="0.4"/>
            <line x1="950" y1="320" x2="820" y2="400" stroke="' . $accent2 . '" stroke-width="2" opacity="0.4"/>
            <line x1="950" y1="320" x2="1020" y2="450" stroke="' . $accent . '" stroke-width="2" opacity="0.4"/>
            <line x1="1080" y1="280" x2="1020" y2="450" stroke="' . $accent2 . '" stroke-width="2" opacity="0.4"/>
            ';
        } elseif ($pattern === 'chart') {
            $patternSvg = '
            <path d="M 750 480 Q 850 350 950 380 T 1120 180" fill="none" stroke="' . $accent . '" stroke-width="6" opacity="0.8"/>
            <path d="M 750 480 Q 850 350 950 380 T 1120 180 L 1120 550 L 750 550 Z" fill="url(#gradAccent)" opacity="0.15"/>
            <circle cx="1120" cy="180" r="10" fill="' . $accent2 . '"/>
            ';
        } elseif ($pattern === 'matrix' || $pattern === 'grid') {
            $patternSvg = '
            <defs>
                <pattern id="gridPattern" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M 40 0 L 0 0 0 40" fill="none" stroke="' . $accent . '" stroke-width="1" opacity="0.15"/>
                </pattern>
            </defs>
            <rect x="650" y="50" width="500" height="500" fill="url(#gridPattern)"/>
            <rect x="750" y="120" width="120" height="120" rx="20" fill="' . $accent . '" opacity="0.12" stroke="' . $accent . '" stroke-width="2"/>
            <rect x="920" y="240" width="140" height="140" rx="24" fill="' . $accent2 . '" opacity="0.15" stroke="' . $accent2 . '" stroke-width="2"/>
            ';
        } else {
            $patternSvg = '
            <circle cx="950" cy="300" r="180" fill="none" stroke="' . $accent . '" stroke-width="2" opacity="0.25"/>
            <circle cx="950" cy="300" r="120" fill="none" stroke="' . $accent2 . '" stroke-width="3" opacity="0.35"/>
            <circle cx="950" cy="300" r="60" fill="' . $accent . '" opacity="0.15"/>
            ';
        }

        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 630" width="1200" height="630">
    <defs>
        <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="{$bg1}"/>
            <stop offset="100%" stop-color="{$bg2}"/>
        </linearGradient>
        <linearGradient id="gradAccent" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="{$accent}" stop-opacity="0.8"/>
            <stop offset="100%" stop-color="{$accent2}" stop-opacity="0.0"/>
        </linearGradient>
        <radialGradient id="glowGrad" cx="80%" cy="30%" r="60%">
            <stop offset="0%" stop-color="{$accent}" stop-opacity="0.45"/>
            <stop offset="100%" stop-color="{$bg2}" stop-opacity="0"/>
        </radialGradient>
    </defs>

    <!-- Background -->
    <rect width="1200" height="630" fill="url(#bgGrad)"/>
    <circle cx="950" cy="250" r="350" fill="url(#glowGrad)"/>

    <!-- Decorative Pattern -->
    {$patternSvg}

    <!-- Glass Icon Container -->
    <g transform="translate(900, 220)">
        <rect x="-60" y="-60" width="120" height="120" rx="30" fill="{$bg2}" fill-opacity="0.7" stroke="{$accent}" stroke-width="2" stroke-opacity="0.6"/>
        <text x="0" y="14" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="34" font-weight="900" fill="{$accent2}" text-anchor="middle" letter-spacing="1">{$icon}</text>
    </g>

    <!-- Left Content -->
    <g transform="translate(80, 160)">
        <!-- Category Badge -->
        <rect x="0" y="0" width="240" height="38" rx="19" fill="{$accent}" fill-opacity="0.2" stroke="{$accent}" stroke-width="1.5"/>
        <text x="120" y="24" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="12" font-weight="800" fill="{$accent2}" text-anchor="middle" letter-spacing="2">{$badge}</text>

        <!-- Main Title -->
        <text x="0" y="100" font-family="Georgia, Cambria, serif" font-size="44" font-weight="bold" fill="#ffffff">
            <tspan x="0" dy="0">{$title}</tspan>
        </text>

        <!-- Subtitle / Meta -->
        <text x="0" y="170" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="16" font-weight="500" fill="#94a3b8">
            Expert Analysis &#8226; 2026 Strategic Blueprint &#8226; Verified Publication
        </text>

        <!-- Decorative bar -->
        <rect x="0" y="220" width="80" height="5" rx="2.5" fill="{$accent}"/>
    </g>
</svg>
SVG;
    }
}
