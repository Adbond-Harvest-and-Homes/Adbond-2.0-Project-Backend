<?php

namespace app\Services;

use DateTime;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

use app\Exceptions\AppException;

use app\Models\ClientBond;
use app\Models\ClientBondPayout;

use app\Mail\MOU;

use app\Enums\FilePurpose;
use app\Enums\FileTypes;
use app\Enums\BondOccurrenceMetric;
use app\Enums\Measurement;
use app\Enums\UserType;

use app\Services\ContractService;
use app\Services\WalletService;
use app\Services\ClientPackageService;

use app\Utilities;
use app\Helpers;

class ClientBondService
{
    public $clientId = null;

    public function getByOrderId($orderId)
    {
        return ClientBond::where("order_id", $orderId)->first();
    }

    public function getBond($id)
    {
        return ClientBond::find($id);
    }

    public function getBonds($with=[])
    {
        return ClientBond::with($with)->when($this->clientId, function($query) {
            $query->where("client_id", $this->clientId);
        })->orderBy("created_at", "DESC")->get();
    }

    public function runningBonds()
    {
        return ClientBond::where("started", 1)->where("ended", 0)->orderBy("created_at", "DESC")->get();
    }

    public function notStartedBonds()
    {
        return ClientBond::where("started", 0)->where("ended", 0)->orderBy("created_at", "DESC")->get();
    }

    public function getBondPayouts($with=[])
    {
        return ClientBondPayout::with($with)->when($this->clientId, function($query) {
            $query->where("client_id", $this->clientId);
        })->orderBy("created_at", "DESC")->get();
    }

    public function getBondPayout($id, $with=[])
    {
        return ClientBondPayout::with($with)->where("id", $id)->when($this->clientId, function($query) {
            $query->where("client_id", $this->clientId);
        })->first();
    }

    public function save($data)
    {
        $clientBond = ClientBond::where("order_id", $data['orderId'])->first();
        if(!$clientBond) $clientBond = new ClientBond;
        $clientBond->client_id = $data['clientId'];
        $clientBond->package_id = $data['packageId'];
        $clientBond->order_id = $data['orderId'];
        $clientBond->start_capital = $data['capital'];
        $clientBond->current_capital = $data['capital'];

        $clientBond->duration = $data['duration'];
        $clientBond->duration_metric = $data['durationMetric'];

        $clientBond->count_down = $data['countDown'];
        $clientBond->count_down_metric = $data['countDownMetric'];

        $clientBond->net_rental_income = $data['netRentalIncome'];
        $clientBond->net_rental_income_timeline = $data['netRentalIncomeTimeline'];
        $clientBond->net_rental_income_measurement = $data['netRentalIncomeMeasurement'];

        $clientBond->asset_appreciation = $data['assetAppreciation'];
        $clientBond->asset_appreciation_timeline = $data['assetAppreciationTimeline'];
        $clientBond->asset_appreciation_measurement = $data['assetAppreciationMeasurement'];

        if(isset($data['startDate'])) {
            $clientBond->start_date = $data['startDate'];
            $clientBond->end_date = $data['endDate'];
            $clientBond->next_capital_payout = $data['nextCapitalPayout'];
        }
        if(isset($data['parentBondId'])) $clientBond->parent_bond_id = $data['parentBondId'];

        $clientBond->save();

        return $clientBond;
    }

    public function start($bond)
    {
        $bond->start_date = (new DateTime())->modify('+'.$bond->count_down.' '.$bond->count_down_metric)->format('Y-m-d');

        $bond->end_date = (new DateTime($bond->start_date))->modify('+'.$bond->duration.' '.$bond->duration_metric)->format('Y-m-d');

        $nextCapitalPayoutDuration = $this->convertTimelineToDuration($bond->net_rental_income_timeline);
        $bond->next_capital_payout = (new DateTime($bond->start_date))->modify('+'.$nextCapitalPayoutDuration)->format('Y-m-d');
        $bond->update();
        return $bond;
    }

