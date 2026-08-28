<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('be_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->index();
            $table->string('subdomain')->unique()->index();
            $table->string('niche')->nullable();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('theme_color')->default('#2563eb');
            $table->string('theme_layout')->default('modern');
            $table->json('seo_defaults')->nullable();
            $table->json('social_links')->nullable();
            $table->mediumText('header_scripts')->nullable();
            $table->mediumText('footer_scripts')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('be_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained('be_sites')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('email')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->json('social_links')->nullable();
            $table->timestamps();
        });

        Schema::create('be_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('be_sites')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['site_id', 'slug']);
        });

        Schema::create('be_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('be_sites')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['site_id', 'slug']);
        });

        Schema::create('be_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('be_sites')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('be_authors')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->enum('status', ['draft', 'published', 'scheduled'])->default('draft');
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->unsignedSmallInteger('reading_time')->default(3);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('schema_type')->default('Article');
            $table->timestamps();

            $table->unique(['site_id', 'slug']);
            $table->index(['site_id', 'status', 'published_at']);
        });

        Schema::create('be_post_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('be_posts')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('be_categories')->cascadeOnDelete();
            $table->unique(['post_id', 'category_id']);
        });

        Schema::create('be_post_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('be_posts')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('be_tags')->cascadeOnDelete();
            $table->unique(['post_id', 'tag_id']);
        });

        Schema::create('be_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('be_sites')->cascadeOnDelete();
            $table->string('name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type')->nullable();
            $table->string('alt_text')->nullable();
            $table->timestamps();
        });

        Schema::create('be_ad_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('be_sites')->cascadeOnDelete();
            $table->string('network')->default('custom'); // adsterra, monetag, adsense, custom
            $table->string('placement_slot'); // header, before_content, in_content_p2, in_content_p5, after_content, sidebar_top, sidebar_sticky, footer, popunder, direct_link, native_banner
            $table->string('title')->nullable();
            $table->mediumText('ad_code');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['site_id', 'placement_slot', 'is_active']);
        });

        Schema::create('be_site_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('be_sites')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('content');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->unique(['site_id', 'slug']);
        });

        Schema::create('be_page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('be_sites')->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained('be_posts')->nullOnDelete();
            $table->string('path');
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_type', 20)->default('desktop');
            $table->string('referer')->nullable();
            $table->timestamp('visited_at')->useCurrent();

            $table->index(['site_id', 'visited_at']);
        });

        Schema::create('be_daily_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('be_sites')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('page_views')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->timestamps();

            $table->unique(['site_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('be_daily_analytics');
        Schema::dropIfExists('be_page_views');
        Schema::dropIfExists('be_site_pages');
        Schema::dropIfExists('be_ad_placements');
        Schema::dropIfExists('be_media');
        Schema::dropIfExists('be_post_tag');
        Schema::dropIfExists('be_post_category');
        Schema::dropIfExists('be_posts');
        Schema::dropIfExists('be_tags');
        Schema::dropIfExists('be_categories');
        Schema::dropIfExists('be_authors');
        Schema::dropIfExists('be_sites');
    }
};
