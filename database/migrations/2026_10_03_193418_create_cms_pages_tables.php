<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->json('location')->nullable();
            $table->json('callout_title')->nullable();
            $table->json('callout_content')->nullable();
            $table->json('callout_label')->nullable();
            $table->json('footer_description')->nullable();
            $table->json('footer_cta_label')->nullable();
            $table->json('footer_copyright')->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->string('linkedin_url', 2048)->nullable();
            $table->string('instagram_url', 2048)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->string('callout_destination', 2048)->nullable();
            $table->string('footer_cta_destination', 2048)->nullable();
            $table->timestamps();
        });

        Schema::create('home_pages', function (Blueprint $table) {
            $table->id();
            $table->json('hero_title')->nullable();
            $table->json('hero_content')->nullable();
            $table->json('hero_eyebrow')->nullable();
            $table->json('hero_image_alt')->nullable();
            $table->json('hero_image_caption')->nullable();
            $table->json('hero_cta_label')->nullable();
            $table->json('services_heading')->nullable();
            $table->json('services_introduction')->nullable();
            $table->json('stories_heading')->nullable();
            $table->json('stories_introduction')->nullable();
            $table->json('portfolio_title')->nullable();
            $table->json('portfolio_image_alt')->nullable();
            $table->json('portfolio_cta_label')->nullable();
            $table->json('cta_title')->nullable();
            $table->json('cta_content')->nullable();
            $table->json('cta_label')->nullable();
            $table->string('hero_image_path', 255)->nullable();
            $table->string('hero_cta_destination', 2048)->nullable();
            $table->string('portfolio_image_path', 255)->nullable();
            $table->string('portfolio_cta_destination', 2048)->nullable();
            $table->string('cta_destination', 2048)->nullable();
            $table->timestamps();
        });

        Schema::create('about_pages', function (Blueprint $table) {
            $table->id();
            $table->json('title')->nullable();
            $table->json('introduction')->nullable();
            $table->json('image_alt')->nullable();
            $table->json('image_caption')->nullable();
            $table->json('benefits_heading')->nullable();
            $table->json('working_method_heading')->nullable();
            $table->json('working_method_introduction')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('service_pages', function (Blueprint $table) {
            $table->id();
            $table->json('title')->nullable();
            $table->json('subtitle')->nullable();
            $table->json('lead')->nullable();
            $table->json('description')->nullable();
            $table->json('heading')->nullable();
            $table->json('image_alt')->nullable();
            $table->json('cta_label')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('cta_destination', 2048)->nullable();
            $table->timestamps();
        });

        Schema::create('portfolio_pages', function (Blueprint $table) {
            $table->id();
            $table->json('title')->nullable();
            $table->json('construction_label')->nullable();
            $table->json('message')->nullable();
            $table->json('cta_label')->nullable();
            $table->string('cta_destination', 2048)->nullable();
            $table->string('linkedin_destination', 2048)->nullable();
            $table->boolean('under_construction')->default(true);
            $table->timestamps();
        });

        Schema::create('contact_pages', function (Blueprint $table) {
            $table->id();
            $table->json('title')->nullable();
            $table->json('introduction')->nullable();
            $table->json('content')->nullable();
            $table->json('conversation')->nullable();
            $table->json('service_legend')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('contact_pages');
        Schema::dropIfExists('portfolio_pages');
        Schema::dropIfExists('service_pages');
        Schema::dropIfExists('about_pages');
        Schema::dropIfExists('home_pages');
        Schema::dropIfExists('site_settings');
    }
};
