<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContactVerificationTest extends TestCase
{
    private const SITEVERIFY = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.turnstile.secret_key' => 'server-only-secret']);
        Http::preventStrayRequests();
    }

    public function test_valid_token_returns_success_and_sends_server_credentials_and_ip(): void
    {
        Http::fake([self::SITEVERIFY => Http::response(['success' => true, 'hostname' => 'private.example'])]);

        $this->postJson('/api/v1/contact/verify', ['token' => 'valid-token', 'secret' => 'attacker-secret', 'remoteip' => '203.0.113.1'])
            ->assertOk()->assertExactJson(['success' => true]);

        Http::assertSent(fn (Request $request): bool => $request->url() === self::SITEVERIFY
            && $request->method() === 'POST'
            && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
            && $request->data() === ['secret' => 'server-only-secret', 'response' => 'valid-token', 'remoteip' => '127.0.0.1']);
    }

    #[DataProvider('failedVerifications')]
    public function test_failed_verification_returns_safe_422(string $error): void
    {
        Http::fake([self::SITEVERIFY => Http::response(['success' => false, 'error-codes' => [$error]])]);

        $this->postJson('/api/v1/contact/verify', ['token' => 'invalid-token'])
            ->assertUnprocessable()->assertExactJson(['success' => false]);
    }

    public static function failedVerifications(): array
    {
        return [['invalid-input-response'], ['timeout-or-duplicate']];
    }

    public function test_reused_token_is_verified_again_and_rejected_with_422(): void
    {
        Http::fake([self::SITEVERIFY => Http::sequence()
            ->push(['success' => true])->push(['success' => false, 'error-codes' => ['timeout-or-duplicate']])]);

        $this->postJson('/api/v1/contact/verify', ['token' => 'single-use-token'])->assertOk();
        $this->postJson('/api/v1/contact/verify', ['token' => 'single-use-token'])
            ->assertUnprocessable()->assertExactJson(['success' => false]);
        Http::assertSentCount(2);
    }

    #[DataProvider('invalidTokens')]
    public function test_missing_or_malformed_token_returns_422_without_cloudflare_request(array $payload): void
    {
        Http::fake();

        $this->postJson('/api/v1/contact/verify', $payload)->assertUnprocessable()->assertExactJson(['success' => false]);

        Http::assertNothingSent();
    }

    public static function invalidTokens(): array
    {
        return [[[]], [['token' => '']], [['token' => '   ']], [['token' => ['invalid']]], [['token' => str_repeat('a', 2049)]]];
    }

    #[DataProvider('missingSecrets')]
    public function test_missing_secret_returns_safe_503_even_in_production(?string $secret): void
    {
        config(['services.turnstile.secret_key' => $secret]);
        $this->app['env'] = 'production';
        Http::fake();

        $this->postJson('/api/v1/contact/verify', ['token' => 'valid-token'])
            ->assertServiceUnavailable()->assertExactJson(['success' => false]);

        Http::assertNothingSent();
    }

    public static function missingSecrets(): array
    {
        return [[null], [''], ['   ']];
    }

    public function test_cloudflare_timeout_returns_safe_503(): void
    {
        Http::fake([self::SITEVERIFY => Http::failedConnection('Internal connection details')]);

        $this->postJson('/api/v1/contact/verify', ['token' => 'valid-token'])
            ->assertServiceUnavailable()->assertExactJson(['success' => false]);
    }

    #[DataProvider('unavailableResponses')]
    public function test_api_error_or_malformed_response_returns_safe_503(mixed $body, int $status): void
    {
        Http::fake([self::SITEVERIFY => Http::response($body, $status)]);

        $this->postJson('/api/v1/contact/verify', ['token' => 'valid-token'])
            ->assertServiceUnavailable()->assertExactJson(['success' => false]);
    }

    public static function unavailableResponses(): array
    {
        return [
            [['success' => true, 'secret' => 'internal-secret'], 500],
            ['upstream internal error', 429],
            ['not-json', 200],
            ['null', 200],
            ['true', 200],
            [[], 200],
            [['success' => 'true'], 200],
            [['success' => 1], 200],
        ];
    }

    public function test_verification_is_rate_limited_per_ip_and_recovers_after_one_minute(): void
    {
        Http::fake([self::SITEVERIFY => Http::response(['success' => false])]);
        $this->freezeTime();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/contact/verify', ['token' => 'invalid-token'])->assertUnprocessable();
        }

        $this->postJson('/api/v1/contact/verify', ['token' => 'invalid-token'])
            ->assertTooManyRequests()->assertExactJson(['success' => false])->assertHeader('Retry-After');
        Http::assertSentCount(5);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.2'])
            ->postJson('/api/v1/contact/verify', ['token' => 'invalid-token'])->assertUnprocessable();
        $this->travel(61)->seconds();
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/api/v1/contact/verify', ['token' => 'invalid-token'])->assertUnprocessable();
    }

    public function test_vue_origin_can_preflight_verification_post(): void
    {
        config(['cors.allowed_origins' => ['https://portfolio.example']]);

        $this->options('/api/v1/contact/verify', [], [
            'Origin' => 'https://portfolio.example',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type',
        ])->assertNoContent()->assertHeader('Access-Control-Allow-Origin', 'https://portfolio.example')
            ->assertHeader('Access-Control-Allow-Methods', 'GET, HEAD, POST, OPTIONS');
    }
}
