<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('home_service_summaries', function (Blueprint $table) {
            $table->string('icon', 32)->nullable();
        });

        foreach ([
            'seed/summary-organization.svg' => 'archive',
            'seed/summary-research.svg' => 'search',
            'seed/summary-digitization.svg' => 'scan',
            'seed/service-advice.svg' => 'book-open',
        ] as $path => $icon) {
            DB::table('home_service_summaries')->where('icon_path', $path)->update(['icon' => $icon]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_service_summaries', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
