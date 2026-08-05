<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\RedeemCommission;
use app\Http\Requests\User\CompleteReferralCommissionPayment;

use app\Http\Resources\StaffCommissionEarningResource;
use app\Http\Resources\TotalStaffCommissionEarningsResource;
use app\Http\Resources\StaffCommissionRedemptionResource;

use app\Services\CommissionService;

use app\Utilities;

class CommissionController extends Controller
{
    public function __construct(protected CommissionService $commissionService) {}

    public function commissions()
    {
        $staffId = Auth::user()->id;

        if ($staffId && (!is_numeric($staffId) || !ctype_digit($staffId))) return Utilities::error402("Invalid parameter staffId");
        $userId = ($staffId) ? $staffId : Auth::user()->id;
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
}
