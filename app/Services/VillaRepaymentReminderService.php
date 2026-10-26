<?php

namespace app\Services;

use Carbon\Carbon;

use app\Models\Order;
use app\Models\SmsCampaign;
use app\Models\SmsCampaignRecipient;

use app\Enums\ProjectType;
use app\Enums\SmsCampaignSource;
use app\Enums\SmsCampaignStatus;
use app\Enums\SmsRecipientStatus;

/**
 * Finds clients with an outstanding balance on a HOMES-type package ("villa")
 * whose payment is due soon or overdue, and builds a personalized SMS
 * reminder campaign for them - one upcoming nudge a few days before the due
 * date, then a repeat nudge every COOLDOWN_DAYS while it stays unpaid.
 */
class VillaRepaymentReminderService
{
    const UPCOMING_WINDOW_DAYS = 3;
    const COOLDOWN_DAYS = 3;

    public function eligibleOrders()
    {
        return Order::with(['client', 'package'])
            ->whereHas('package.project.projectType', function ($q) {
                $q->where('name', ProjectType::HOMES->value);
            })
            ->where('balance', '>', 0)
            ->whereNotNull('payment_due_date')
            ->whereHas('client', function ($q) {
                $q->whereNotNull('phone_number');
            })
            ->get();
    }

    /**
     * Builds (and persists as a draft) an automated reminder campaign for
     * every eligible order that isn't within its cooldown window. Returns
     * null if nothing is due to be reminded right now.
     */
    public function buildCampaign(?int $createdBy = null): ?SmsCampaign
    {
        $smsCampaignService = new SmsCampaignService;
        $today = Carbon::today();

        $recipients = [];

        foreach ($this->eligibleOrders() as $order) {
            $dueDate = Carbon::parse($order->payment_due_date)->startOfDay();
            $isOverdue = $dueDate->lt($today);
            $isUpcoming = !$isOverdue && $dueDate->diffInDays($today) <= self::UPCOMING_WINDOW_DAYS;

            if (!$isOverdue && !$isUpcoming) continue;
            if ($this->wasRemindedRecently($order->id)) continue;

            $phoneNumber = $smsCampaignService->normalizePhoneNumber($order->client->phone_number);
            if (!$phoneNumber) continue;

            $recipients[] = [
                'client_id' => $order->client_id,
                'order_id' => $order->id,
                'phone_number' => $phoneNumber,
                'message' => $this->buildMessage($order, $dueDate, $isOverdue),
            ];
        }

        if (count($recipients) == 0) return null;

        $campaign = SmsCampaign::create([
            'created_by' => $createdBy,
            'message' => 'Villa repayment reminder (automated - see per-recipient messages)',
            'source' => SmsCampaignSource::AUTOMATED->value,
            'status' => SmsCampaignStatus::DRAFT->value,
            'total_recipients' => count($recipients),
        ]);

        foreach ($recipients as $recipient) {
            SmsCampaignRecipient::create([
                'sms_campaign_id' => $campaign->id,
                'client_id' => $recipient['client_id'],
                'order_id' => $recipient['order_id'],
                'phone_number' => $recipient['phone_number'],
                'message' => $recipient['message'],
                'status' => SmsRecipientStatus::PENDING->value,
            ]);
        }

        return $campaign;
    }

    private function wasRemindedRecently(int $orderId): bool
    {
        $lastSentAt = SmsCampaignRecipient::where('order_id', $orderId)
            ->where('status', SmsRecipientStatus::SENT->value)
            ->whereHas('campaign', function ($q) {
                $q->where('source', SmsCampaignSource::AUTOMATED->value);
            })
            ->max('sent_at');

        if (!$lastSentAt) return false;

        return Carbon::parse($lastSentAt)->diffInDays(Carbon::now()) < self::COOLDOWN_DAYS;
    }

    private function buildMessage(Order $order, Carbon $dueDate, bool $isOverdue): string
    {
        $name = $order->client->firstname;
        $amount = '₦' . number_format($order->balance);
        $package = $order->package->name;
        $formattedDate = $dueDate->format('M d, Y');

        if ($isOverdue) {
            return "Dear {$name}, your payment of {$amount} for {$package} was due on {$formattedDate} and is now overdue. Kindly make payment as soon as possible to avoid penalties. - Bond Homes";
        }

        return "Dear {$name}, your payment of {$amount} for {$package} is due on {$formattedDate}. Kindly make payment on time to avoid penalties. - Bond Homes";
    }
}
