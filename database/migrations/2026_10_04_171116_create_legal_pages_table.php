<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_pages', function (Blueprint $table): void {
            $table->id();
            $table->enum('type', ['legal_notice', 'privacy_policy', 'cookie_policy'])->unique();
            $table->json('title');
            $table->json('body')->nullable();
            $table->boolean('active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_pages');
    }
};
