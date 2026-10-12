<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactSubmissionRequest;
use App\Mail\ContactSubmission;
use App\Models\ContactServiceOption;
use App\Models\SiteSetting;
use App\Services\TurnstileVerification;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactSubmissionController extends Controller
{
    public function __invoke(ContactSubmissionRequest $request, TurnstileVerification $turnstile): JsonResponse
    {
        $data = $request->validated();
        $status = $turnstile->verify($data['token'], $request->ip());
        if ($status !== 200) {
            $code = $status === 422 ? 'turnstile_failed' : 'turnstile_unavailable';

            return $this->failure($code, $status);
        }

        $mailer = config('mail.default');
        if (! is_string($mailer) || config('mail.mailers.'.$mailer.'.transport') !== 'smtp') {
            return $this->failure('mail_unavailable', 503);
        }
        $recipient = SiteSetting::find(1)?->contact_email;
        if (! is_string($recipient) || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $recipient = config('mail.contact_fallback_to');
        }
        $locale = app()->getLocale();
        $services = ContactServiceOption::where('contact_page_id', 1)->where('active', true)
            ->whereIn('slug', $data['services'])->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (ContactServiceOption $service): array => ['slug' => $service->slug, 'label' => $service->translated('label', $locale)])->all();

        try {
            $sent = Mail::send((new ContactSubmission(
                $data['name'], $data['email'], $services, $data['message'], CarbonImmutable::now('UTC'), $locale,
            ))->to($recipient)->locale($locale));
        } catch (Throwable $exception) {
            Log::warning('Contact mail transport failed.', ['exception_type' => $exception::class]);

            return $this->failure('mail_unavailable', 503);
        }
        if (! $sent instanceof SentMessage) {
            return $this->failure('mail_unavailable', 503);
        }

        return response()->json(['success' => true, 'code' => 'accepted', 'message' => __('contact.accepted')]);
    }

    private function failure(string $code, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'code' => $code, 'message' => __('contact.'.$code)], $status);
    }
}
