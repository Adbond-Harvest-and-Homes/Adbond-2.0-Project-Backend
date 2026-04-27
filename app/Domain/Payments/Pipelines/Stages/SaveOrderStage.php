<?php

namespace app\Domain\Payments\Pipelines\Stages;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Closure;

use app\Mail\NewOrder;

use app\Domain\Payments\Context\PaymentContext;

use app\Domain\Payments\Events\OrderSaved;

use app\Services\OrderService;
use app\Services\PackageService;
use app\Services\PromoCodeService;

use app\Models\PaymentStatus;

use app\Utilities;

class SaveOrderStage implements PaymentStage
{
    public function __construct(
        private OrderService $orderService,
        private PackageService $packageService,
        private PromoCodeService $promoCodeService
    ) {}

    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        // dd($context->processedData);
        $package = $this->packageService->package($context->processedData['packageId']);
        $context->package = $package;
        
        $orderData = $this->prepareOrderData($context, $package);
        $order = $this->orderService->save($orderData);
        
        // Generate order number
        $order->order_number = $order->id . $context->requestData['processingId'];
        $order->update();
        
        // Save discounts if any
        if (!empty($context->processedData['amountDetail']['appliedDiscounts'])) {
            $this->orderService->saveOrderDiscounts($order, $context->processedData['amountDetail']['appliedDiscounts']);
        }
        
        $order->load(['client', 'package', 'discounts']);
        $context->order = $order;
        
        // Send email notification (fire and forget)
        // try {
        //     Mail::to($order->client->email)->send(new NewOrder($order));
        // } catch (\Exception $e) {
        //     Utilities::logStuff("Error sending Order Email: " . $e->getMessage());
        // }
        
        
        return $next($context);
    }

    private function prepareOrderData(PaymentContext $context, $package): array
    {
        $data = [
            'clientId' => Auth::guard('client')->user()->id,
            'packageId' => $context->processedData['packageId'],
            'isInstallment' => $context->processedData['isInstallment'],
            'units' => $context->processedData['units'],
            'amountPayable' => $context->processedData['amountDetail']['amount'],
            'unitPrice' => $package->amount,
            'paymentStatusId' => PaymentStatus::pending()->id,
            'orderDate' => now(),
        ];
        
        if ($context->processedData['isInstallment']) {
            $data['installmentCount'] = $context->processedData['installmentCount'];
            $data['paymentDueDate'] = now()->addMonths((int)$context->processedData['installmentCount']);
        }
        
        if (isset($context->processedData['promoCode'])) {
            $promoCode = $this->promoCodeService->promoCode($context->processedData['promoCode']);
            $data['promoCodeId'] = $promoCode->id;
        }
        
        return $data;
    }
}