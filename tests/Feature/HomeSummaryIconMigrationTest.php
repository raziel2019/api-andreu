<?php

namespace Tests\Feature;

use App\Models\HomePage;
use Database\Seeders\ApprovedContentSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeSummaryIconMigrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_migration_backfills_approved_paths_preserves_custom_icons_and_rolls_back_without_losing_content(): void
    {
        Storage::fake('public');
        $this->seed(ApprovedContentSeeder::class);
        $home = HomePage::findOrFail(1);
        $migration = require database_path('migrations/2026_10_12_080514_add_icon_to_home_service_summaries_table.php');
        $migration->down();
        DB::table('home_service_summaries')->where('slug', 'research')->update(['icon_path' => 'cms/custom.png']);
        $before = DB::table('home_service_summaries')->orderBy('id')->get()->map(fn (object $summary): array => (array) $summary)->all();

        $migration->up();

        $this->assertDatabaseHas('home_service_summaries', ['slug' => 'organization', 'icon' => 'archive']);
        $this->assertDatabaseHas('home_service_summaries', ['slug' => 'digitization', 'icon' => 'scan']);
        $this->assertDatabaseHas('home_service_summaries', ['slug' => 'advice', 'icon' => 'book-open']);
        $this->assertDatabaseHas('home_service_summaries', ['slug' => 'research', 'icon' => null, 'icon_path' => 'cms/custom.png']);
        $this->assertSame($before, DB::table('home_service_summaries')->orderBy('id')->get()->map(function (object $summary): array {
            $attributes = (array) $summary;
            unset($attributes['icon']);

            return $attributes;
        })->all());
        $this->assertSame($home->getAttributes(), $home->fresh()->getAttributes());

        $migration->down();

        $this->assertFalse(Schema::hasColumn('home_service_summaries', 'icon'));
        $this->assertSame($before, DB::table('home_service_summaries')->orderBy('id')->get()->map(fn (object $summary): array => (array) $summary)->all());
        $migration->up();
    }
}
