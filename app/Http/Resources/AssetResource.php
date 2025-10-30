<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Http\Resources\PackageResource;
use app\Http\Resources\FileResource;
use app\Http\Resources\ClientBriefResource;

use app\Enums\ClientPackageOrigin;

use app\Utilities;

class AssetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "client" => new ClientBriefResource($this->whenLoaded("client")),
            "package" => $this->package->name,
            "media" => FileResource::collection($this->package->media),
            "project_identifier" => $this->identifier,
            "project" => $this->package?->project?->name,
            "projectType" => $this->package?->project?->projectType?->name,
            "purchaseAt" => $this->created_at->format('F j, Y'), 
            "purchaseId" => $this->purchase_id,
            "amount" => $this->amount, //($this->origin == ClientPackageOrigin::ORDER->value) ? $this->purchase?->amount_payable : $this->purchase?->price,
            "location" => $this->package?->state,
            "address" => $this->package?->address,
            "description" => $this->package?->description,
            "units" => $this->units,
            "size" => $this->package?->size,
            "amountPaid" => $this->amountPaid(),
            "makePayment" => $this->makePaymentFlag(),
            "paymentPlan" => $this->paymentPlan(),
            "amountPerInstallment" => $this->installmentAmount(),
            "installmentCount" => $this->installmentCount(),
            "requestedSwitch" => $this->requestedSwitch(),
            "valuation" => ($this->package) ? $this->package->amount * $this->units : null,
            "nextPaymentDate" => $this->payment_due_date,
            "appreciation" => $this->appreciation(),
            "balance" => $this->balance(),
            "status" => $this->status(), 
            "active" => ($this->origin == ClientPackageOrigin::ORDER->value && !$this->purchase?->completed) ? true : false,
            "sold" => $this->sold > 0,
            "onOffer" => $this->onOffer(),
            "doaUploaded" => ($this->doa()) ? true : false,
            "files" => FileResource::collection($this->files),
            // "returns" => $this->investmentReturns()
        ];
    }

    // private function package()
    // {
    //     if($this->origin == ClientPackageOrigin::INVESTMENT->value) {
    //         $package = $this->purchase->order
    //     }
    // }

    private function appreciation()
    {
        $currentWorth = $this->package->amount * $this->purchase?->units ?? 0;
        // dd($currentWorth);
        $purchaseWorth = 0; 
        if($this->origin == ClientPackageOrigin::ORDER->value) {
            $purchaseWorth = ($this->purchase?->unit_price ?? 0) * ($this->purchase?->units ?? 0);
        }else{
            $purchaseWorth = $this->purchase?->price ?? 0;
        }
        // dd($purchaseWorth);
        return Utilities::calculateAppreciation($currentWorth, $purchaseWorth);
    }

    private function amountPaid()
    {
        if($this->purchase_complete==0) {
            if($this->origin == ClientPackageOrigin::INVESTMENT->value) return $this->purchase->order->amount_payed;
            return $this->purchase?->amount_payed;
        }
        return $this->amount;
    }

    private function balance()
    {
        if($this->origin == ClientPackageOrigin::ORDER->value) {
            return $this->purchase?->balance;
        }
        if($this->origin == ClientPackageOrigin::INVESTMENT->value) {
            return $this->purchase?->order?->balance;
        }
        return 0;
    }

    private function installmentCount()
    {
        if($this->origin == ClientPackageOrigin::ORDER->value || $this->origin == ClientPackageOrigin::INVESTMENT->value) {
            $order = ($this->origin == ClientPackageOrigin::ORDER->value) ? $this?->purchase : $this->purchase->order;
            return ($order?->is_installment == 1) ? $order->installment_count : null;
        }
        return null;
    }

    private function installmentAmount()
    {
        if($this->origin == ClientPackageOrigin::ORDER->value || $this->origin == ClientPackageOrigin::INVESTMENT->value) {
            $order = ($this->origin == ClientPackageOrigin::ORDER->value) ? $this?->purchase : $this->purchase->order;
            return ($order?->is_installment == 1) ? $order->amount_per_installment : null;
        }
        return null;
    }

    private function paymentPlan()
    {
        
        if($this->origin == ClientPackageOrigin::ORDER->value || $this->origin == ClientPackageOrigin::INVESTMENT->value) {
            $order = ($this->origin == ClientPackageOrigin::ORDER->value) ? $this?->purchase : $this->purchase->order;
            return ($order?->is_installment == 1) ? "installment" : "one-off";
        }
        return "one-off";
    }

    private function investmentReturns()
    {
        if($this->origin == ClientPackageOrigin::INVESTMENT->value) {
            $investment = $this->purchase;
            if($investment) {
                return [
                    "returns" => ($investment->amount) ? $investment->amount : $investment->percentage.'%',
                    "timeline" => $investment->duration.'Months',
                    "duration" => $investment->timeline.'Months'
                ];
            }
        }
        return null;
    }

    private function makePaymentFlag()
    {
        $flag = false;
        if($this->origin == ClientPackageOrigin::ORDER->value && $this->purchase->is_installment == 1) {
            if($this->purchase->installment_count){
                $flag = ($this->purchase->installment_count > $this->purchase->installments_payed);
            }else{
                $flag = ($this->balance() > 0);
            }
        }
        $payments = $this->purchase->payments;
        if(!$payments || $payments->count() == 0) {
            $flag = true;
        }else{
            foreach($payments as $payment) {
                if($payment->confirmed == null) $flag = false;
            }
        }
        if($this->requestedSwitch()) $flag = false;
        return $flag;
    }

    private function status()
    {
        $status = "pending";
        if($this->origin == ClientPackageOrigin::INVESTMENT->value) {
            if($this->purchase->order->completed == 1) $status = "completed";
        }else{
            if($this->purchase->completed == 1) $status = "completed";
        }
        return $status;
    }
}
