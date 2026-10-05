<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['home_pages', 'about_pages', 'service_pages', 'portfolio_pages', 'contact_pages'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->boolean('active')->default(true);
            });
        }
    }

    public function down(): void
    {
        foreach (['home_pages', 'about_pages', 'service_pages', 'portfolio_pages', 'contact_pages'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('active');
            });
        }
    }
};