    public function getPayout($bond)
    {
        //if its a fixed amount, return that fixed amount as the payout
        if($bond->net_rental_income_measurement == Measurement::FIXED->value) return $bond->net_rental_income;

        return Utilities::getPercentageAmount($bond->current_capital, $bond->net_rental_income);
    }

    public function saveBond($order, $data)
    {
        $package = $order->package;
        $bondData['clientId'] = $order->client_id;
        $bondData['packageId'] = $order->package_id;
        $bondData['orderId'] = $order->id;
        $bondData['capital'] = $order->amount_payable;
        $bondData['countDown'] = $package->bond_count_down;
        $bondData['countDownMetric'] = $package->bond_count_down_metric;

        $bondData['duration'] = $package->bond_investment_duration;
        $bondData['durationMetric'] = $package->bond_investment_duration_metric;

        $bondData['netRentalIncomeTimeline'] = $package->bond_net_rental_income_timeline;
        $bondData['netRentalIncome'] = $package->bond_net_rental_income;
        $bondData['netRentalIncomeMeasurement'] = $package->bond_net_rental_income_measurement; 

        $bondData['assetAppreciation'] = $package->bond_asset_appreciation;
        $bondData['assetAppreciationMeasurement'] = $package->bond_asset_appreciation_measurement;
        $bondData['assetAppreciationTimeline'] = $package->bond_asset_appreciation_timeline;

        if($order->completed == 1) {
            $bondData = $this->calculateBondDates($bondData);
        }
        $clientBond = $this->save($bondData);

        return $clientBond;
    }

    private function calculateBondDates($bondData)
    {
        $bondData['startDate'] = (new DateTime())->modify('+'.$bondData['countDown'].' '.$bondData['countDownMetric'])->format('Y-m-d');

        $bondData['endDate'] = (new DateTime($bondData['startDate']))->modify('+'.$bondData['duration'].' '.$bondData['durationMetric'])->format('Y-m-d');

        $nextCapitalPayoutDuration = $this->convertTimelineToDuration($bondData['netRentalIncomeTimeline']);
        $bondData['nextCapitalPayout'] = (new DateTime($bondData['startDate']))->modify('+'.$nextCapitalPayoutDuration)->format('Y-m-d');

        return $bondData;
    }

    public function convertTimelineToDuration($measurement)
    {
        switch($measurement) {
            case BondOccurrenceMetric::WEEKLY->value :
                return "1 week"; break;
            case BondOccurrenceMetric::MONTHLY->value :
                return "1 month"; break;
            case BondOccurrenceMetric::QUARTERLY->value :
                return "4 months"; break;
            case BondOccurrenceMetric::YEARLY->value :
                return "1 year"; break;
            default: return null;
        }
    }

    public function addPayout($bond, $payout)
    {
        DB::beginTransaction();
        try{
            $bondPayout = new ClientBondPayout;
            $bondPayout->client_id = $bond->client_id;
            $bondPayout->client_bond_id = $bond->id;
            $bondPayout->payout_amount = $payout;
            $bondPayout->interest = $bond->net_rental_income;
            $bondPayout->interest_measurement = $bond->net_rental_income_measurement;

            $bondPayout->save();

            $client = $bond->client;
            $wallet = $client->wallet;
            if(!$wallet) {
                $wallet = app(WalletService::class)->create($client->id);
            }
            app(WalletService::class)->addToLockedAmount($wallet, $payout);

            DB::commit();

            return $bondPayout;
        }catch(\Exception $e) {
            DB::rollBack();
            Utilities::error($e, "An error Occurred trying to add payout");
        }
    }

