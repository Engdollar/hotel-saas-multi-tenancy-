<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_presets', function (Blueprint $table) {
            $table->id();
            $table->string('type', 60);
            $table->string('slug');
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('content');
            $table->timestamps();

            $table->unique(['type', 'slug']);
            $table->index(['type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_presets');
    }
};
