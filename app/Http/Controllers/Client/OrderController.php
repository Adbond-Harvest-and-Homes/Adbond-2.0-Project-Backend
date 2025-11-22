<?php

namespace app\Http\Controllers\Client;

use Illuminate\Http\Request;
use app\Http\Controllers\Controller;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

use app\Http\Requests\Client\PrepareOrder;

use app\Services\OrderService;
use app\Services\PromoCodeService;
use app\Services\PromoService;
use app\Services\PackageService;

use app\Enums\PackageType;
use app\Enums\InvestmentRedemptionOption;

use app\Utilities;

class OrderController extends Controller
{
    private $orderService;
    private $promoCodeService;
    private $promoService;
    private $packageService;

    public function __construct()
    {
        $this->orderService = new OrderService;
        $this->packageService = new PackageService;
        $this->promoCodeService = new PromoCodeService;
        $this->promoService = new PromoService;
    }

    public function prepareOrder(PrepareOrder $request)
    {
        try{
            $data = $request->validated();

            $package = $this->packageService->package($data['packageId']);
            if(!$package) return Utilities::error402("Package was not found");

            if($package->type == PackageType::INVESTMENT->value) {
                if(!isset($data['redemptionOption'])) return Utilities::error402("You must select a redemption option");
                if($data['redemptionOption'] == InvestmentRedemptionOption::PROFIT_ONLY->value || $data['redemptionOption'] == InvestmentRedemptionOption::PROPERTY->value) {
                    // if(!isset($data['redemptionPackageId'])) return Utilities::error402("You must select the redemption package");
                    $data['redemptionPackageId'] = $package->redemption_package_id;
                }
            }

            if($package->available_units < $data['units']) return Utilities::error402("Available Units is not up to ".$data['units']);

            $promoCodeDiscount = (isset($data['promoCode'])) ? $this->promoCodeService->validatePromoCode($data['promoCode'], $package)['discount'] : null;
            $promos = $this->promoService->getPromos($package, Auth::guard('client')->user());
            $processingData = ["amount" => ($package->amount * $data['units']), "isInstallment" => $data['isInstallment'], "packageType" => $package->type];
            if($data['isInstallment']) $processingData['duration'] = $data['installmentCount'];
            $amountDetail = $this->orderService->getPayable($processingData, $promos, $promoCodeDiscount);

            $processingId  = (isset($data['processingId'])) ? $data['processingId'] : Utilities::getOrderProcessingId();
            
            $data['amountDetail'] = $amountDetail;

            if($data['isInstallment']) {
                // if its installment payment, ensure that the client is not paying less than the minimum
                $minPayable = round($amountDetail['amount']/$data['installmentCount'], 2);
                if($data['amount'] < $minPayable) return Utilities::error402("You cannot pay less than ".$minPayable);
                if($data['amount'] > $amountDetail['amount']) return Utilities::error402("you cannot pay more than ".$amountDetail['amount']);
            }

            $data['amountPayable'] = ($data['isInstallment']) ? $data['amount'] : $amountDetail['amount'];
            // Cache this data to be used to complete the order processing
            if(isset($data['processingId'])) Cache::forget('order_processing_' . $processingId);
            Cache::put('order_processing_' . $processingId, $data, now()->addHours(12));

            return Utilities::ok([
                "processingId" => $processingId,
                "package" => $package->name,
                "units" => $data['units'],
                "amountPerUnit" => $package->amount,
                "totalAmount" => $package->amount * $data['units'],
                "amountPayable" => $data['amountPayable'],
                "appliedDiscounts" => $amountDetail
            ]);
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to perform this operation, Please try again later or contact support');
        }
    }
}
