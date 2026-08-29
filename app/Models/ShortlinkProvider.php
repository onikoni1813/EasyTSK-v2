<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShortlinkProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'api_url',
        'api_key',
        'daily_limit',
        'is_active',
        'icon',
    ];

    protected $casts = [
        'daily_limit' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * All 12 Official Shortlink Providers from guidelines
     */
    public const PRESETS = [
        'shrinkme' => [
            'name' => 'ShrinkMe.io',
            'api_url' => 'https://shrinkme.io/api',
            'icon' => '🔗',
            'default_key' => 'd4010139b013fb1e1cf8260ace15c49e985fab5d',
            'type' => 'adlinkfly',
        ],
        'exe' => [
            'name' => 'Exe.io',
            'api_url' => 'https://exe.io/api',
            'icon' => '⚡',
            'default_key' => '7f51aa2a6c67065832e859fbeeb93415a7e83112',
            'type' => 'adlinkfly',
        ],
        'gplinks' => [
            'name' => 'GPLinks',
            'api_url' => 'https://api.gplinks.com/api',
            'icon' => '💎',
            'default_key' => '6411a5a0ce3f100e690125a539e2d33217ec2fe4',
            'type' => 'adlinkfly',
        ],
        'droplink' => [
            'name' => 'Droplink.co',
            'api_url' => 'https://droplink.co/api',
            'icon' => '💧',
            'default_key' => '46cf4328855d079b1e0a2b45df82fc5a7d5048f2',
            'type' => 'adlinkfly',
        ],
        'cuty' => [
            'name' => 'Cuty.io',
            'api_url' => 'https://cuty.io/api',
            'icon' => '✂️',
            'default_key' => 'eaefa6288147fdc378ad1fa8f3544d482f10721e',
            'type' => 'adlinkfly',
        ],
        'clksh' => [
            'name' => 'Clk.sh',
            'api_url' => 'https://clk.sh/api',
            'icon' => '⌛',
            'default_key' => '42b6acff8da066e5f8338de8f3d4eb94133d22d7',
            'type' => 'adlinkfly',
        ],
        'cutwin' => [
            'name' => 'CutWin',
            'api_url' => 'https://cutw.in/api',
            'icon' => '✂️',
            'default_key' => '208ccebdd7bed98944b2a3d66e3085066822f3ea',
            'type' => 'adlinkfly',
        ],
        'fclc' => [
            'name' => 'Fc.lc',
            'api_url' => 'https://fc.lc/api',
            'icon' => '🔥',
            'default_key' => 'f684596598b4c257c5c61f044571c1a23c0039ae',
            'type' => 'adlinkfly',
        ],
        'kutli' => [
            'name' => 'Kut.li',
            'api_url' => 'https://kut.li/api',
            'icon' => '📍',
            'default_key' => 'acedfffaa0d181f803fee691f6a733a945e50d9e',
            'type' => 'adlinkfly',
        ],
        'shrinkearn' => [
            'name' => 'ShrinkEarn',
            'api_url' => 'https://shrinkearn.com/api',
            'icon' => '💰',
            'default_key' => '2da410177414156a42d37ae01fd9020dca1eeba8',
            'type' => 'adlinkfly',
        ],
        'shrtfly' => [
            'name' => 'ShrtFly.com',
            'api_url' => 'https://shrtfly.com/api',
            'icon' => '🚀',
            'default_key' => 'a544689d14efd1e28111b5455f24b90a',
            'type' => 'shrtfly',
        ],
        'adfocus' => [
            'name' => 'AdFoc.us',
            'api_url' => 'http://adfoc.us/api/',
            'icon' => '🎯',
            'default_key' => 'e172743ea8084e90c2dc17231eb274aa',
            'type' => 'adfocus',
        ],
        'custom' => [
            'name' => 'Custom Shortener',
            'api_url' => '',
            'icon' => '🌐',
            'default_key' => '',
            'type' => 'adlinkfly',
        ],
    ];
}
