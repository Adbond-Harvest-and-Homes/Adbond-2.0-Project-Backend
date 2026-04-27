<?php

namespace app\Domain\Payments\Context;

use app\Exceptions\AppException;

use app\Domain\Payments\Context\PaymentContext;

use app\Models\Order;
use app\Models\Offer;
use app\Models\Payment;
use app\Models\Package;
use app\Models\ClientPackage;
use app\Models\ClientInvestment;
use app\Models\ClientBond;
use app\Models\PaymentMode;
use app\Models\PaymentStatus;

use app\Services\PaymentService;
use app\Services\OrderService;
use app\Services\ClientPackageService;

use app\Enums\PackageType;

class PaymentConfirmationContext extends PaymentContext
{
    private $paymentService;
    private $orderService;

    public array $requestData;
    public ?Package $package = null;
    public bool $confirmation = true;
    public ?Order $order = null;
    public ?Offer $offer = null;
    public ?Payment $payment = null;
    public ?ClientPackage $asset = null;
    public ?ClientInvestment $investment = null;
    public ?ClientBond $bond = null;
    public bool $shouldDeductUnits = false;
    public bool $isFirstPayment = false;

    public function __construct(array $requestData)
    {
        $this->requestData = $requestData;
        $this->paymentService = new PaymentService;
        $this->orderService = new OrderService;

        $this->initialize();
    }

    public function initialize()
    {
        $payment = $this->paymentService->getPayment($this->requestData["paymentId"]);
        if(!$payment) throw new AppException(402, "Payment not found");

        $this->client = $payment->client;

        if($payment->confirmed === 0) throw new AppException(402, "This Payment has been rejected, let another request be made");
        if($payment->confirmed === 1) throw new AppException(402, "This Payment is already Confirmed");

        $this->payment = $payment;
        $this->package = $payment->purchase->package;
        if($payment->purchase_type == Order::$type) {
            if(!$payment?->purchase) throw new AppException(402, "Order not found");

            $this->order = $payment->purchase;

            $asset = null;
            switch($this->order?->package?->type) {
                case PackageType::NON_INVESTMENT->value :
                    $asset = $this->order?->clientPackage; 
                    if(!$asset && $this->order) $asset = app(ClientPackageService::class)->saveClientPackageOrder($this->order);
                    break;
                case PackageType::INVESTMENT->value :
                    $asset = $this->order?->clientInvestment?->clientPackage; 
                    if(!$asset && $this->order?->clientInvestment) $asset = app(ClientPackageService::class)->saveClientPackageInvestment($this->order->clientInvestment);
                    break;
                case PackageType::BOND->value :
                    $asset = $this->order?->clientBond?->clientPackage; 
                    if(!$asset && $this->order?->clientBond) $asset = app(ClientPackageService::class)->saveClientPackageBond($this->order->clientBond);
                    break;
            }
            if(!$asset) throw new AppException(402, "Asset not found..".$this->order?->package?->type);

            $this->order->refresh();
            $this->asset = $asset;
            
            if($this->order?->clientInvestment) $this->investment = $this->order?->clientInvestment;
            if($this->order?->clientBond) $this->bond = $this->order?->clientBond;
        }
        if($payment->purchase_type == Offer::$type) $this->offer = $payment->purchase;

        $confirmedPayment = Payment::where("purchase_id", $payment->purchase_id)->where("confirmed", 1)->first();
        $this->isFirstPayment = (!$confirmedPayment);
    }

    public function isCardPayment(): bool
    {
        return $this->payment->payment_mode_id == PaymentMode::cardPayment()->id;
    }

    public function isInvestmentPackage(): bool
    {
        return $this->package && $this->package->type === \App\Enums\PackageType::INVESTMENT->value;
    }

    public function isFullPayment(): bool
    {
        if (!$this->order) return false;
        return $this->order->is_installment == 0 || 
               (($this->order->payment_status_id === PaymentStatus::complete()->id) || $this->order->amount_payed == $this->order->amount_payable);
    }

    public function isFirstOrFullPayment(): bool 
    {
        return $this->isFirstPayment || $this->isFullPayment();
    }
}