<?php

namespace App\Services;

use App\Models\AdPlacement;
use Illuminate\Support\Facades\Cache;

class AdEngine
{
    public function __construct(protected SiteContext $siteContext)
    {
    }

    /**
     * Get all active ad placements for the current site (cached).
     */
    public function getPlacements(): array
    {
        $site = $this->siteContext->get();
        if (!$site) {
            return [];
        }

        return Cache::remember("site_{$site->id}_ads", 300, function () use ($site) {
            return AdPlacement::where('site_id', $site->id)
                ->where('is_active', true)
                ->get()
                ->keyBy('placement_slot')
                ->all();
        });
    }

    /**
     * Check if an active ad exists for a specific slot.
     */
    public function has(string $slot): bool
    {
        $placements = $this->getPlacements();
        return isset($placements[$slot]) && !empty($placements[$slot]->ad_code);
    }

    /**
     * Render ad HTML/Script for a specific slot.
     */
    public function render(string $slot, string $wrapperClass = 'my-4 text-center ad-container'): string
    {
        $placements = $this->getPlacements();
        if (!isset($placements[$slot]) || empty($placements[$slot]->ad_code)) {
            return '';
        }

        $code = $placements[$slot]->ad_code;
        return "<div class=\"{$wrapperClass} ad-slot-{$slot}\">{$code}</div>";
    }

    /**
     * Smartly inject ads into post HTML content after specific paragraphs (e.g. p2 and p5).
     */
    public function injectInContent(string $htmlContent): string
    {
        $placements = $this->getPlacements();
        $p2Ad = isset($placements['in_content_p2']) && !empty($placements['in_content_p2']->ad_code) 
            ? "<div class=\"my-6 text-center ad-in-content ad-p2\">" . $placements['in_content_p2']->ad_code . "</div>" 
            : '';
        $p5Ad = isset($placements['in_content_p5']) && !empty($placements['in_content_p5']->ad_code) 
            ? "<div class=\"my-6 text-center ad-in-content ad-p5\">" . $placements['in_content_p5']->ad_code . "</div>" 
            : '';

        if (empty($p2Ad) && empty($p5Ad)) {
            return $htmlContent;
        }

        $paragraphs = explode('</p>', $htmlContent);
        $total = count($paragraphs);
        $output = '';

        for ($i = 0; $i < $total; $i++) {
            $chunk = $paragraphs[$i];
            if (empty(trim($chunk))) {
                continue;
            }

            $output .= $chunk . '</p>';

            // Inject after paragraph 2
            if ($i === 1 && !empty($p2Ad)) {
                $output .= "\n" . $p2Ad . "\n";
            }

            // Inject after paragraph 5
            if ($i === 4 && !empty($p5Ad)) {
                $output .= "\n" . $p5Ad . "\n";
            }
        }

        return $output;
    }

    /**
     * Clear ad cache for a site.
     */
    public function clearCache(int $siteId): void
    {
        Cache::forget("site_{$siteId}_ads");
    }
}
