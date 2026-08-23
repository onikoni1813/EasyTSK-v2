<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeployWebhookTest extends TestCase
{
    use RefreshDatabase;
    public function test_deploy_webhook_rejects_unauthorized_request()
    {
        $response = $this->postJson('/api/deploy-webhook', [
            'ref' => 'refs/heads/main',
        ]);

        $response->assertStatus(403);
    }

    public function test_deploy_webhook_accepts_valid_secret_query_parameter()
    {
        $secret = config('services.github.webhook_secret', 'easytsk_secure_deploy_key_2026');

        $response = $this->getJson("/api/deploy-webhook?secret={$secret}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_deploy_webhook_handles_github_ping_event()
    {
        $secret = config('services.github.webhook_secret', 'easytsk_secure_deploy_key_2026');
        $payload = json_encode(['zen' => 'Keep it logically awesome.']);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $response = $this->call(
            'POST',
            '/api/deploy-webhook',
            [],
            [],
            [],
            [
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
                'HTTP_X_GITHUB_EVENT'       => 'ping',
                'CONTENT_TYPE'              => 'application/json',
            ],
            $payload
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Pong! Webhook connection verified successfully.',
        ]);
    }
}
