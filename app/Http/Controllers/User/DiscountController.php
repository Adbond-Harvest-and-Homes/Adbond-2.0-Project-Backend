<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\UpdateDiscount;
use app\Http\Requests\User\UpdateInstallmentDiscount;

use app\Http\Resources\InstallmentDiscountResource;

use app\Services\DiscountService;

use app\Enums\DiscountType;

use app\Utilities;

class DiscountController extends Controller
{
    protected $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    public function fullPayment()
    {
        $fullPayment = $this->discountService->getFullPayment();

        return Utilities::ok(
            ["discount" => $fullPayment->discount]
        );
    }

    public function updateFullPayment(UpdateDiscount $request)
    {
        $data = $request->validated();
        $type = DiscountType::FULL_PAYMENT->value;
        $fullPayment = $this->discountService->updateDiscount($type, $data);

        return Utilities::ok(
            ["discount" => $fullPayment->discount]
        );
    }

    public function installments()
    {
        $installments = $this->discountService->getInstallmentDurations();

        return Utilities::ok(InstallmentDiscountResource::collection($installments));
    }

    public function updateInstallmentDiscounts(UpdateInstallmentDiscount $request)
    {
        $data = $request->validated();
        // dd($data);

        $this->discountService->updateInstallmentDiscounts($data);

        $installments = $this->discountService->getInstallmentDurations();

        return Utilities::ok(InstallmentDiscountResource::collection($installments));
    }

    public function addInstallmentDiscounts(UpdateInstallmentDiscount $request)
    {
        $data = $request->validated();

        $this->discountService->addInstallmentDiscounts($data);

        $installments = $this->discountService->getInstallmentDurations();

        return Utilities::ok(InstallmentDiscountResource::collection($installments));
    }

    public function deleteInstallment($duration)
    {
        $installment = $this->discountService->getInstallmentDuration($duration);
        if(!$installment) return Utilities::error402("Could not find this installment duration");

        $this->discountService->delete($installment);

        return Utilities::okay("Installment Duration deleted successfully");
    }
    
}
