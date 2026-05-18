<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HireReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $staffName,
        public readonly string $returnDate,
        public readonly string $type,   // 'due_today' | 'due_soon' | 'overdue'
        public readonly int    $days,   // 0 = today, positive = days until due, negative = days overdue
        public readonly array  $items,  // list of hired product names
        public readonly int    $hireId,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match($this->type) {
            'overdue'   => "OVERDUE: Equipment return required — {$this->days} day(s) past due",
            'due_today' => 'REMINDER: Your hired equipment is due back today',
            default     => "REMINDER: Equipment return due in {$this->days} day(s)",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.hire-reminder');
    }

    public function attachments(): array
    {
        return [];
    }
}
