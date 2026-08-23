<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\SiteType;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToolEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_tool_category_and_tool(): void
    {
        $adminPath = config('app.admin_path', 'secret-panel');
        $admin = User::factory()->create(['role' => 'admin']);

        // Create Tool Category
        $response = $this->actingAs($admin)->post("/{$adminPath}/tool-categories", [
            'name' => 'Text Processing',
            'slug' => 'text-processing',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tool_categories', ['name' => 'Text Processing']);

        $category = ToolCategory::first();

        // Create Master Tool
        $response = $this->actingAs($admin)->post("/{$adminPath}/tools", [
            'name' => 'Word Counter',
            'slug' => 'word-counter',
            'category_id' => $category->id,
            'component_name' => 'WordCounter',
            'execution_type' => 'client_side',
            'is_active' => true,
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tools', ['slug' => 'word-counter', 'component_name' => 'WordCounter']);
    }

    public function test_admin_can_attach_and_detach_tool_to_site(): void
    {
        $adminPath = config('app.admin_path', 'secret-panel');
        $admin = User::factory()->create(['role' => 'admin']);
        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Tools Hub',
            'slug' => 'tools-hub',
            'subdomain' => 'tools-hub',
            'primary_domain' => 'tools-hub.com',
            'status' => 'active',
        ]);

        $tool = Tool::create([
            'name' => 'Word Counter',
            'slug' => 'word-counter',
            'component_name' => 'WordCounter',
            'execution_type' => 'client_side',
            'is_active' => true,
        ]);

        // Attach
        $response = $this->actingAs($admin)->post("/{$adminPath}/sites/{$site->id}/tools/{$tool->id}/toggle");
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('site_tools', ['site_id' => $site->id, 'tool_id' => $tool->id]);

        // Detach
        $response = $this->actingAs($admin)->post("/{$adminPath}/sites/{$site->id}/tools/{$tool->id}/toggle");
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('site_tools', ['site_id' => $site->id, 'tool_id' => $tool->id]);
    }

    public function test_external_site_renders_attached_tool(): void
    {
        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Tools Hub',
            'slug' => 'tools-hub',
            'subdomain' => 'tools-hub',
            'primary_domain' => 'tools-hub.com',
            'status' => 'active',
        ]);

        $tool = Tool::create([
            'name' => 'Word Counter',
            'slug' => 'word-counter',
            'component_name' => 'WordCounter',
            'execution_type' => 'client_side',
            'is_active' => true,
        ]);

        $site->tools()->attach($tool->id);

        app()->instance('current_site', $site);

        $response = $this->get('/tools/word-counter');
        $response->assertStatus(200);
    }

    public function test_external_site_returns_404_for_unattached_tool(): void
    {
        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Tools Hub',
            'slug' => 'tools-hub',
            'subdomain' => 'tools-hub',
            'primary_domain' => 'tools-hub.com',
            'status' => 'active',
        ]);

        $tool = Tool::create([
            'name' => 'Word Counter',
            'slug' => 'word-counter',
            'component_name' => 'WordCounter',
            'execution_type' => 'client_side',
            'is_active' => true,
        ]);

        // Tool exists in registry, but NOT attached to this site
        app()->instance('current_site', $site);

        $response = $this->get('/tools/word-counter');
        $response->assertStatus(404);
    }
}
