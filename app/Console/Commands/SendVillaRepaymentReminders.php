<?php

namespace app\Console\Commands;

use Illuminate\Console\Command;

use app\Jobs\SendSmsCampaign;

use app\Services\VillaRepaymentReminderService;

use app\Enums\SmsCampaignStatus;

use app\Utilities;

class SendVillaRepaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-villa-repayment-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Texts clients with an overdue or soon-due balance on a villa (HOMES) package a personalized repayment reminder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $campaign = app(VillaRepaymentReminderService::class)->buildCampaign();

        if (!$campaign) {
            $this->info('No villa repayment reminders due right now.');
            return;
        }

        $campaign->update(['status' => SmsCampaignStatus::QUEUED->value]);

        SendSmsCampaign::dispatch($campaign);

        $this->info("Queued villa repayment reminder campaign #{$campaign->id} for {$campaign->total_recipients} recipient(s).");
        Utilities::jobLog("Villa repayment reminder campaign #{$campaign->id} queued for {$campaign->total_recipients} recipient(s).");
    }
}
