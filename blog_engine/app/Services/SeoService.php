<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Site;
use App\Models\SitePage;

class SeoService
{
    public function __construct(protected SiteContext $siteContext)
    {
    }

    /**
     * Build SEO Meta data array for head rendering.
     */
    public function getMetadata(?Post $post = null, ?Category $category = null, ?SitePage $page = null): array
    {
        $site = $this->siteContext->get();
        $siteName = $site?->name ?? config('app.name', 'Blog');
        $siteTagline = $site?->tagline ?? 'Insights, Tutorials & Trends';
        $siteDesc = $site?->description ?? 'Discover latest updates and trending articles.';

        if ($post) {
            $title = ($post->meta_title ?: $post->title) . ' - ' . $siteName;
            $description = $post->meta_description ?: ($post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 160));
            $canonical = $post->canonical_url ?: url('/' . $post->slug);
            $ogImage = $post->featured_image ? url($post->featured_image) : ($site?->logo ? url($site->logo) : null);
            $type = 'article';
        } elseif ($category) {
            $title = ($category->meta_title ?: $category->name . ' Archives') . ' - ' . $siteName;
            $description = $category->meta_description ?: "Explore all articles filed under {$category->name} on {$siteName}.";
            $canonical = url('/category/' . $category->slug);
            $ogImage = $site?->logo ? url($site->logo) : null;
            $type = 'website';
        } elseif ($page) {
            $title = ($page->meta_title ?: $page->title) . ' - ' . $siteName;
            $description = $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($page->content), 160);
            $canonical = url('/page/' . $page->slug);
            $ogImage = $site?->logo ? url($site->logo) : null;
            $type = 'website';
        } else {
            // Homepage / Default
            $defaults = $site?->seo_defaults ?? [];
            $title = !empty($defaults['meta_title']) ? $defaults['meta_title'] . ' - ' . $siteName : "{$siteName} - {$siteTagline}";
            $description = !empty($defaults['meta_description']) ? $defaults['meta_description'] : $siteDesc;
            $canonical = url('/');
            $ogImage = $site?->logo ? url($site->logo) : null;
            $type = 'website';
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'og_image' => $ogImage,
            'og_type' => $type,
            'site_name' => $siteName,
            'favicon' => $site?->favicon ? url($site->favicon) : null,
        ];
    }

    /**
     * Generate JSON-LD Schema markup.
     */
    public function generateSchema(?Post $post = null): ?string
    {
        $site = $this->siteContext->get();
        if (!$site) {
            return null;
        }

        if ($post) {
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => $post->schema_type ?: 'BlogPosting',
                'headline' => $post->title,
                'description' => $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 160),
                'datePublished' => $post->published_at?->toIso8601String(),
                'dateModified' => $post->updated_at?->toIso8601String(),
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id' => url('/' . $post->slug),
                ],
                'author' => [
                    '@type' => 'Person',
                    'name' => $post->author?->name ?? $site->name,
                ],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => $site->name,
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => $site->logo ? url($site->logo) : '',
                    ],
                ],
            ];

            if ($post->featured_image) {
                $schema['image'] = [url($post->featured_image)];
            }

            return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        // Default Organization Schema
        $orgSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $site->name,
            'url' => url('/'),
            'description' => $site->description,
        ];
        if ($site->logo) {
            $orgSchema['logo'] = url($site->logo);
        }

        return json_encode($orgSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