    public function redeem($bond)
    {
        $this->checkBondEnded($bond);
        DB::beginTransaction();
        try{
            //account for parent bonds
            $parentBondIds = [];
            $loopBond = $bond;
            while($loopBond->parentBond) {
                $parentBondIds[] = $loopBond->parentBond->id;
                $loopBond = $loopBond->parentBond;
            }

            $allBondIds = array_merge([$bond->id], $parentBondIds);

            $total = ClientBondPayout::whereIn('bond_id', $allBondIds)->sum('payout_amount');

            $client = $bond->client;
            $wallet = $client->wallet;
            if(!$wallet) {
                throw new AppException(402, "Serious error.. contact the admin");
            }

            if($wallet->locked_amount < $total) throw new AppException(402, "Calculation error.. contact admin");

            $source = ["id"=>$bond->id, "type"=>ClientBond::$type];

            app(WalletService::class)->releaseLockedAmount($wallet, $total, $source);
            app(WalletService::class)->credit($wallet, $bond->current_capital, $source);

            $bond->redeemed = 1;

            $bond->save();

            DB::commit();
        }catch(\Exception $e) {
            DB::rollBack();
            throw new AppException(402, "An Error Occurred while attempting to redeem Bond Investment");
        }
    }

    public function renew($bond, $order)
    {
        $this->checkBondEnded($bond);
        $package = $bond->package;
        $bondData = [
            "clientId" => $bond->client_id,
            "packageId" => $bond->package_id,
            "orderId" => $order->id,
            "capital" => $bond->current_capital,
            "duration" => $package->bond_investment_duration,
            "durationMetric" => $package->bond_investment_duration_metric,
            "countDown" => $package->bond_count_down,
            "countDownMetric" => $package->bond_count_down_metric,
            "netRentalIncomeTimeline" => $package->bond_net_rental_income_timeline,
            "netRentalIncome" => $package->bond_net_rental_income,
            "netRentalIncomeMeasurement" => $package->bond_net_rental_income_measurement,
            "assetAppreciation" => $package->bond_asset_appreciation,
            "assetAppreciationMeasurement" => $package->bond_asset_appreciation_measurement,
            "assetAppreciationTimeline" => $package->bond_asset_appreciation_timeline,
        ];
        $bondData = $this->calculateBondDates($bondData);

        $renewedBond = $this->save($bondData);

        //Create new asset
        $asset = app(ClientPackageService::class)->saveClientPackageBond($renewedBond);

        //delete old bond asset
        $bond->clientPackage->delete();

        return $renewedBond;
    }

    public function checkBondEnded($bond)
    {
        $startDate = Carbon::parse($bond->start_date); 
        if ($startDate->isFuture()) {
            if($bond->started == 1) {
                $bond->started = 0;
                $bond->save();
            }
            throw new AppException(402, "bond has not started");
        }


        $endDate = Carbon::parse($bond->end_date);
        if ($endDate->isFuture()) {
            if($bond->ended == 1) {
                $bond->ended = 0;
                $bond->save();
            }
            throw new AppException(402, "bond has not ended");
        }
    }

    public function addMemorandumAgreement($mouFileId, $clientBond)
    {
        $clientBond->mou_file_id = $mouFileId;
        $clientBond->update();
        return $clientBond;
    }

    public function uploadMOU($order, $bond)
    {
        // generate MOU
        try{
            $fileService = new FileService;
            $contractService = new ContractService;
            // $uploadedFile = Helpers::generateMemorandumAgreement($order);
            // $uploadedFile = $contractService->generateMOU($order);
            // dd('generate MOU');
            $uploadedFile = "files/bond_memorandum_agreement_{$order->id}.pdf";
            $publicFile = public_path($uploadedFile);
            // dd('generate Contract');
            if(!file_exists($publicFile)) {
                // check if it has already been moved to the cloud

                if($bond->mou_file_id) return null;
                $uploadedFile = $contractService->generateBondMOU($order);
            }

            $response = Helpers::moveUploadedFileToCloud($uploadedFile, FileTypes::PDF->value, $order->client->id, 
                                FilePurpose::BOND_MEMORANDUM_OF_AGREEMENT->value, UserType::CLIENT->value, "client-bond-MOUs");
            if($response['success']) {
                $fileMeta = ["belongsId"=>$bond->id, "belongsType"=>ClientBond::$type];
                $fileService->updateFileObj($fileMeta, $response['upload']['file']);

                $this->addMemorandumAgreement($response['upload']['file']->id, $bond);

                $bond->markDocUploaded();
            }
        }catch(\Exception $e) {
            Utilities::logStuff("Error Occurred while attempting to generate and upload MOU..".$e);
        }
    }
}