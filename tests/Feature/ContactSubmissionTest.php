<?php

namespace Tests\Feature;

use App\Mail\ContactSubmission;
use App\Models\ContactPage;
use App\Models\ContactServiceOption;
use App\Models\SiteSetting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Testing\Fakes\MailFake;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class ContactSubmissionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const SITEVERIFY = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.turnstile.secret_key' => 'server-only-secret', 'mail.default' => 'smtp', 'mail.from.address' => 'info@andreugurrera.cat', 'mail.from.name' => 'Andreu Gurrera']);
        Http::preventStrayRequests();
    }

    private function payload(array $overrides = []): array
    {
        return [...['name' => 'Visitant', 'email' => 'visitor@example.test', 'services' => ['arxius'], 'message' => 'Voldria informació.', 'locale' => 'ca', 'token' => 'single-use-token'], ...$overrides];
    }

    private function service(): ContactServiceOption
    {
        $page = ContactPage::factory()->create(['id' => 1]);

        return ContactServiceOption::factory()->for($page)->create(['slug' => 'arxius', 'label' => $this->bilingual('Arxius', 'Archivos')]);
    }

    /** MailFake records the mailable; this mocked receipt does not prove SMTP delivery. */
    private function acceptedMail(): MailFake
    {
        $fake = Mail::fake();
        Mail::shouldReceive('send')->once()->andReturnUsing(function (ContactSubmission $mail) use ($fake): SentMessage {
            $fake->send($mail);

            return \Mockery::mock(SentMessage::class);
        });

        return $fake;
    }

    public function test_valid_submission_sends_one_mailable_with_configured_recipient_reply_to_and_sender(): void
    {
        $this->service();
        SiteSetting::factory()->create(['id' => 1, 'contact_email' => 'configured@example.test']);
        Http::fake([self::SITEVERIFY => Http::response(['success' => true])]);
        $fake = $this->acceptedMail();
        $this->freezeTime();

        $this->postJson('/api/v1/contact', $this->payload())->assertOk()->assertJsonPath('success', true)->assertJsonPath('code', 'accepted')->assertHeader('Content-Language', 'ca');

        $fake->assertSentCount(1);
        $fake->assertSent(ContactSubmission::class, function (ContactSubmission $mail): bool {
            $envelope = $mail->envelope();

            return $mail->hasTo('configured@example.test') && $envelope->from->address === 'info@andreugurrera.cat'
                && $envelope->replyTo[0]->address === 'visitor@example.test'
                && $envelope->replyTo[0]->name === 'Visitant'
                && $mail->selectedServices === [['slug' => 'arxius', 'label' => 'Arxius']]
                && $mail->visitorMessage === 'Voldria informació.'
                && $mail->submittedAt->equalTo(CarbonImmutable::now('UTC'));
        });
        Http::assertSentCount(1);
        Http::assertSent(fn (ClientRequest $request): bool => $request->data() === ['secret' => 'server-only-secret', 'response' => 'single-use-token', 'remoteip' => '127.0.0.1']);
    }

    public static function fallbackAddresses(): array
    {
        return [[null], [''], ['invalid'], ["info@example.test\r\nBcc:attacker@example.test"], ['one@example.test,two@example.test']];
    }

    #[DataProvider('fallbackAddresses')]
    public function test_invalid_or_missing_cms_recipient_uses_fixed_fallback(?string $recipient): void
    {
        $this->service();
        SiteSetting::factory()->create(['id' => 1, 'contact_email' => $recipient]);
        Http::fake([self::SITEVERIFY => Http::response(['success' => true])]);
        $fake = $this->acceptedMail();

        $this->postJson('/api/v1/contact', $this->payload())->assertOk();

        $fake->assertSent(ContactSubmission::class, fn (ContactSubmission $mail): bool => $mail->hasTo('info@andreugurrera.cat'));
    }

    public function test_missing_settings_uses_fallback_and_spanish_body_locale_localizes_mail(): void
    {
        $this->service();
        Http::fake([self::SITEVERIFY => Http::response(['success' => true])]);
        $fake = $this->acceptedMail();

        $this->postJson('/api/v1/contact', $this->payload(['locale' => 'es']))->assertOk()->assertHeader('Content-Language', 'es')->assertJsonPath('message', 'El servidor de correo ha aceptado el mensaje para su entrega.');

        $fake->assertSent(ContactSubmission::class, fn (ContactSubmission $mail): bool => $mail->submissionLocale === 'es'
            && $mail->locale === 'es' && $mail->selectedServices[0]['label'] === 'Archivos'
            && $mail->hasTo('info@andreugurrera.cat'));
    }

    public function test_multiple_services_are_resolved_from_active_cms_labels_in_cms_order(): void
    {
        $service = $this->service();
        $service->update(['sort_order' => 5]);
        ContactServiceOption::factory()->create(['contact_page_id' => 1, 'slug' => 'recerca', 'sort_order' => 0, 'label' => $this->bilingual('Recerca', 'Investigación')]);
        Http::fake([self::SITEVERIFY => Http::response(['success' => true])]);
        $fake = $this->acceptedMail();

        $this->postJson('/api/v1/contact', $this->payload(['services' => ['arxius', 'recerca']]))->assertOk();

        $fake->assertSent(ContactSubmission::class, fn (ContactSubmission $mail): bool => $mail->selectedServices === [['slug' => 'recerca', 'label' => 'Recerca'], ['slug' => 'arxius', 'label' => 'Arxius']]);
    }

    public static function invalidFields(): array
    {
        return [
            ['name', ''], ['name', str_repeat('x', 121)], ['name', "Name\r\nBcc: other"], ['name', '<script>name</script>'],
            ['email', 'invalid'], ['email', "valid@example.test\r\nBcc:evil@example.test"], ['email', ['email@example.test']], ['email', str_repeat('x', 255).'@example.test'],
            ['services', []], ['services', 'arxius'], ['services', ['arxius', 'arxius']], ['services', ['missing']], ['services', ['label' => 'arxius']], ['services', array_fill(0, 11, 'arxius')],
            ['message', ''], ['message', str_repeat('a', 10001)], ['message', "hello\x00world"], ['message', ['malicious']],
            ['token', ''], ['token', 'bad token'], ['token', str_repeat('x', 2049)], ['token', ['value']],
            ['locale', 'en'], ['locale', ['es']], ['locale', ''],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_fields_fail_before_turnstile_or_mail(string $field, mixed $value): void
    {
        $this->service();
        Http::fake();
        Mail::fake();

        $this->postJson('/api/v1/contact', $this->payload([$field => $value]))->assertUnprocessable()->assertJsonPath('success', false)->assertJsonPath('code', 'validation_failed');

        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_empty_submission_rejects_required_fields(): void
    {
        Mail::fake();
        Http::fake();

        $this->postJson('/api/v1/contact', [])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'services', 'message', 'token']);

        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    public static function unexpectedFields(): array
    {
        return [['recipient'], ['to'], ['cc'], ['bcc'], ['from'], ['secret'], ['verified'], ['remoteip'], ['service']];
    }

    #[DataProvider('unexpectedFields')]
    public function test_unexpected_fields_are_rejected_instead_of_creating_a_relay(string $field): void
    {
        $this->service();
        Mail::fake();
        Http::fake();

        $this->postJson('/api/v1/contact', $this->payload([$field => 'attacker@example.test']))->assertUnprocessable()->assertJsonValidationErrors('payload');

        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_inactive_and_other_page_services_are_not_accepted(): void
    {
        $service = $this->service();
        $service->update(['active' => false]);
        $otherPage = ContactPage::factory()->create();
        ContactServiceOption::factory()->for($otherPage)->create(['slug' => 'other-page']);
        Mail::fake();
        Http::fake();

        $this->postJson('/api/v1/contact', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('services.0');
        $this->postJson('/api/v1/contact', $this->payload(['services' => ['other-page']]))->assertUnprocessable()->assertJsonValidationErrors('services.0');

        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    public static function failedTurnstile(): array
    {
        return [
            [['success' => false], 200, 422],
            [['success' => false, 'error-codes' => ['timeout-or-duplicate']], 200, 422],
            [['success' => true], 500, 503], ['not-json', 200, 503], [['success' => 'true'], 200, 503],
        ];
    }

    #[DataProvider('failedTurnstile')]
    public function test_turnstile_rejection_or_outage_never_sends_mail(mixed $body, int $upstreamStatus, int $expectedStatus): void
    {
        $this->service();
        Http::fake([self::SITEVERIFY => Http::response($body, $upstreamStatus)]);
        Mail::fake();

        $this->postJson('/api/v1/contact', $this->payload())->assertStatus($expectedStatus)->assertJsonPath('success', false);

        Mail::assertNothingSent();
        Http::assertSentCount(1);
    }

    public function test_turnstile_connection_failure_returns_generic_503(): void
    {
        $this->service();
        Http::fake([self::SITEVERIFY => Http::failedConnection('sensitive connection details')]);
        Mail::fake();

        $this->postJson('/api/v1/contact', $this->payload())->assertServiceUnavailable()->assertJsonPath('code', 'turnstile_unavailable')->assertDontSee('sensitive');

        Mail::assertNothingSent();
    }

    public function test_missing_turnstile_secret_fails_closed(): void
    {
        $this->service();
        config(['services.turnstile.secret_key' => null]);
        Http::fake();
        Mail::fake();

        $this->postJson('/api/v1/contact', $this->payload())->assertServiceUnavailable()->assertJsonPath('code', 'turnstile_unavailable');

        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_old_verification_success_does_not_authorize_submission_or_reuse(): void
    {
        $this->service();
        Http::fake([self::SITEVERIFY => Http::sequence()->push(['success' => true])->push(['success' => false, 'error-codes' => ['timeout-or-duplicate']])]);
        Mail::fake();

        $this->postJson('/api/v1/contact/verify', ['token' => 'single-use-token'])->assertOk();
        $this->postJson('/api/v1/contact', $this->payload())->assertUnprocessable()->assertJsonPath('code', 'turnstile_failed');

        Http::assertSentCount(2);
        Mail::assertNothingSent();
    }

    public function test_smtp_exception_returns_generic_503_and_redacted_log_even_with_debug_on(): void
    {
        $this->service();
        Http::fake([self::SITEVERIFY => Http::response(['success' => true])]);
        config(['app.debug' => true]);
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('smtp-password message-body secret'));
        Log::shouldReceive('warning')->once()->with('Contact mail transport failed.', ['exception_type' => TransportException::class]);

        $this->postJson('/api/v1/contact', $this->payload())->assertServiceUnavailable()->assertJsonPath('success', false)->assertJsonPath('code', 'mail_unavailable')->assertDontSee('smtp-password');

        Http::assertSentCount(1);
    }

    public function test_cancelled_mail_without_transport_receipt_does_not_return_success(): void
    {
        $this->service();
        Http::fake([self::SITEVERIFY => Http::response(['success' => true])]);
        Mail::shouldReceive('send')->once()->andReturnNull();

        $this->postJson('/api/v1/contact', $this->payload())->assertServiceUnavailable()->assertJsonPath('code', 'mail_unavailable');

        Http::assertSentCount(1);
    }

    public static function simulatedTransports(): array
    {
        return [['array'], ['log'], ['failover']];
    }

    #[DataProvider('simulatedTransports')]
    public function test_non_smtp_mailers_cannot_claim_delivery(string $mailer): void
    {
        $this->service();
        config(['mail.default' => $mailer]);
        Http::fake([self::SITEVERIFY => Http::response(['success' => true])]);
        Mail::fake();

        $this->postJson('/api/v1/contact', $this->payload())->assertServiceUnavailable()->assertJsonPath('code', 'mail_unavailable');

        Mail::assertNothingSent();
    }

    public function test_message_and_cms_service_html_are_escaped_in_both_locales(): void
    {
        $mail = new ContactSubmission('Visitant & Company', 'visitor@example.test', [['slug' => 'arxius', 'label' => '<img src=x onerror=evil()>']], "<script>alert('xss')</script>\nSecond line", CarbonImmutable::parse('2026-10-12T12:00:00Z'), 'ca');

        $spanish = clone $mail;
        $spanish->submissionLocale = 'es';

        $mail->locale('ca')->assertSeeInHtml('Nou missatge')->assertSeeInHtml('&lt;script&gt;', false)->assertDontSeeInHtml('<script>', false)->assertDontSeeInHtml('<img src=x', false)->assertSeeInHtml('2026-10-12T12:00:00+00:00')->assertSeeInHtml('Visitant &amp; Company', false);
        $spanish->locale('es')->assertSeeInHtml('Nuevo mensaje')->assertSeeInHtml('Servicios seleccionados');
    }

    public function test_ip_rate_limit_counts_failed_attempts_and_resets_after_one_minute(): void
    {
        $this->service();
        Mail::fake();
        Http::fake([self::SITEVERIFY => Http::response(['success' => false])]);
        $this->freezeTime();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/contact', $this->payload())->assertUnprocessable();
        }

        $this->postJson('/api/v1/contact', $this->payload())->assertTooManyRequests()->assertJsonPath('code', 'rate_limited')->assertHeader('Retry-After');

        Http::assertSentCount(5);
        $this->travel(61)->seconds();
        $this->postJson('/api/v1/contact', $this->payload())->assertUnprocessable();
        Mail::assertNothingSent();
    }

    public function test_email_rate_limit_applies_across_different_ips(): void
    {
        $this->service();
        Mail::fake();
        Http::fake([self::SITEVERIFY => Http::response(['success' => false])]);
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.'.($attempt + 1)])
                ->postJson('/api/v1/contact', $this->payload())->assertUnprocessable();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.99'])->postJson('/api/v1/contact', $this->payload(['email' => 'VISITOR@example.test']))->assertTooManyRequests();

        Http::assertSentCount(10);
        Mail::assertNothingSent();
    }

    public function test_cors_preflight_allows_only_configured_vue_origin(): void
    {
        config(['cors.allowed_origins' => ['https://portfolio.example']]);

        $this->options('/api/v1/contact', [], ['Origin' => 'https://portfolio.example', 'Access-Control-Request-Method' => 'POST', 'Access-Control-Request-Headers' => 'content-type'])->assertNoContent()->assertHeader('Access-Control-Allow-Origin', 'https://portfolio.example');
        $denied = $this->options('/api/v1/contact', [], ['Origin' => 'https://attacker.example', 'Access-Control-Request-Method' => 'POST']);
        $this->assertNotSame('https://attacker.example', $denied->headers->get('Access-Control-Allow-Origin'));
        $this->assertNotSame('*', $denied->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_submission_is_stateless_and_does_not_require_session_csrf(): void
    {
        $this->service();
        Http::fake([self::SITEVERIFY => Http::response(['success' => true])]);
        $fake = $this->acceptedMail();
        $this->app['env'] = 'production';

        $this->postJson('/api/v1/contact', $this->payload())->assertOk();

        $fake->assertSentCount(1);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('sessions', 0);
    }
}
