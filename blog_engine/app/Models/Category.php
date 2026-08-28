<?php

namespace App\Models;

use App\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory, BelongsToSite;

    protected $table = 'be_categories';

    protected $fillable = [
        'site_id',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'sort_order',
    ];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'be_post_category', 'category_id', 'post_id');
    }
}
