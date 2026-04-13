<?php

namespace app\Domain\Payments\Context;

use illuminate\Support\Facades\Auth;

use app\Models\Order;
use app\Models\Offer;
use app\Models\Payment;
use app\Models\Package;
use app\Models\ClientPackage;
use app\Models\ClientInvestment;
use app\Models\PaymentStatus;
use app\Models\Client;

class PaymentContext
{
    public array $requestData;
    public array $processedData;
    public bool $confirmation = false;
    public bool $additional = false;
    public ?Client $client;
    public ?Package $package = null;
    public ?Order $order = null;
    public ?Offer $offer = null;
    public ?Payment $payment = null;
    public ?ClientPackage $asset = null;
    public ?ClientInvestment $investment = null;
    public ?array $gatewayResponse = null;
    public ?array $uploadedReceipt = null;
    public bool $shouldDeductUnits = false;
    public bool $isFirstPayment = false;

    public function __construct(array $requestData, array $processedData)
    {
        $this->requestData = $requestData;
        $this->processedData = $processedData;
        $this->client = Auth::guard("client")->user();
    }

    public static function fromPaymentRequest(array $requestData): self
    {
        return new self($requestData, []);
    }

    public static function fromAdditionalPaymentRequest(array $requestData): self
    {
        $context = new self($requestData, []);
        $context->additional = true;
        // $context->processedData['orderId'] = $orderId;
        $context->processedData['isInstallment'] = true;
        return $context;
    }

    public function isCardPayment(): bool
    {
        return $this->requestData['cardPayment'] ?? false;
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