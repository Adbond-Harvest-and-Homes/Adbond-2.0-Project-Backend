<?php

namespace app\Http\Controllers\User;

use app\Http\Controllers\Controller;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use app\Http\Resources\ProjectResource;
use app\Http\Resources\ProjectTypeResource;
use app\Http\Resources\TransactionResource;

use app\Services\ProjectTypeService;
use app\Services\ProjectService;
use app\Services\ClientService;
use app\Services\ClientPackageService;
use app\Services\PurchaseService;
use app\Services\TransactionService;
use app\Services\MetricService;
use app\Services\UserService;

use app\Models\ClientPurchasesSummaryView;

use app\Enums\ProjectFilter;
use app\Enums\PurchaseSummaryDuration;

use app\Utilities;
use app\EnumClass;
use app\Enums\MetricDuration;

class IndexController extends Controller
{
    private $userActivityLogService;
    private $userService;
    private $projectTypeService;
    private $projectService;
    private $clientService;
    private $clientPackageService;
    private $purchaseService;
    private $transactionService;
    private $metricService;

    public function __construct()
    {
        $this->userService = new UserService;
        $this->userActivityLogService = new UserActivityLogService;
        $this->projectTypeService = new ProjectTypeService;
        $this->projectService = new ProjectService;
        $this->clientService = new ClientService;
        $this->clientPackageService = new ClientPackageService;
        $this->purchaseService = new PurchaseService;
        $this->transactionService = new TransactionService;
        $this->metricService = new MetricService;
    }

    public function dashboard(Request $request)
    {
        $projectTypes = $this->projectTypeService->projectTypes();
        $projectTypeObj = null;

        $this->purchaseService->summaryDuration = PurchaseSummaryDuration::WEEK->value;

        $purchaseChart = $this->purchaseService->clientPurchaseSummary();
        $purchaseTotal = 0;
        if ($purchaseChart->count() > 0) {
            foreach ($purchaseChart as $purchase) {
                $purchaseTotal += $purchase->total_amount;
            }
        }

        $projectTypesCounts = [];
        if (count($projectTypes) > 0) {
            foreach ($projectTypes as $projectType) {
                if (!$projectTypeObj) $projectTypeObj = $projectType;
                $projectTypesCounts[$projectType->name] = [
                    "totalProjects" => $projectType->projects->count(),
                    "activeProjects" => $projectType->activeProjects->count(),
                    "inactiveProjects" => $projectType->inactiveProjects->count()
                ];
            }
        }
        unset($projectTypeObj['projects']);

        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if (!is_int((int) $page) || $page <= 0) $page = 1;
        if (!is_int((int) $perPage) || $perPage == null) $perPage = null;
        $offset = $perPage * ($page - 1);

        $duration = ($request->query('duration'));
        $validDurations = [MetricDuration::TODAY->value, MetricDuration::WEEK->value, MetricDuration::MONTH->value, MetricDuration::YEAR->value];
        if (!in_array($duration, $validDurations)) $duration = MetricDuration::TODAY->value;

        $this->projectService->typeId = $projectTypeObj->id;
        $projects = $this->projectService->projects([], $offset, $perPage);

        $this->projectService->typeId = null;

        $this->projectService->count = true;
        $projectsCount = $this->projectService->projects();

        $this->projectService->status = ProjectFilter::ACTIVE->value;
        $projectsActiveCount = $this->projectService->projects();

        $this->clientService->active = true;
        $this->clientService->count = true;
        $activeClientsCount = $this->clientService->clients();

        $this->clientPackageService->count = true;
        $assetsCount = $this->clientPackageService->assets();

        $this->clientPackageService->active = true;
        $activeAssetsCount = $this->clientPackageService->assets();

        $projectMetric = $this->metricService->projectMetric($duration);
        $assetMetric = $this->metricService->assetMetric($duration);
        $clientMetric = $this->metricService->clientMetric($duration);

        $summary = [
            "projectsCount" => $projectsCount,
            "activeProjectsCount" => $projectsActiveCount,
            "activeClientsCount" => $activeClientsCount,
            "assetsCount" => $assetsCount,
            "activeAssetsCount" => $activeAssetsCount,
            "activeProjectsMetric" => $projectMetric['active'],
            "activeClientsMetric" => $clientMetric['active'],
            "totalAssetsMetric" => $assetMetric['total'],
            "activeAssetsMetric" => $assetMetric['active']
        ];

        // transactions
        $perPage = 10;
        $offset = 0;

        $transactions = $this->transactionService->transactions(['client'], $offset, $perPage);

        return Utilities::ok([
            "summary" => $summary,
            "purchaseTotal" => $purchaseTotal,
            "purchaseChart" => $purchaseChart,
            "projectTypes" => $projectTypesCounts,
            "projects" => ProjectResource::collection($projects),
            "activeProjectType" => new ProjectTypeResource($projectTypeObj),
            "transactions" => TransactionResource::collection($transactions)
        ]);
    }

    public function dashboard2(Request $request)
    {
        try {
            $summary = $this->userService->getStaffSalesSummary(Auth::user()->id);

            $clients = $this->userService->getMyClients(Auth::user()->id);

            return Utilities::ok([
                "summary" => [
                    "totalClients" => $summary->total_clients,
                    "commisiionEarned" => $summary->total_commission,
                    "totalSales" => $summary->total_sales
                ],
                "clients" => $clients
            ]);
        } catch (\Exception $e) {
            return Utilities::error($e, "An error occurred while fetching data");
        }
    }

    public function purchaseSummary(Request $request)
    {
        $summaryDuration = ($request->query('summaryDuration')) ?? PurchaseSummaryDuration::WEEK->value;
        if (!in_array($summaryDuration, EnumClass::purchaseSummaryDurations())) return Utilities::error402("Invalid Summary Duration");

        $this->purchaseService->summaryDuration = $summaryDuration;
        if ($summaryDuration == PurchaseSummaryDuration::CUSTOM->value) {
            $start = $request->query('start');
            $end = $request->query('end');

            if (!$start) return Utilities::error402("Custom Start Date is required");

            $this->purchaseService->start = $start;
            if ($end) $this->purchaseService->end = $end;
        }

        $this->purchaseService->summaryDuration = $summaryDuration;

        $purchaseChart = $this->purchaseService->clientPurchaseSummary();
        $purchaseTotal = 0;
        if ($purchaseChart->count() > 0) {
            foreach ($purchaseChart as $purchase) {
                $purchaseTotal += $purchase->total_amount;
            }
        }

        return Utilities::ok(["purchaseTotal" => $purchaseTotal, "purchaseChart" => $purchaseChart]);
    }
}
