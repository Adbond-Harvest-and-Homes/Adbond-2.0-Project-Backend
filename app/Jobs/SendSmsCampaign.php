<?php

namespace app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;

use app\Services\TermiiService;

use app\Models\SmsCampaign;

use app\Enums\SmsCampaignStatus;
use app\Enums\SmsRecipientStatus;

use app\Utilities;

class SendSmsCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Queueable;

    protected $campaign;

    public function __construct(SmsCampaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->campaign->id)];
    }

    public function handle(): void
    {
        $termii = new TermiiService;

        $recipients = $this->campaign->recipients()->where('status', SmsRecipientStatus::PENDING->value)->get();

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            $result = $termii->send($recipient->phone_number, $this->campaign->message, $this->campaign->sender_id);

            if ($result['success']) {
                $recipient->update([
                    'status' => SmsRecipientStatus::SENT->value,
                    'provider_message_id' => $result['message_id'] ?? null,
                    'sent_at' => now(),
                ]);
                $sent++;
            } else {
                $recipient->update([
                    'status' => SmsRecipientStatus::FAILED->value,
                    'error_message' => $result['message'] ?? 'Unknown error',
                ]);
                $failed++;
            }
        }

        $this->campaign->update([
            'sent_count' => $this->campaign->sent_count + $sent,
            'failed_count' => $this->campaign->failed_count + $failed,
            'status' => $failed == 0 ? SmsCampaignStatus::SENT->value : ($sent == 0 ? SmsCampaignStatus::FAILED->value : SmsCampaignStatus::PARTIALLY_SENT->value),
            'sent_at' => now(),
        ]);

        Utilities::jobLog("SMS campaign {$this->campaign->id} finished: {$sent} sent, {$failed} failed");
    }

    public function failed(\Throwable $exception): void
    {
        Utilities::jobLog("SendSmsCampaign job failed permanently for campaign {$this->campaign->id}: " . $exception->getMessage());
        $this->campaign->update(['status' => SmsCampaignStatus::FAILED->value]);
    }
}
