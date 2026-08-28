<?php

namespace App\Models;

use App\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RootFile extends Model
{
    use HasFactory, BelongsToSite;

    protected $table = 'be_root_files';

    protected $fillable = [
        'site_id',
        'filename',
        'content',
        'mime_type',
    ];
}
