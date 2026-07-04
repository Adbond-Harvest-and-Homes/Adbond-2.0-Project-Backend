<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\UpdateDiscount;
use app\Http\Requests\User\UpdateInstallmentDiscount;

use app\Http\Resources\InstallmentDiscountResource;
use app\Http\Resources\DiscountResource;

use app\Services\DiscountService;

use app\Enums\DiscountType;

use app\Utilities;

class DiscountController extends Controller
{
    private $userActivityLogService;

    protected $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->discountService = $discountService;
    }

    public function fullPayment()
    {
        $fullPayment = $this->discountService->getFullPayment();

        return Utilities::ok(
            ["discount" => $fullPayment->discount]
        );
    }

    public function getBondDiscounts()
    {
        $discounts = $this->discountService->getBondDiscounts();

        return Utilities::ok(
            DiscountResource::collection($discounts)
        );
    }

    public function updateFullPayment(UpdateDiscount $request)
    {
        $data = $request->validated();
        $data['type'] = DiscountType::FULL_PAYMENT->value;
        $fullPayment = $this->discountService->updateDiscount($data);

        try {
            $this->userActivityLogService->log(Auth::user(), "Updated Full Payment Discount");
        } catch (\Exception $e) {
            Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
        }

        return Utilities::ok(
            ["discount" => $fullPayment->discount]
        );
    }

    public function updateBond(UpdateDiscount $request)
    {
        $data = $request->validated();
        $data['type'] = (isset($data['installment'])) ? DiscountType::BOND_INSTALLMENT->value : DiscountType::BOND->value;
        $discountObj = $this->discountService->updateDiscount($data);

        try {
            $this->userActivityLogService->log(Auth::user(), "Updated Bond Discount");
        } catch (\Exception $e) {
            Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
        }

        return Utilities::ok(
            ["discount" => $discountObj->discount]
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


        try {
            $this->userActivityLogService->log(Auth::user(), "Updated Installment Discounts");
        } catch (\Exception $e) {
            Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
        }

        return Utilities::ok(InstallmentDiscountResource::collection($installments));
    }

    public function addInstallmentDiscounts(UpdateInstallmentDiscount $request)
    {
        $data = $request->validated();

        $this->discountService->addInstallmentDiscounts($data);

        $installments = $this->discountService->getInstallmentDurations();


        try {
            $this->userActivityLogService->log(Auth::user(), "Added Installment Discounts");
        } catch (\Exception $e) {
            Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
        }

        return Utilities::ok(InstallmentDiscountResource::collection($installments));
    }

    public function deleteInstallment($duration)
    {
        $installment = $this->discountService->getInstallmentDuration($duration);
        if (!$installment) return Utilities::error402("Could not find this installment duration");

        $this->discountService->delete($installment);


        try {
            $this->userActivityLogService->log(Auth::user(), "Deleted Installment Discount");
        } catch (\Exception $e) {
            Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
        }

        return Utilities::okay("Installment Duration deleted successfully");
    }
}
