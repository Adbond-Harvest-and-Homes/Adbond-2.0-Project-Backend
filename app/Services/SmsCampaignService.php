<?php

namespace app\Services;

use app\Models\SmsCampaign;
use app\Models\SmsCampaignRecipient;
use app\Models\Client;

use app\Enums\SmsCampaignStatus;
use app\Enums\SmsRecipientStatus;

class SmsCampaignService
{
    public $filters = [];
    public $count = null;

    public function campaigns($with = [], $offset = 0, $perPage = null)
    {
        $query = SmsCampaign::with($with);

        if (isset($this->filters['status'])) $query->where('status', $this->filters['status']);
        if (isset($this->filters['text'])) $query->where('message', 'LIKE', '%' . $this->filters['text'] . '%');

        if ($this->count) return $query->count();

        if ($perPage == null) $perPage = config('pagination.PER_PAGE');
        return $query->offset($offset)->limit($perPage)->orderBy('id', 'DESC')->get();
    }

    public function createCampaign(string $message, array $clientIds, int $createdBy, ?string $senderId = null): SmsCampaign
    {
        $clients = Client::whereIn('id', $clientIds)->whereNotNull('phone_number')->get();

        $campaign = SmsCampaign::create([
            'created_by' => $createdBy,
            'sender_id' => $senderId,
            'message' => $message,
            'status' => SmsCampaignStatus::DRAFT->value,
            'total_recipients' => $clients->count(),
        ]);

        foreach ($clients as $client) {
            $normalized = $this->normalizePhoneNumber($client->phone_number);
            if (!$normalized) continue;

            SmsCampaignRecipient::create([
                'sms_campaign_id' => $campaign->id,
                'client_id' => $client->id,
                'phone_number' => $normalized,
                'status' => SmsRecipientStatus::PENDING->value,
            ]);
        }

        return $campaign;
    }

    /**
     * Normalizes a Nigerian local number to E.164 (+234...). Returns null if
     * the number doesn't look like a valid Nigerian mobile number, since
     * phone_number isn't validated/normalized at client registration time.
     */
    public function normalizePhoneNumber(?string $phoneNumber): ?string
    {
        if (!$phoneNumber) return null;

        $digits = preg_replace('/\D/', '', $phoneNumber);

        if (str_starts_with($digits, '234') && strlen($digits) == 13) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) == 11) {
            return '+234' . substr($digits, 1);
        }

        if (strlen($digits) == 10) {
            return '+234' . $digits;
        }

        return null;
    }

    /**
     * Rough SMS segment estimate for cost preview (160 chars per GSM-7 page).
     */
    public function estimateSegments(string $message): int
    {
        return (int) max(1, ceil(strlen($message) / 160));
    }
}
