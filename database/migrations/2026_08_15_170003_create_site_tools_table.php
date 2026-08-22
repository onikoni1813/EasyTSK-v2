<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->foreignId('tool_id')->constrained('tools')->onDelete('cascade');
            $table->boolean('is_featured')->default(false);
            $table->string('custom_title')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'tool_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_tools');
    }
};
