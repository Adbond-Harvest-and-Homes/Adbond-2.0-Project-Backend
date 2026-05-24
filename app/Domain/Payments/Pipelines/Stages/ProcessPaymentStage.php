<?php

namespace app\Domain\Payments\Pipelines\Stages;

use Illuminate\Support\Facades\Auth;
use Closure;

use app\Domain\Payments\Context\PaymentContext;

use app\Services\PaymentService;
use app\Services\FileService;

use app\Enums\FilePurpose;

use app\Models\PaymentStatus;


class ProcessPaymentStage implements PaymentStage
{
    public function __construct(
        private PaymentService $paymentService,
        private FileService $fileService
    ) {}

    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        if ($context->isCardPayment()) {
            $context->gatewayResponse = $this->processCardPayment($context);
        } else {
            $context->uploadedReceipt = $this->processBankPayment($context);
        }
        
        return $next($context);
    }

    private function processCardPayment(PaymentContext $context): array
    {
        $response = $this->paymentService->paystackVerify(
            $context->requestData['reference'], 
            $context->processedData['amountPayable']
        );

        if ($response['success'] && !$response['paymentError']) {
            $context->requestData['paymentStatusId'] = ($context->order->is_installment == 1) 
                ? PaymentStatus::deposit()->id 
                : PaymentStatus::complete()->id;
        } else {
            $context->requestData['paymentStatusId'] = PaymentStatus::pending()->id;
        }

        return $response;
    }

    private function processBankPayment(PaymentContext $context): array
    {
        $purpose = FilePurpose::PAYMENT_EVIDENCE->value;
        $file = $context->requestData['evidence'];
        $fileType = str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'pdf';
        
        $response = $this->fileService->save(
            $file, 
            $fileType, 
            Auth::guard('client')->user()->id, 
            $purpose, 
            'client', 
            'payment-evidences'
        );
        
        if ($response['status'] != 200) {
            throw new \Exception('Sorry Payment Evidence could not be uploaded ' . $response['message'], 402);
        }
        
        $context->requestData['evidenceFileId'] = $response['file']->id;
        $context->requestData['paymentStatusId'] = PaymentStatus::pending()->id;
        
        return $response;
    }
}