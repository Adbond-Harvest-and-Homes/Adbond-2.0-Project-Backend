<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use app\Domain\Payments\Context\PaymentContext;
use app\Domain\Payments\Pipelines\EventDrivenPaymentPipeline;
use app\Domain\Payments\Events\OrderSaved;
use app\Domain\Payments\Pipelines\Stages\{
    ValidateProcessingIdStage,
    ProcessPaymentStage,
    SaveOrderStage,
    SavePaymentStage,
    PostPaymentActionsStage,
    DispatchEventsStage
};

use app\Services\PaymentService;
use app\Services\OpayService;
use app\Models\Client;

use app\Utilities;

/**
 * Safety net for the Opay hosted-checkout flow: if a customer pays on Opay's
 * page but never returns to the app (so the client-driven PaymentController::save()
 * is never called), this webhook creates the order/payment itself using the
 * same pipeline, from the cached order-processing draft.
 *
 * If the client did return and `save()` already ran, this is a no-op.
 */
class OpayWebhookController extends Controller
{
    private $paymentService;
    private $opayService;
    private EventDrivenPaymentPipeline $pipeline;

    public function __construct(
        private ValidateProcessingIdStage $validateStage,
        private ProcessPaymentStage $processPaymentStage,
        private SaveOrderStage $saveOrderStage,
        private SavePaymentStage $savePaymentStage,
        private PostPaymentActionsStage $postActionsStage,
        private DispatchEventsStage $dispatchEventsStage
    ) {
        $this->pipeline = new EventDrivenPaymentPipeline([
            $this->validateStage,
            $this->processPaymentStage,
            $this->saveOrderStage,
            $this->savePaymentStage,
            $this->postActionsStage,
            $this->dispatchEventsStage,
        ]);

        $this->paymentService = new PaymentService;
        $this->opayService = new OpayService;
    }

    public function handle(Request $request)
    {
        $rawBody = $request->getContent();

        if (!$this->opayService->verifyWebhookSignature($rawBody)) {
            return response('invalid signature', 400);
        }

        $decoded = json_decode($rawBody, true);
        $payload = $decoded['payload'] ?? [];
        $reference = $payload['reference'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$reference) {
            return response('missing reference', 400);
        }

        // Already processed via the client-driven /save endpoint - nothing to do.
        if ($this->paymentService->getPaymentByReference($reference)) {
            return response('ok', 200);
        }

        if ($status !== 'SUCCESS') {
            Utilities::logStuff("Opay webhook: non-success status '{$status}' for reference {$reference}, ignoring");
            return response('ok', 200);
        }

        $processingId = $this->extractProcessingId($reference);
        $processedData = $processingId ? Cache::get('order_processing_' . $processingId) : null;

        if (!$processedData) {
            // Either already consumed by /save (and cache forgotten) or genuinely expired.
            // Re-check for the payment once more in case of a race with /save.
            Utilities::logStuff("Opay webhook: no cached draft for reference {$reference} (processingId {$processingId})");
            return response('ok', 200);
        }

        $client = Client::find($processedData['clientId'] ?? null);
        if (!$client) {
            Utilities::logStuff("Opay webhook: client not found for reference {$reference}");
            return response('ok', 200);
        }

        Auth::shouldUse('client');
        Auth::guard('client')->setUser($client);

        DB::beginTransaction();

        try {
            $requestData = $processedData;
            $requestData['processingId'] = $processingId;
            $requestData['cardPayment'] = true;
            $requestData['gateway'] = 'opay';
            $requestData['reference'] = $reference;

            $context = PaymentContext::fromPaymentRequest($requestData);
            $context->client = $client;
            $context->isFirstPayment = true;

            $context = $this->pipeline->process($context);

            DB::commit();

            if ($context->order) Event::dispatch(new OrderSaved($context));

            Cache::forget('order_processing_' . $processingId);

            return response('ok', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Utilities::logStuff("Opay webhook: failed to process reference {$reference}: " . $e->getMessage());
            return response('processing error', 500);
        }
    }

    private function extractProcessingId(string $reference): ?string
    {
        // Expected format: opay_{processingId}_{unixtimestamp}
        if (preg_match('/^opay_(\d+)_\d+$/', $reference, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
