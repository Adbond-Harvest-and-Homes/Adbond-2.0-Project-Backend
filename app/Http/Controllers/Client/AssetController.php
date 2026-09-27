<?php

namespace app\Http\Controllers\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Requests\Client\UpdateInstallment;
use app\Http\Requests\Client\AssignAssetOwner;

use app\Http\Resources\ClientAssetSummaryResource;
use app\Http\Resources\ClientAssetResource;
use app\Http\Resources\AssetResource;

use app\Services\ClientPackageService;
use app\Services\OrderService;
use app\Services\FamilyMemberService;

use app\Models\Order;

use app\Enums\ClientPackageOrigin;

use app\Utilities;

class AssetController extends Controller
{
    private $clientPackageService;
    private $orderService;
    private $familyMemberService;

    public function __construct()
    {
        $this->clientPackageService = new ClientPackageService;
        $this->orderService = new OrderService;
        $this->familyMemberService = new FamilyMemberService;
    }

    public function summary()
    {
        $summary = $this->clientPackageService->clientAssetSummary(Auth::guard('client')->user()->id);

        return Utilities::ok(new ClientAssetSummaryResource($summary));
    }

    public function assets(Request $request)
    {
        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if(!is_int((int) $page) || $page <= 0) $page = 1;
        if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE', 15);
        $offset = $perPage * ($page-1);

        $filter = [];
        if($request->query('text')) $filter["text"] = $request->query('text');
        if($request->query('date')) $filter["date"] = $request->query('date');
        
        $this->clientPackageService->filter = $filter;
        $assets = $this->clientPackageService->clientAssets(Auth::guard('client')->user()->id, ['package.media'], $offset, $perPage);

        // Get Count
        $this->clientPackageService->count = true;
        $assetsCount = $this->clientPackageService->clientAssets(Auth::guard('client')->user()->id);

        return Utilities::paginatedOkay(AssetResource::collection($assets), $page, $perPage, $assetsCount);
    }

    public function asset($assetId)
    {
        $asset = $this->clientPackageService->clientPackage($assetId, ['package.media']);
        if(!$asset) return Utilities::error402("Asset not found");

        if($asset->client_id != Auth::guard('client')->user()->id) return Utilities::error402("You are not permitted to view this asset");

        return Utilities::ok(new AssetResource($asset));
    }

    public function assignOwner(AssignAssetOwner $request, $assetId)
    {
        try {
            $data = $request->validated();

            $asset = $this->clientPackageService->clientPackage($assetId);
            if(!$asset) return Utilities::error402("Asset not found");

            if($asset->client_id != Auth::guard('client')->user()->id) return Utilities::error402("You are not permitted to modify this asset");

            if($asset->sold) return Utilities::error402("This property has already been sold/transferred and can no longer be reassigned");

            $familyMember = null;
            if(isset($data['familyMemberId'])) {
                $familyMember = $this->familyMemberService->find($data['familyMemberId'], Auth::guard('client')->user()->id);
                if(!$familyMember) return Utilities::error402("Family member not found");
            }

            $asset = $this->clientPackageService->assignOwner($asset, $familyMember);
            $asset = $this->clientPackageService->clientPackage($asset->id, ['package.media']);

            $message = ($familyMember) ? "Property assigned to {$familyMember->full_name} successfully" : "Property assigned back to you successfully";

            return Utilities::okay($message, new AssetResource($asset));
        } catch(\Exception $e) {
            return Utilities::error($e, 'An error occurred while trying to perform this operation, Please try again later or contact support');
        }
    }

    public function updateInstallment(UpdateInstallment $request)
    {
        try{
            $data = $request->validated();
            $asset = $this->clientPackageService->clientPackage($data['assetId']);
            if(!$asset) return Utilities::error402("Asset not found");

            if($asset->purchase_complete == 1) return Utilities::error402("This asset order cannot be modified because the purchase is complete");

            if($asset->origin != ClientPackageOrigin::ORDER->value && $asset->origin != ClientPackageOrigin::INVESTMENT->value) return Utilities::error402("Asset is not an Order");

            if($asset->purchase->is_installment == 0) return Utilities::error402("This purchase is not an installment purchase");

            $order = ($asset->origin == ClientPackageOrigin::ORDER->value) ? $asset->purchase : $asset->purchase->order;

            $order = $this->orderService->updateInstallmentCount($order, $data['count']);
            $asset = $this->clientPackageService->clientPackage($asset->id);

            return Utilities::ok(new AssetResource($asset));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to perform this operation, Please try again later or contact support');
        }
    }
}
