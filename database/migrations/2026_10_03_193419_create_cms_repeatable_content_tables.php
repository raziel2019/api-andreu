<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_service_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_page_id')->constrained()->cascadeOnDelete();
            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->string('icon_path', 255)->nullable();
            $table->string('slug', 100)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->index(['home_page_id', 'sort_order', 'id']);
            $table->timestamps();
        });

        Schema::create('about_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('about_page_id')->constrained()->cascadeOnDelete();
            $table->json('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->index(['about_page_id', 'sort_order', 'id']);
            $table->timestamps();
        });

        Schema::create('working_method_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('about_page_id')->constrained()->cascadeOnDelete();
            $table->json('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->index(['about_page_id', 'sort_order', 'id']);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->json('title')->nullable();
            $table->json('subtitle')->nullable();
            $table->json('description')->nullable();
            $table->string('icon_path', 255)->nullable();
            $table->string('slug', 100)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->index(['active', 'sort_order', 'id']);
            $table->timestamps();
        });

        Schema::create('service_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->json('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->index(['service_id', 'sort_order', 'id']);
            $table->timestamps();
        });

        Schema::create('contact_service_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_page_id')->constrained()->cascadeOnDelete();
            $table->json('label')->nullable();
            $table->string('slug', 100)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->index(['active', 'sort_order', 'id']);
            $table->index(['contact_page_id', 'sort_order', 'id']);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('contact_service_options');
        Schema::dropIfExists('service_features');
        Schema::dropIfExists('services');
        Schema::dropIfExists('working_method_steps');
        Schema::dropIfExists('about_benefits');
        Schema::dropIfExists('home_service_summaries');
    }
};
