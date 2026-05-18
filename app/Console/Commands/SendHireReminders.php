<?php

namespace App\Console\Commands;

use App\Mail\HireReminderMail;
use App\Models\Hire;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendHireReminders extends Command
{
    protected $signature   = 'hires:send-reminders';
    protected $description = 'Email staff whose hired equipment is due today, due in 3 days, or overdue';

    public function handle(): void
    {
        $today   = now()->startOfDay();
        $in3days = now()->addDays(3)->startOfDay();

        $hires = Hire::with(['staff', 'items.product'])
            ->where('hire_status', 'active')
            ->whereNotNull('hire_return_date')
            ->where('hire_return_date', '<=', $in3days)
            ->get();

        $sent = 0;

        foreach ($hires as $hire) {
            $staff = $hire->staff;

            if (!$staff || !$staff->staff_email) {
                $this->warn("Hire #{$hire->id}: no staff email, skipping.");
                continue;
            }

            $returnDate = $hire->hire_return_date->startOfDay();
            $diffDays   = (int) $today->diffInDays($returnDate, false); // negative = overdue

            $type = match(true) {
                $diffDays < 0  => 'overdue',
                $diffDays === 0 => 'due_today',
                default         => 'due_soon',
            };

            $items = $hire->items->map(fn($i) => $i->product?->prod_name ?? 'Unknown item')->toArray();

            Mail::to($staff->staff_email)
                ->send(new HireReminderMail(
                    staffName:  "{$staff->staff_first_name} {$staff->staff_last_name}",
                    returnDate: $hire->hire_return_date->format('d M Y'),
                    type:       $type,
                    days:       abs($diffDays),
                    items:      $items,
                    hireId:     $hire->id,
                ));

            $this->line("Sent {$type} reminder to {$staff->staff_email} (hire #{$hire->id})");
            $sent++;
        }

        $this->info("Done. {$sent} reminder(s) sent.");
    }
}
