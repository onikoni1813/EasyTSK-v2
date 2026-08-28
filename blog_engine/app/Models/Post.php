<?php

namespace App\Models;

use App\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    use HasFactory, BelongsToSite;

    protected $table = 'be_posts';

    protected $fillable = [
        'site_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'fixed_secret_code',
        'featured_image',
        'status',
        'published_at',
        'views_count',
        'is_featured',
        'is_trending',
        'reading_time',
        'meta_title',
        'meta_description',
        'canonical_url',
        'schema_type',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'views_count' => 'integer',
        'reading_time' => 'integer',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'be_post_category', 'post_id', 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'be_post_tag', 'post_id', 'tag_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->published()->where('is_featured', true);
    }

    public function scopeTrending(Builder $query): Builder
    {
        return $query->published()->where('is_trending', true);
    }

    public function getEstimatedReadingTimeAttribute(): int
    {
        if ($this->reading_time > 0) {
            return $this->reading_time;
        }
        $words = str_word_count(strip_tags($this->content));
        return max(1, (int) ceil($words / 200));
    }
}
