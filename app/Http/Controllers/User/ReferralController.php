<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\RedeemCommission;
use app\Http\Requests\User\CompleteReferralCommissionPayment;

use app\Http\Resources\StaffCommissionEarningResource;
use app\Http\Resources\TotalStaffCommissionEarningsResource;
use app\Http\Resources\StaffCommissionRedemptionResource;

use app\Services\CommissionService;

use app\Utilities;
use app\EnumClass;

class ReferralController extends Controller
{
    private $userActivityLogService;

    private $commissionService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->commissionService = new CommissionService;
    }

    public function referralCommissions()
    {
        // return all the staffs that has referrals with the number of referrals that they have

        $totalCommissions = $this->commissionService->getTotalStaffsEarnings();

        return Utilities::ok(TotalStaffCommissionEarningsResource::collection($totalCommissions));
    }

    public function referralEarnings(Request $request, $staffId = null)
    {
        if ($staffId && (!is_numeric($staffId) || !ctype_digit($staffId))) return Utilities::error402("Invalid parameter staffId");
        $userId = ($staffId) ? $staffId : Auth::user()->id;

        $type = ($request->query('type')) ?? null;
        $installment = ($request->query('installment')) ?? null;

        if ($installment && !in_array($installment, [0, 1])) return Utilities::error402("Invalid parameter installment");

        if ($type) {
            if (!in_array($type, EnumClass::staffCommissionTypes())) return Utilities::error402("Invalid parameter type");
            $this->commissionService->type = $type;
        }

        if ($installment) {
            if (!in_array($installment, [0, 1])) return Utilities::error402("Invalid parameter installment");
            $this->commissionService->installment = $installment;
        }

        $earnings = $this->commissionService->getStaffEarnings($userId);

        $totalEarnings = $this->commissionService->getTotalStaffEarnings($userId);
        $totalRedemptions = $this->commissionService->getTotalStaffRedemptions($userId);
        $balance = $totalEarnings - $totalRedemptions;

        return Utilities::ok([
            "totalEarnings" => $totalEarnings,
            "totalRedemption" => $totalRedemptions,
            "balance" => $balance,
            "earnings" => StaffCommissionEarningResource::collection($earnings)
        ]);
    }

    public function staffRedemptions($staffId = null)
    {
        if ($staffId && (!is_numeric($staffId) || !ctype_digit($staffId))) return Utilities::error402("Invalid parameter staffId");
        $userId = ($staffId) ? $staffId : Auth::user()->id;

        $with = ($staffId) ? ['user'] : [];

        $redemptions = $this->commissionService->commissionRedemptions($with, $userId);

        return Utilities::ok(StaffCommissionRedemptionResource::collection($redemptions));
    }

    public function commissionRedemptions()
    {
        $redemptions = $this->commissionService->commissionRedemptions(['user']);

        return Utilities::ok(StaffCommissionRedemptionResource::collection($redemptions));
    }

    public function redeem(RedeemCommission $request)
    {
        try {
            $data = $request->validated();
            if ($this->commissionService->pendingRedemption(Auth::user()->id)) {
                return Utilities::error402("You have a pending redemption, wait for it to be resolved before raising another redemption");
            }
            $totalEarnings = $this->commissionService->getTotalStaffEarnings(Auth::user()->id);
            if ($data['amount'] > $totalEarnings) return Utilities::error402("Your proposed Redemption Amount is more than your earning");
            $data['userId'] = Auth::user()->id;

            $this->commissionService->redeemCommission($data);


            try {
                $this->userActivityLogService->log(Auth::user(), "Redeemed Referral Commission");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Redemption Request Successful");
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function completePayment(CompleteReferralCommissionPayment $request)
    {
        try {
            $redemption = $this->commissionService->commissionRedemption($request->validated('redemptionId'));

            $this->commissionService->completeRedemption($redemption);


            try {
                $this->userActivityLogService->log(Auth::user(), "Completed Referral Redemption Payment");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Referral Earning Redemption Completed");
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }
}
