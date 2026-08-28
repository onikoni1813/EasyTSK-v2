<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Author extends Model
{
    use HasFactory;

    protected $table = 'be_authors';

    protected $fillable = [
        'site_id',
        'name',
        'slug',
        'email',
        'bio',
        'avatar',
        'social_links',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }
}
