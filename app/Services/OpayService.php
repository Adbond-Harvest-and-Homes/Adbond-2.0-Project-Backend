<?php

namespace app\Services;

use Illuminate\Support\Facades\Http;

use app\Utilities;

/**
 * Opay Cashier (hosted checkout) integration.
 *
 * Mirrors the shape of PaymentService::paystackInit()/paystackVerify() so the
 * payment pipeline can treat both gateways the same way.
 */
class OpayService
{
    public function initializeCashier($client, $amount, $merchantReference)
    {
        $url = rtrim(config('services.opay.base_url'), '/').'/api/v1/international/cashier/create';
        $headers = [
            "Authorization" => "Bearer ".config('services.opay.public_key'),
            "MerchantId" => config('services.opay.merchant_id'),
            "Content-Type" => "application/json",
            "Accept" => "application/json",
        ];

        $body = [
            "reference" => $merchantReference,
            "country" => "NG",
            "amount" => [
                "total" => (int) ceil($amount),
                "currency" => "NGN",
            ],
            "returnUrl" => config('services.opay.return_url'),
            "callbackUrl" => config('services.opay.callback_url'),
            "product" => [
                "name" => "Adbond Package Purchase",
                "description" => "Payment for package purchase",
            ],
            "userInfo" => [
                "userId" => (string) $client->id,
                "userName" => $client->full_name ?? trim(($client->first_name ?? '').' '.($client->last_name ?? '')),
                "userEmail" => $client->email,
                "userMobile" => $client->phone ?? null,
            ],
        ];

        $res = ['success' => false];

        try {
            $response = Http::withHeaders($headers)->post($url, $body)->json();
        } catch (\Exception $e) {
            Utilities::logStuff("Opay cashier/create request failed: ".$e->getMessage());
            $res['message'] = 'Could not reach Opay, please try again later';
            return $res;
        }

        if (isset($response['code']) && $response['code'] === '00000') {
            $res['success'] = true;
            $res['data'] = [
                'reference' => $response['data']['reference'] ?? $merchantReference,
                'orderNo' => $response['data']['orderNo'] ?? null,
                'cashierUrl' => $response['data']['cashierUrl'] ?? null,
            ];
        } else {
            $res['message'] = $response['message'] ?? 'Failed to initialize Opay payment';
            Utilities::logStuff("Opay cashier/create error: ".json_encode($response));
        }

        return $res;
    }

    public function verifyTransaction($reference, $amount)
    {
        $url = rtrim(config('services.opay.base_url'), '/').'/api/v1/international/cashier/status';
        $body = ["reference" => $reference, "country" => "NG"];
        $headers = $this->signRequest($body);

        $res = ['success' => false, 'paymentError' => false];

        try {
            $response = Http::withHeaders($headers)->post($url, $body)->json();
        } catch (\Exception $e) {
            Utilities::logStuff("Opay cashier/status request failed: ".$e->getMessage());
            $res['paymentError'] = true;
            $res['message'] = 'Could not reach Opay to verify payment, please try again later';
            return $res;
        }

        $status = $response['data']['status'] ?? null;

        if (isset($response['code']) && $response['code'] === '00000' && $status === 'SUCCESS') {
            $payedAmount = ($response['data']['amount']['total'] ?? 0) / 100;
            $res['success'] = true;

            if (($payedAmount == $amount) || abs($payedAmount - $amount) < 1) {
                $res['amount'] = $payedAmount;
            } else {
                $res['amount'] = $payedAmount;
                $res['paymentError'] = true;
                $res['message'] = "The amount to be paid(".$amount.") doesn't match the amount that was paid(".$payedAmount.")";
            }
        } else {
            $res['paymentError'] = true;
            $res['message'] = $response['message'] ?? ('Opay transaction status: '.($status ?? 'unknown'));
        }

        return $res;
    }

    private function signRequest(array $body): array
    {
        $timestamp = (string) round(microtime(true) * 1000);
        $stringToSign = "RequestBody=".json_encode($body)."&RequestTimestamp=".$timestamp;
        $signature = hash_hmac('sha512', $stringToSign, config('services.opay.secret_key'));

        return [
            "Authorization" => $signature,
            "MerchantId" => config('services.opay.merchant_id'),
            "RequestTimestamp" => $timestamp,
            "Content-Type" => "application/json",
            "Accept" => "application/json",
        ];
    }

    /**
     * Verifies the `sha512` field Opay sends on its webhook payload.
     *
     * NOTE: Opay's docs are not fully consistent on the exact string that gets
     * signed for webhook callbacks. This implementation signs the `payload`
     * object as received (json-encoded) with the merchant secret key. If this
     * never matches a real sandbox webhook, log the raw body that gets printed
     * below and adjust the signed string here - this is the only place it
     * needs to change.
     */
    public function verifyWebhookSignature(string $rawBody): bool
    {
        $decoded = json_decode($rawBody, true);

        if (!$decoded || !isset($decoded['sha512']) || !isset($decoded['payload'])) {
            Utilities::logStuff("Opay webhook: unexpected payload shape: ".$rawBody);
            return false;
        }

        $expected = hash_hmac('sha512', json_encode($decoded['payload']), config('services.opay.secret_key'));

        if (!hash_equals($expected, $decoded['sha512'])) {
            Utilities::logStuff("Opay webhook: signature mismatch. Raw body: ".$rawBody);
            return false;
        }

        return true;
    }
}
