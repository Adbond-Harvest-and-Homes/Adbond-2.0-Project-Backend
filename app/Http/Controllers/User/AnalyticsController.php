<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Services\PurchaseService;
use app\Services\ProjectService;

use app\Enums\PurchaseSummaryDuration;

use app\Utilities;

class AnalyticsController extends Controller
{
    private $userActivityLogService;

    private $purchaseService;
    private $projectService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->purchaseService = new PurchaseService;
        $this->projectService = new ProjectService;
    }


    public function salesOverview(Request $request)
    {
        $duration = ($request->query('duration'));
        $validDurations = [PurchaseSummaryDuration::TODAY, PurchaseSummaryDuration::WEEK->value, PurchaseSummaryDuration::MONTH->value, 
                            PurchaseSummaryDuration::YEAR->value, PurchaseSummaryDuration::ALL->value, PurchaseSummaryDuration::CUSTOM->value];
        if(!$duration || !in_array($duration, $validDurations)) $duration = PurchaseSummaryDuration::MONTH->value;

        if($duration == PurchaseSummaryDuration::CUSTOM->value) {
            $start = $request->query('start');
            $end = $request->query('end');

            if(!$start) return Utilities::error402("Custom Start Date is required");

            $this->purchaseService->start = $start;
            if($end) $this->purchaseService->end = $end;
        }

        $this->purchaseService->summaryDuration = $duration;

        $purchaseChart = $this->purchaseService->clientPurchaseSummary();
        $purchaseTotal = 0;
        if($purchaseChart->count() > 0) {
            foreach($purchaseChart as $purchase) {
                $purchaseTotal += $purchase->total_amount;
            }
        }

        return Utilities::ok([
            "purchaseTotal" => $purchaseTotal,
            "purchaseChart" => $purchaseChart,
        ]);
    }

    public function projectTypes()
    {
        $projectTypesSummary = $this->projectService->projectTypesSummary();
        return Utilities::ok($projectTypesSummary);
    }
}
