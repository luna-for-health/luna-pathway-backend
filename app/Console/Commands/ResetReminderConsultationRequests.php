<?php

namespace App\Console\Commands;

use App\Enums\ConsultationRequestStatus;
use App\Models\ConsultationRequest;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('consultation-requests:reset-reminders')]
#[Description('Move every consultation request in "reminder" status back to "new"')]
class ResetReminderConsultationRequests extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $updatedCount = ConsultationRequest::query()
            ->where('status', ConsultationRequestStatus::Reminder)
            ->update(['status' => ConsultationRequestStatus::New]);

        $this->info($updatedCount.' consultation request(s) moved from reminder to new.');

        return self::SUCCESS;
    }
}
