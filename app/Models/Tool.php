<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tool extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'icon',
        'summary',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'component_name',
        'execution_type',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ToolCategory::class, 'category_id');
    }

    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, 'site_tools')
            ->withPivot('is_featured', 'custom_title')
            ->withTimestamps();
    }
}
