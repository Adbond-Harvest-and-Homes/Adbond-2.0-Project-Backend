<?php

namespace app\Http\Controllers\Client;

use app\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;

use app\Http\Requests\Client\SavePayment;
use app\Http\Requests\Client\PrepareAdditionalPayment;
use app\Http\Requests\Client\SaveAdditionalPayment;
use app\Http\Requests\Client\InitializeCardPayment;

use app\Mail\NewOrder;
use app\Mail\NewPayment;

use app\Domain\Payments\Context\PaymentContext;
use app\Domain\Payments\Pipelines\EventDrivenPaymentPipeline;
use app\Domain\Payments\Events\OrderSaved;
use app\Domain\Payments\Pipelines\Stages\{
    ValidateProcessingIdStage,
    ValidateOrderIdStage,
    ProcessPaymentStage,
    SaveOrderStage,
    SavePaymentStage,
    PostPaymentActionsStage,
    DispatchEventsStage
};

use app\Services\PackageService;
use app\Services\PromoCodeService;
use app\Services\OrderService;
use app\Services\PaymentService;
use app\Services\FileService;
use app\Services\CommissionService;
use app\Services\ClientPackageService;
use app\Services\ClientInvestmentService;

use app\Models\PaymentStatus;
use app\Models\PaymentMode;
use app\Models\PaymentGateway;
use app\Models\ClientPackage;
use app\Models\Order;
use app\Models\Offer;
use app\Models\ClientInvestment;

use app\Http\Resources\OrderResource;
use app\Http\Resources\PaymentResource;

use app\Enums\PaymentPurpose;
use app\Enums\FilePurpose;
use app\Enums\FileTypes;
use app\Enums\PackageType;
use app\Enums\UserType;
use app\Enums\OrderType;
use app\Enums\NotificationType;

use app\Utilities;
use app\Helpers;
use app\Services\NotificationService;

class PaymentController extends Controller
{
    private $paymentService;
    private $packageService;
    private $promoCodeService;
    private $orderService;
    private $fileService;
    private $commissionService;
    private $clientPackageService;
    private $clientInvestmentService;
    private $notificationService;

    // private PaymentPipeline $pipeline;
    private EventDrivenPaymentPipeline $pipeline;
    private EventDrivenPaymentPipeline $additionalPaymentPipeline;

    private static $userType = "app\Models\Client";

    public function __construct(
        private ValidateProcessingIdStage $validateStage,
        private ValidateOrderIdStage $validateOrderIdStage,
        private ProcessPaymentStage $processPaymentStage,
        private SaveOrderStage $saveOrderStage,
        private SavePaymentStage $savePaymentStage,
        private PostPaymentActionsStage $postActionsStage,
        private DispatchEventsStage $dispatchEventsStage
    )
    {
        $this->pipeline = new EventDrivenPaymentPipeline([
            $this->validateStage,
            $this->processPaymentStage,
            $this->saveOrderStage,
            $this->savePaymentStage,
            $this->postActionsStage,
            $this->dispatchEventsStage  // Events dispatched at the end
        ]);

        $this->additionalPaymentPipeline = new EventDrivenPaymentPipeline([
            $this->validateStage,
            $this->validateOrderIdStage,
            $this->processPaymentStage,
            $this->savePaymentStage,
            $this->postActionsStage,
            $this->dispatchEventsStage
        ]);

        $this->paymentService = new PaymentService;
        $this->packageService = new PackageService;
        $this->promoCodeService = new PromoCodeService;
        $this->orderService = new OrderService;
        $this->fileService = new FileService;
        $this->commissionService = new CommissionService;
        $this->clientPackageService = new ClientPackageService;
        $this->clientInvestmentService = new ClientInvestmentService;
        $this->notificationService = new NotificationService;
    }

    public function initializeCardPayment(InitializeCardPayment $request)
    {
        try{
            $processingId = $request->validated("processingId");
            $processedData = Cache::get('order_processing_' . $processingId);
            if(!$processedData) return Utilities::error402("processing Id has expired.. Go back and prepare the order again");
            $res = $this->paymentService->paystackInit(Auth::guard('client')->user(), $processedData['amountPayable']*100);
            // dd($res);
            if($res['success']==true) {
                $processedData['reference'] = $res['data']['reference'];
                if(isset($data['processingId'])) Cache::forget('order_processing_' . $processingId);
                Cache::put('order_processing_' . $processingId, $processedData, now()->addHours(12));
                return Utilities::ok($res['data']);
            }else{
                return Utilities::error402("failed to initialize payment.. ".$res['message']);
            }
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to perform this operation, Please try again later or contact support');
        }
    }

