<?php

namespace Tests\Unit;

use App\Models\AppSetting;
use App\Services\BulkSmsDhakaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkSmsDhakaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_format_number_normalizes_bangladeshi_numbers(): void
    {
        $this->assertEquals('01712345678', BulkSmsDhakaService::formatNumber('01712345678'));
        $this->assertEquals('01712345678', BulkSmsDhakaService::formatNumber('+8801712345678'));
        $this->assertEquals('01712345678', BulkSmsDhakaService::formatNumber('8801712345678'));
        $this->assertEquals('01712345678', BulkSmsDhakaService::formatNumber('01712-345678'));
        $this->assertEquals('01712345678', BulkSmsDhakaService::formatNumber('+88 01712 345 678'));
    }

    public function test_is_configured_returns_false_when_no_api_key(): void
    {
        putenv('BULKSMSDHAKA_API_KEY');
        unset($_ENV['BULKSMSDHAKA_API_KEY']);
        config(['services.bulksmsdhaka.api_key' => null]);

        $service = new BulkSmsDhakaService('');
        $this->assertFalse($service->isConfigured());

        $result = $service->sendSms('01712345678', 'Test message');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('API key is not configured', $result['message']);
    }

    public function test_send_sms_validates_phone_number(): void
    {
        $service = new BulkSmsDhakaService('test_api_key_123');
        $this->assertTrue($service->isConfigured());

        $result = $service->sendSms('12345', 'Test message');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid Bangladeshi phone number', $result['message']);
    }

    public function test_service_resolves_key_from_app_settings(): void
    {
        AppSetting::setByKey('bulksmsdhaka_api_key', 'setting_key_xyz');

        $service = new BulkSmsDhakaService();
        $this->assertTrue($service->isConfigured());
        $this->assertEquals('setting_key_xyz', $service->getApiKey());
    }
}
