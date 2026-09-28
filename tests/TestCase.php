<?php

namespace Tests;

use App\Models\AppSetting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AppSetting::flushCache();
    }

    protected function tearDown(): void
    {
        AppSetting::flushCache();
        parent::tearDown();
    }
}
