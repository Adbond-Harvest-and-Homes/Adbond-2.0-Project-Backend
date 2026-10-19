<?php

namespace app\Services;

use Illuminate\Support\Facades\Http;

use app\Utilities;

/**
 * Termii SMS gateway integration.
 *
 * Runs in simulated mode whenever no API key is configured, so the campaign
 * pipeline (queueing, recipient status, delivery log) can be built and tested
 * ahead of Termii account approval/Sender ID registration - swap the real
 * credentials into TERMII_API_KEY and this starts sending for real with no
 * other code changes.
 */
class TermiiService
{
    public function send(string $to, string $message, ?string $senderId = null): array
    {
        if (!config('services.termii.api_key')) {
            Utilities::logStuff("Termii running in simulated mode (no TERMII_API_KEY set) - would send SMS to {$to}");
            return [
                'success' => true,
                'simulated' => true,
                'message_id' => 'SIMULATED-' . uniqid(),
            ];
        }

        $url = rtrim(config('services.termii.base_url'), '/') . '/api/sms/send';

        $body = [
            'api_key' => config('services.termii.api_key'),
            'to' => $to,
            'from' => $senderId ?: config('services.termii.sender_id'),
            'sms' => $message,
            'type' => 'plain',
            'channel' => 'generic',
        ];

        $res = ['success' => false, 'simulated' => false];

        try {
            $response = Http::asJson()->post($url, $body)->json();
        } catch (\Exception $e) {
            Utilities::logStuff("Termii send request failed for {$to}: " . $e->getMessage());
            $res['message'] = 'Could not reach Termii, please try again later';
            return $res;
        }

        if (isset($response['message_id'])) {
            $res['success'] = true;
            $res['message_id'] = $response['message_id'];
        } else {
            $res['message'] = $response['message'] ?? 'Failed to send SMS via Termii';
            Utilities::logStuff("Termii send error for {$to}: " . json_encode($response));
        }

        return $res;
    }
}
