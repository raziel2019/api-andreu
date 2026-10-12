<?php

namespace App\Mail;

use Carbon\CarbonImmutable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactSubmission extends Mailable
{
    /** @param list<array{slug: string, label: ?string}> $selectedServices */
    public function __construct(
        public string $visitorName,
        public string $visitorEmail,
        public array $selectedServices,
        public string $visitorMessage,
        public CarbonImmutable $submittedAt,
        public string $submissionLocale,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            replyTo: [new Address($this->visitorEmail, $this->visitorName)],
            subject: __('contact.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contact-submission');
    }
}
