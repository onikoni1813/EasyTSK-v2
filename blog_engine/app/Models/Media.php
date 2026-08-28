<?php

namespace App\Models;

use App\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory, BelongsToSite;

    protected $table = 'be_media';

    protected $fillable = [
        'site_id',
        'name',
        'file_path',
        'file_size',
        'mime_type',
        'alt_text',
    ];
}
