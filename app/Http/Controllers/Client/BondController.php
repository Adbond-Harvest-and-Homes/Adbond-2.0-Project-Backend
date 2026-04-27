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

use app\Services\ClientBondService;
use app\Services\OrderService;

use app\Enums\OrderType;

use app\Utilities;

class BondController extends Controller
{
    public function __construct(protected ClientBondService $bondService, protected OrderService $orderService)
    {
    }

    public function bonds()
    {
        $bonds = $this->bondService->getBonds(["package", "mou"]);

        return Utilities::ok(ClientBondResource::collection($bonds));
    }

    public function redeem(RedeemBond $request)
    {
        try{
            $bond = $this->bondService->getBond($request->validated("id"));
            if(!$bond) return Utilities::error402("Bond not found");

            if($bond->client_id != Auth::guard("client")->user()->id) return Utilities::error402("You cannot renew a bond you do not own!");

            $this->bondService->redeem($bond);

            return Utilities::okay("Bond redemption successful.. Go to your wallet and redeem your funds");
        }catch(AppException $e){
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
            $bond = $this->bondService->getBond($request->validated("bondId"));
            if(!$bond) return Utilities::error402("Bond not found");

            if($bond->client_id != Auth::guard("client")->user()->id) return Utilities::error402("You cannot renew a bond you do not own!");

            // get the package from the bond
            $package = $bond->package;

            // build the new order
            $orderData = [
                "clientId" => $bond->client_id,
                "packageId" => $package->id,
                "units" => $bond->order->units,
                "amountPayable" => $bond->order->amount_payable,
                "amountPayed" => $bond->order->amount_payable,
                "balance" => 0,
                "unitPrice" => $bond->order->unit_price,
                "isInstallment" => 0,
                "paymentStatusId" => $bond->order->payment_status_id,
                "orderDate" => now(),
                "type" => OrderType::BOND_REINVEST->value
            ];

            // create new order
            $order = $this->orderService->save($orderData);

            $renewedBond = $this->bondService->renew($bond, $order);

            DB::commit();

            return Utilities::ok(new ClientBondResource($renewedBond));

        }catch(AppException $e){
            DB::rollBack();
            throw $e;
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to renew bond, Please try again later or contact support');
        }
    }
}
