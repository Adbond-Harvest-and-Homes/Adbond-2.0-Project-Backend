<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\CreatePromoCode;
use app\Http\Requests\User\UpdatePromoCode;
use app\Http\Requests\User\Toggle;

use app\Http\Resources\PromoCodeResource;

use app\Services\PromoCodeService;

use app\Utilities;

class PromoCodeController extends Controller
{
    private $userActivityLogService;

    private $promoCodeService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->promoCodeService = new PromoCodeService;
    }

    public function createPromoCode(CreatePromoCode $request)
    {
        $data = $request->validated();
        $promoCode = $this->promoCodeService->save($data);

        
            try {
                $this->userActivityLogService->log(Auth::user(), "Created Promo Code");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new PromoCodeResource($promoCode));
    }

    public function update(UpdatePromoCode $request, $promoCodeId)
    {
        try{
            if (!is_numeric($promoCodeId) || !ctype_digit($promoCodeId)) return Utilities::error402("Invalid parameter promoCodeId");

            $promoCode = $this->promoCodeService->promoCodeById($promoCodeId);
            if(!$promoCode) return Utilities::error402("Promo Code not found");

            $data = $request->validated();

            $promoCode = $this->promoCodeService->update($data, $promoCode);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Updated Promo Code");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new PromoCodeResource($promoCode));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function toggleActivate(Toggle $request)
    {
        $promoCode = $this->promoCodeService->promoCodeById($request->validated("id"));

        if(!$promoCode) return Utilities::error402("Promo Code not found");

        $promoCode = $this->promoCodeService->toggleActivate($promoCode);

        $action = ($promoCode->active) ? "Activated" : "Deactivated";

        
            try {
                $this->userActivityLogService->log(Auth::user(), "Toggled Promo Code Activation State");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Promo Code has been ".$action);
    }

    public function promoCodes()
    {
        $promoCodes = $this->promoCodeService->promoCodes(['promo']);

        return Utilities::ok(PromoCodeResource::collection($promoCodes));
    }

    public function delete($promoCodeId)
    {
        try{
            if (!is_numeric($promoCodeId) || !ctype_digit($promoCodeId)) return Utilities::error402("Invalid parameter promoCodeId");

            $promoCode = $this->promoCodeService->promoCodeById($promoCodeId);
            if(!$promoCode) return Utilities::error402("Promo Code not found");

            $this->promoCodeService->delete($promoCode);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Deleted Promo Code");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Promo Code Deleted");
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }
}
