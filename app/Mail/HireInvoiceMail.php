<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HireInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $staffName,
        public readonly string $invoiceNumber,
        public readonly float  $total,
        public readonly int    $hireId,
        public readonly string $filePath,   // relative path on the 'public' disk
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Invoice {$this->invoiceNumber} — CSR Equipment Hire");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.hire-invoice');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('public', $this->filePath)
                ->as($this->invoiceNumber . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
