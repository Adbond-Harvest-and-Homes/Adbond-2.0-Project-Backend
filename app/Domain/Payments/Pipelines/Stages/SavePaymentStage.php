<?php

namespace app\Domain\Payments\Pipelines\Stages;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Closure;

use app\Domain\Payments\Context\PaymentContext;

use app\Services\PaymentService;
use app\Services\ReceiptService;
use app\Services\OrderService;
use app\Services\NotificationService;

use app\Models\PaymentMode;
use app\Models\PaymentStatus;

use app\Enums\PaymentPurpose;
use app\Enums\NotificationType;

use app\Helpers;

class SavePaymentStage implements PaymentStage
{
    public function __construct(
        private PaymentService $paymentService,
        private ReceiptService $receiptService,
        private OrderService $orderService
    ) {}

    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        $paymentData = $this->preparePaymentData($context);
        $payment = $this->paymentService->save($paymentData);

        $context->payment = $payment;
        
        // Update order with payment data
        
        
        // Send notification for bank payments
        // if (!$context->isCardPayment()) {
        //     $this->notificationService->save(
        //         $payment, 
        //         NotificationType::ORDER_PAYMENT_CONFIRMATION_REQ->value, 
        //         Auth::guard('client')->user()
        //     );
        // }
        
        return $next($context);
    }

    private function preparePaymentData(PaymentContext $context): array
    {
        $paymentData = [
            'clientId' => Auth::guard('client')->user()->id,
            'paymentModeId' => $context->isCardPayment() 
                ? PaymentMode::cardPayment()->id 
                : PaymentMode::bankTransfer()->id,
            'purchaseId' => $context->order->id,
            'purchaseType' => "app\Models\Order",
            'receiptNumber' => $this->receiptService->generateReceiptNo(
                $context->order->id, 
                Auth::guard('client')->user()->id, 
                $context->requestData['processingId']
            ),
            'paymentGatewayId' => $context->isCardPayment() 
                ? PaymentMode::cardPayment()->id 
                : PaymentMode::bankTransfer()->id,
            // 'docsUploaded' => Helpers::kycCompleted(Auth::guard('client')->user()),
        ];
        if($context->additional) {
            $paymentData['purpose'] = PaymentPurpose::INSTALLMENT_PAYMENT->value;
        }else{
            $paymentData['purpose'] = ($context->order->is_installment == 1)
                    ? PaymentPurpose::INSTALLMENT_PAYMENT->value 
                    : PaymentPurpose::PACKAGE_FULL_PAYMENT->value;
        }
        
        if ($context->isCardPayment() && $context->gatewayResponse) {
            $paymentData['amount'] = $context->gatewayResponse['amount'] ?? $context->processedData['amountPayable'];
            $paymentData['success'] = $context->gatewayResponse['success'];
            $paymentData['reference'] = $context->requestData['reference'];
            $paymentData['paymentDate'] = now();
            
            if ($context->gatewayResponse['success'] && !$context->gatewayResponse['paymentError']) {
                $paymentData['confirmed'] = true;
            } else {
                $paymentData['flag'] = true;
                $paymentData['flagMessage'] = $context->gatewayResponse['message'] ?? '';
            }
            
            if (!$context->gatewayResponse['success']) {
                $paymentData['failureMessage'] = $context->gatewayResponse['message'];
            }
        } else {
            $paymentData['amount'] = $context->requestData['amountPayed'] ?? $context->processedData['amountPayable'];
            $paymentData['evidenceFileId'] = $context->requestData['evidenceFileId'];
            $paymentData['bankAccountId'] = $context->requestData['bankId'];
            $paymentData['paymentDate'] = $context->requestData['paymentDate'];
        }
        
        return $paymentData;
    }

    private function prepareOrderUpdateData(PaymentContext $context): array
    {
        $updateData = [];
        
        if ($context->isCardPayment() && $context->gatewayResponse) {
            $updateData['paymentStatusId'] = $context->requestData['paymentStatusId'] ?? null;
            $updateData['amountPayed'] = $context->gatewayResponse['amount'] ?? $context->processedData['amountPayable'];
            $updateData['balance'] = $context->order->balance - ($updateData['amountPayed'] ?? 0);
        }
        
        if (isset($context->requestData['amountPayed'])) {
            $updateData['amountPayed'] = $context->requestData['amountPayed'];
            $updateData['balance'] = $context->order->balance - $context->requestData['amountPayed'];
        }
        
        if ($context->order->type === OrderType::PURCHASE->value || $context->order->is_installment == 1) {
            $updateData['installmentsPayed'] = $context->order->installments_payed + 1;
        }
        
        return $updateData;
    }
}