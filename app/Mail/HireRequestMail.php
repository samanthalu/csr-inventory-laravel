<?php

namespace App\Mail;

use App\Models\HireRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HireRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public HireRequest $hireRequest) {}

    public function envelope(): Envelope
    {
        $requester = $this->hireRequest->requestedBy?->name ?? 'A user';
        return new Envelope(
            subject: "New Hire Request: {$this->hireRequest->purpose}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.hire-request');
    }
}
