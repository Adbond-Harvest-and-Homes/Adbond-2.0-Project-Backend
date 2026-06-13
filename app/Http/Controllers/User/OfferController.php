<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\ApproveOffer;
use app\Http\Requests\User\RejectOffer;
use app\Http\Requests\User\CompleteOffer;

use app\Http\Resources\OfferResource;

use app\Services\OfferService;
use app\Services\PaymentService;
use app\Services\ClientPackageService;

use app\Models\Offer;

use app\Enums\OfferApprovalStatus;

use app\Utilities;

class OfferController extends Controller
{
    private $userActivityLogService;

    private $offerService;
    private $clientPackageService;
    private $paymentService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->offerService = new OfferService;
        $this->clientPackageService = new ClientPackageService;
        $this->paymentService = new PaymentService;
    }

    public function offers(Request $request)
    {
        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if(!is_int((int) $page) || $page <= 0) $page = 1;
        if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE');
        $offset = $perPage * ($page-1);

        // $filter['status'] = OfferApprovalStatus::PENDING->value;
        // $this->offerService->filter = $filter;

        $offers = $this->offerService->offers(['client'], $offset, $perPage);

        $this->offerService->count = true;
        $offersCount = $this->offerService->offers();

        return Utilities::paginatedOkay(OfferResource::collection($offers), $page, $perPage, $offersCount);
    }

    public function approve(ApproveOffer $request)
    {
        try{
            $data = $request->validated();
            $offer = $this->offerService->offer($data['offerId']);
            if(!$offer) return Utilities::error402("Offer not found");

            if($offer->approved && $offer->approved==0) return Utilities::error402("This offer has been rejected");

            if($offer->approved && $offer->approved==1) return Utilities::error402("This offer has already been Approved");

            $offer = $this->offerService->approve($offer, Auth::user()->id);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Approved Offer");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Offer approved successfully");

        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to send verification mail, Please try again later or contact support');
        }
    }

    public function reject(RejectOffer $request)
    {
        try{
            $data = $request->validated();
            $offer = $this->offerService->offer($data['offerId']);
            if(!$offer) return Utilities::error402("Offer not found");

            if($offer->approved && $offer->approved==0) return Utilities::error402("This offer has already been rejected");

            if($offer->approved && $offer->approved==1) return Utilities::error402("This offer has been Approved");

            $offer = $this->offerService->reject($offer, $data['reason'], Auth::user()->id);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Rejected Offer");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Offer Rejected");

        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to perform this operation, Please try again later or contact support');
        }
    }

    public function readyOffers(Request $request)
    {
        $offers = $this->offerService->readyOffers();

        return Utilities::ok(OfferResource::collection($offers));
    }


    // handles completing the offer sale and transferring the property to the new client
    public function complete(CompleteOffer $request)
    {
        try{
            $offer = $this->offerService->offer($request->validated("offerId"));
            if(!$offer) return Utilities::error402("Offer not found");

            if($offer->completed == 1) return Utilities::error402("This offer is already complete");

            $offerPayment = $this->paymentService->getPurchasePayment($offer->id, Offer::$type);
            if(!$offerPayment) return Utilities::error402("The payment for this offer was not found");

            DB::beginTransaction();

            $offer = $this->offerService->completeOffer($offer, $offerPayment);

            $this->clientPackageService->markAsSold($offer->asset);

            DB::commit();

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Completed Offer");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new OfferResource($offer));
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to perform this operation, Please try again later or contact support');
        }
    }
}
