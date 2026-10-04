<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('legal_name', 255)->nullable();
            $table->string('tax_identifier', 255)->nullable();
            $table->string('postal_address', 2000)->nullable();
            $table->string('privacy_contact_email', 255)->nullable();
            $table->string('professional_registration', 2000)->nullable();
            $table->string('data_controller_name', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['legal_name', 'tax_identifier', 'postal_address', 'privacy_contact_email', 'professional_registration', 'data_controller_name']);
        });
    }
};
