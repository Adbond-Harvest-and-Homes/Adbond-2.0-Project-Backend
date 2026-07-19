<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\ApproveAssetSwitch;
use app\Http\Requests\User\RejectAssetSwitch;

use app\Http\Resources\AssetSwitchRequestResource;

use app\Services\AssetSwitchService;
use app\Services\PackageService;
use app\Services\ClientPackageService;

use app\Enums\AssetSwitchType;
use app\Enums\ClientPackageOrigin;

use app\Utilities;

class AssetSwitchController extends Controller
{
    private $userActivityLogService;

    private $assetSwitchService;
    private $packageService;
    private $clientPackageService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->assetSwitchService = new AssetSwitchService;
        $this->packageService = new PackageService;
        $this->clientPackageService = new ClientPackageService;
    }

    public function assetSwitchRequests(Request $request)
    {
        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if(!is_int((int) $page) || $page <= 0) $page = 1;
        if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE');
        $offset = $perPage * ($page-1);

        $default = false;
        if($request->query('status')) {
            $this->assetSwitchService->setStatus = true;
            $validStatuses = ['pending', 'approved', 'rejected'];
            $validStatusesString = '';
            foreach($validStatuses as $valid) $validStatusesString .= $valid.', ';
            if(!in_array($request->query('status'), $validStatuses)) return Utilities::error402("Valid Statuses are: ".$validStatusesString);
            switch($request->query('status')) {
                case "pending" : $this->assetSwitchService->approved = null; break;
                case "approved" : $this->assetSwitchService->approved = 1; break;
                case "rejected" : $this->assetSwitchService->approved = 0; break;
            }
            $default = $this->assetSwitchService->approved;
        }
        $requests = $this->assetSwitchService->assetSwitchRequests([], $offset, $perPage);

        $this->assetSwitchService->count = true;
        $requestsCount = $this->assetSwitchService->assetSwitchRequests();

        $this->assetSwitchService->setStatus = true;
        $this->assetSwitchService->approved = 1;
        $approvedCount = $this->assetSwitchService->assetSwitchRequests();
        // dd($approvedCount);

        $this->assetSwitchService->approved = 0;
        $rejectedCount = $this->assetSwitchService->assetSwitchRequests();
        // dd($rejectedCount);

        $this->assetSwitchService->approved = null;
        $pendingCount = $this->assetSwitchService->assetSwitchRequests();

        if($default !== false) {
            switch($default) {
                case null : $defaultTotal = $pendingCount; break;
                case 1 : $defaultTotal = $approvedCount; break;
                case 0 : $defaultTotal = $rejectedCount; break;
            }
        }else{
            $defaultTotal = $requestsCount;
        }

        return Utilities::paginatedOkay([
            "requests" => AssetSwitchRequestResource::collection($requests),
            "requestsCount" => $requestsCount,
            "approvedCount" => $approvedCount,
            "pendingCount" => $pendingCount,
            "rejectedCount" => $rejectedCount
        ], $page, $perPage, $defaultTotal);
    }

    public function approve(ApproveAssetSwitch $request)
    {
        try{
            DB::beginTransaction();

            $data = $request->validated();
            $request = $this->assetSwitchService->assetSwitchRequest($data['requestId']);
            if(!$request) return Utilities::error402("Asset Switch Request not found");

            $request = $this->assetSwitchService->approve($request);
            if($request->type == AssetSwitchType::DOWNGRADE->value) {
                $assetSwitch = $this->assetSwitchService->downgrade($request, $request->packageTo);
            }else{
                $assetSwitch = $this->assetSwitchService->upgrade($request, $request->packageTo);
            }

            //if the purchase is complete, send the required documents
            if($assetSwitch?->asset?->purchase_complete == 1) {
                $order = ($assetSwitch?->asset?->origin == ClientPackageOrigin::INVESTMENT->value) ? $assetSwitch?->asset?->purchase?->order : $assetSwitch?->asset?->purchase;
                if($order) $this->clientPackageService->uploadContract($order, $assetSwitch->asset);
                $payment = $order->payments->first();
                if($payment) $this->clientPackageService->uploadLetterOfHappiness($payment, $assetSwitch->asset);
            }

            DB::commit();

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Approved Asset Switch Request");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Asset has been ".$request->type."d successfully");
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function reject(RejectAssetSwitch $request)
    {
        try{
            $data = $request->validated();
            $request = $this->assetSwitchService->assetSwitchRequest($data['requestId']);
            if(!$request) return Utilities::error402("Asset Switch Request not found");

            $request = $this->assetSwitchService->reject($request, $data['reason']);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Rejected Asset Switch Request");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Asset Switch Request rejected");
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }
}
