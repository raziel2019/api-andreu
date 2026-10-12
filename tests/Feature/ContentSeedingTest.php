<?php

namespace Tests\Feature;

use App\Models\HomePage;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\ApprovedContentSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentSeedingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_content_seeding_is_idempotent_and_preserves_editor_changes(): void
    {
        Storage::fake('public');
        $this->seed(ApprovedContentSeeder::class);
        HomePage::findOrFail(1)->update(['hero_title' => ['ca' => 'Canvi editorial', 'es' => 'Cambio editorial']]);

        $this->seed(ApprovedContentSeeder::class);

        $this->assertDatabaseCount('home_pages', 1);
        $this->assertDatabaseCount('services', 4);
        $this->assertDatabaseCount('service_features', 12);
        $this->assertDatabaseCount('about_benefits', 3);
        $this->assertDatabaseCount('working_method_steps', 3);
        $this->assertDatabaseCount('contact_service_options', 6);
        $this->assertSame('Canvi editorial', HomePage::findOrFail(1)->hero_title['ca']);
        Storage::disk('public')->assertExists(['seed/home-hero.png', 'seed/about.png', 'seed/documents.png', 'seed/logo.svg']);
    }

    public function test_home_summary_icons_are_seeded_and_reseeding_preserves_choices_and_removed_summaries(): void
    {
        Storage::fake('public');
        $this->seed(ApprovedContentSeeder::class);
        $home = HomePage::with('serviceSummaries')->findOrFail(1);
        $this->assertSame(['organization' => 'archive', 'digitization' => 'scan', 'research' => 'search', 'advice' => 'book-open'], $home->serviceSummaries->pluck('icon', 'slug')->all());
        $home->serviceSummaries[0]->update(['icon' => 'search', 'icon_path' => 'seed/summary-research.svg', 'sort_order' => 9, 'title' => ['ca' => 'Arxius', 'es' => 'Archivos']]);
        $home->serviceSummaries[3]->delete();
        $before = $home->fresh('serviceSummaries')->serviceSummaries->toArray();

        $this->seed(ApprovedContentSeeder::class);
        $this->seed(ApprovedContentSeeder::class);

        $this->assertSame($before, $home->fresh('serviceSummaries')->serviceSummaries->toArray());
        $this->assertDatabaseCount('home_service_summaries', 3);
    }

    public function test_environment_admin_is_seeded_with_a_hashed_password_and_no_password_reset_on_reseed(): void
    {
        config(['cms.admin_email' => 'admin@example.test', 'cms.admin_password' => 'local-password-123']);

        $this->seed(AdminUserSeeder::class);

        $user = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('local-password-123', $user->password));
        config(['cms.admin_password' => 'replacement-password-123']);
        $this->seed(AdminUserSeeder::class);
        $this->assertTrue(Hash::check('local-password-123', $user->fresh()->password));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_no_admin_or_demo_user_is_created_without_env_credentials(): void
    {
        config(['cms.admin_email' => null, 'cms.admin_password' => null]);

        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_development_admin_seeding_is_disabled_in_production(): void
    {
        config(['cms.admin_email' => 'admin@example.test', 'cms.admin_password' => 'local-password-123']);
        User::query()->count();
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--class' => AdminUserSeeder::class, '--force' => true])->assertExitCode(0);

        $this->assertDatabaseCount('users', 0);
    }
}
