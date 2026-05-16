<?php

namespace app\Http\Controllers\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use app\Http\Controllers\Controller;

use app\Exceptions\AppException;

use app\Http\Requests\Client\RedeemBond;
use app\Http\Requests\Client\RenewBond;

use app\Http\Resources\ClientBondResource;
use app\Http\Resources\ClientBondPayoutResource;

use app\Services\ClientBondService;
use app\Services\ClientBondRequestService;
use app\Services\OrderService;
use app\Services\NotificationService;

use app\Enums\OrderType;
use app\Enums\ClientBondStatus;
use app\Enums\ClientBondRequestType;
use app\Enums\NotificationType;

use app\Utilities;

class BondController extends Controller
{
    public function __construct(protected ClientBondService $bondService, 
                                    protected OrderService $orderService, 
                                    protected ClientBondRequestService $requestService,
                                    protected NotificationService $notificationService
                                )
    {
    }

    public function bonds()
    {
        $this->bondService->clientId = Auth::guard("client")->user()->id;
        $bonds = $this->bondService->getBonds(["package", "mou"]);
        $summary = $this->bondService->clientBondSummary();

        return Utilities::ok([
            "bonds" => ClientBondResource::collection($bonds),
            "summary" => $summary
        ]);
    }

    public function payouts()
    {
        $this->bondService->clientId = Auth::guard("client")->user()->id;
        $payouts = $this->bondService->getBondPayouts(['bond.media']);
        $payoutSummary = $this->bondService->getClientPayoutSummary(Auth::guard("client")->user()->id);
        $summary = [
            "totalEarned" => ($payoutSummary) ? $payoutSummary->total_payouts : null,
            "expectedAnnualIncome" => ($payoutSummary) ? $payoutSummary->expected_annual_payout : null,
            "nextPayout" => ($payoutSummary) ? $payoutSummary->next_payout_date?->format('jS M, Y') : null,
            "activeAssets" => ($payoutSummary) ? $payoutSummary->active_bond_count : null
        ];


        return Utilities::ok([
            "payouts" => ClientBondPayoutResource::collection($payouts),
            "summary" => $summary
        ]);
    }

    public function redeem(RedeemBond $request)
    {
        DB::beginTransaction();
        try{
            $bond = $this->bondService->getBond($request->validated("id"));
            if(!$bond) return Utilities::error402("Bond not found");

            if($bond->client_id != Auth::guard("client")->user()->id) return Utilities::error402("You cannot renew a bond you do not own!");

            if($bond->status != ClientBondStatus::COMPLETED->value) {
                if($bond->status == ClientBondStatus::LIQUIDATION_REQUEST->value) return Utilities::error402("There's a pending request on this bond");
                return Utilities::error402("This bond is not eligible for liquidation");
            }

            // $this->bondService->redeem($bond);
            // make a request to liquidate bond
            $bondRequest = $this->requestService->makeRequest($bond, ClientBondRequestType::LIQUIDATION->value);

            //Update the status of the bond to liquidation request
            $this->bondService->updateStatus($bond, ClientBondStatus::LIQUIDATION_REQUEST->value);

            // Register a notification for this request
            $this->notificationService->save($bondRequest, NotificationType::BOND_LIQUIDATION_REQ->value,  Auth::guard("client")->user());

            DB::commit();

            return Utilities::okay("Bond Liquidation request has been made Successfully");
        }catch(AppException $e){
            DB::rollBack();
            throw $e;
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to redeem bond, Please try again later or contact support');
        }
    }

    public function renew(RenewBond $request)
    {
        DB::beginTransaction();
        try{
            $bond = $this->bondService->getBond($request->validated("id"));
            if(!$bond) return Utilities::error402("Bond not found");

            if($bond->client_id != Auth::guard("client")->user()->id) return Utilities::error402("You cannot renew a bond you do not own!");

            if($bond->status != ClientBondStatus::COMPLETED->value) {
                if($bond->status == ClientBondStatus::LIQUIDATION_REQUEST->value) return Utilities::error402("There's a pending request on this bond");
                return Utilities::error402("This bond is not eligible for liquidation");
            }

            // make a request to renew bond
            $bondRequest = $this->requestService->makeRequest($bond, ClientBondRequestType::RENEWAL->value);

            //Update the status of the bond to renewal request
            $this->bondService->updateStatus($bond, ClientBondStatus::RENEWAL_REQUEST->value);

            // Register a notification for this request
            $this->notificationService->save($bondRequest, NotificationType::BOND_RENEWAL_REQ->value,  Auth::guard("client")->user());
            // get the package from the bond
            // $package = $bond->package;

            // // build the new order
            // $orderData = [
            //     "clientId" => $bond->client_id,
            //     "packageId" => $package->id,
            //     "units" => $bond->order->units,
            //     "amountPayable" => $bond->order->amount_payable,
            //     "amountPayed" => $bond->order->amount_payable,
            //     "balance" => 0,
            //     "unitPrice" => $bond->order->unit_price,
            //     "isInstallment" => 0,
            //     "paymentStatusId" => $bond->order->payment_status_id,
            //     "orderDate" => now(),
            //     "type" => OrderType::BOND_REINVEST->value
            // ];

            // // create new order
            // $order = $this->orderService->save($orderData);

            // $renewedBond = $this->bondService->renew($bond, $order);

            DB::commit();

            return Utilities::okay("Bond Renewal request has been made Successfully");
            // return Utilities::ok(new ClientBondResource($renewedBond));

        }catch(AppException $e){
            DB::rollBack();
            throw $e;
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to renew bond, Please try again later or contact support');
        }
    }
}
