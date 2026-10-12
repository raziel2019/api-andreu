<?php

namespace Tests\Feature;

use App\Models\ContactPage;
use App\Models\ContactServiceOption;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContactSmtpIntegrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_local_mailhog_accepts_contact_mail_over_real_smtp(): void
    {
        if (getenv('CONTACT_SMTP_SMOKE_TEST') !== '1') {
            $this->markTestSkipped('Opt-in real SMTP check against the local Docker MailHog capture server.');
        }
        config([
            'services.turnstile.secret_key' => 'isolated-test-secret',
            'mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'mailhog', 'mail.mailers.smtp.port' => 1025,
            'mail.mailers.smtp.scheme' => 'smtp', 'mail.mailers.smtp.url' => null,
            'mail.mailers.smtp.username' => null, 'mail.mailers.smtp.password' => null,
            'mail.mailers.smtp.require_tls' => false,
            'mail.from.address' => 'info@andreugurrera.cat', 'mail.from.name' => 'Andreu Gurrera',
        ]);
        $page = ContactPage::factory()->create(['id' => 1]);
        ContactServiceOption::factory()->for($page)->create(['slug' => 'smtp-smoke']);
        Http::preventStrayRequests();
        Http::fake(['https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true])]);

        $this->postJson('/api/v1/contact', [
            'name' => 'Prova SMTP local', 'email' => 'contact-smoke@example.test', 'services' => ['smtp-smoke'],
            'message' => 'CONTACT_SMTP_SMOKE_TEST: captura local, sense lliurament extern.', 'locale' => 'ca', 'token' => 'isolated-fixture-token',
        ])->assertOk()->assertJsonPath('code', 'accepted');

        Http::assertSentCount(1);
    }
}