    public function prepareAdditionalPayment(PrepareAdditionalPayment $request)
    {
        $asset = $this->clientPackageService->clientPackage($request->validated('assetId'));
        if(!$asset) return Utilities::error402("Asset not found");
        $order = ($asset->purchase_type == Order::$type) ? $asset?->purchase : $asset?->purchase?->order;//$this->orderService->order($request->validated('orderId'));
        if(!$order) return Utilities::error402("Order not found");

        //confirm that its an installment order, else, return error
        if($order->type == OrderType::PURCHASE->value && $order->is_installment == 0) return Utilities::error402("This is not an Installment order, so no additional payments can be made");

        // confirm that the order is not complete
        if($order->completed == 1) return Utilities::error402("This order has already been completed!");

        // if($order->type == OrderType::PURCHASE->value && $order->installment_count == $order->installments_payed) return Utilities::error402("No more payment is required for this order at this time");
        if($order->type == OrderType::PURCHASE->value && !Utilities::shouldMakePayment($order)) return Utilities::error402("No more payment is required for this order at this time");

        //get the amount to be paid
        if($order->type == OrderType::PURCHASE->value) {
            $amount = $request->validated('amount');
            if($amount > $order->balance) return Utilities::error402("Sorry, you cannot pay more than the balance");
        }else{
            $amount = ($order->is_installment == 1) ? $request->validated('amount') : $order->amount_payable;
            if($amount > $order->balance) return Utilities::error402("Sorry, you cannot pay more than the balance");
        }
        $processingId = Utilities::getOrderProcessingId();

        // Save in Cache
        Cache::put('order_processing_' . $processingId, ["orderId" => $order->id, "amountPayable" => $amount, "type" => "old"], now()->addHours(12));

        // return the processingId and amount
        return Utilities::ok([
            "processingId" => $processingId,
            "amount" => $amount
        ]);
    }
    

    public function save(SavePayment $request)
    {
        DB::beginTransaction();
        
        try {
            $context = PaymentContext::fromPaymentRequest($request->validated());
            $context->client = Auth::user();
            $context->isFirstPayment = true;
            // $context = new PaymentContext(requestData: $request->validated(), processedData: []);
            $context = $this->pipeline->process($context);
            
            // Update installment count if needed
            // if ($context->payment->confirmed == 1 && $context->order->is_installment == 1) {
            //     $this->orderService->update(['installmentsPayed' => 1], $context->order);
            // }
            
            DB::commit();

            if($context->order) Event::dispatch(new OrderSaved($context));
            
            // Clean up cache
            Cache::forget('order_processing_' . $context->requestData['processingId']);

            $context->order->load(['package', 'discounts', 'paymentStatus']);

            return Utilities::ok([
                "paymentSummary" => new PaymentResource($context->payment),
                "order" => new OrderResource($context->order)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($e->getCode() === 402) {
                return Utilities::error402($e->getMessage());
            }
            
            return Utilities::error($e, 'An error occurred while processing your payment');
        }
    }

    public function saveAdditionalPayment(SaveAdditionalPayment $request)
    {
        DB::beginTransaction();
        
        try {
            $data = $request->validated();

            $context = PaymentContext::fromAdditionalPaymentRequest($data);
            // $context = new PaymentContext($request->validated(), []);
            // $context->processedData['orderId'] = $request->validated()['orderId'] ?? null;
            // $context->processedData['isInstallment'] = true;
            
            // Set amount payable
            // $context->processedData['amountPayable'] = $this->calculateInstallmentAmount($context->order);
            
            $context = $this->additionalPaymentPipeline->process($context);
            
            DB::commit();
            Cache::forget('order_processing_' . $context->requestData['processingId']);
            
            return Utilities::ok(new PaymentResource($context->payment));
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($e->getCode() === 402) {
                return Utilities::error402($e->getMessage());
            }
            
            return Utilities::error($e, 'An error occurred while processing your additional payment');
        }
    }
    
    private function calculateInstallmentAmount($order): float
    {
        // Calculate remaining balance or installment amount
        return $order->balance / ($order->installment_count - $order->installments_payed);
    }
    
}
