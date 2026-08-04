<?php

namespace App\Console\Commands\Sales;

use App\Jobs\Sales\ExpireQuotationsJob;
use App\Jobs\Sales\SendQuotationReminderJob;
use Illuminate\Console\Command;

class ProcessQuotationReminders extends Command
{
    protected $signature = 'sales:process-reminders';
    protected $description = 'Expire old quotations and create follow-up reminders';

    public function handle(): int
    {
        $this->info('Expiring quotations...');
        ExpireQuotationsJob::dispatchSync();

        $this->info('Creating reminders...');
        SendQuotationReminderJob::dispatchSync();

        $this->info('Done.');

        return self::SUCCESS;
    }
}
