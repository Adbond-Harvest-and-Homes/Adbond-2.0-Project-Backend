<?php

namespace app\Services;

use DateTime;
use Illuminate\Support\Facades\Mail;

use app\Models\ClientInvestment;

use app\Mail\MOU;

use app\Enums\FilePurpose;
use app\Enums\FileTypes;
use app\Enums\UserType;

use app\Services\ContractService;

use app\Utilities;
use app\Helpers;

class ClientInvestmentService
{

    public function getByOrderId($orderId)
    {
        return ClientInvestment::where("order_id", $orderId)->first();
    }

    public function getInvestment($id)
    {
        return ClientInvestment::find($id);
    }

    public function runningInvestments()
    {
        return ClientInvestment::where("started", 1)->where("ended", 0)->orderBy("created_at", "DESC")->get();
    }

    public function save($data)
    {
        $clientInvestment = ClientInvestment::where("order_id", $data['orderId'])->first();
        if(!$clientInvestment) $clientInvestment = new ClientInvestment;
        $clientInvestment->client_id = $data['clientId'];
        $clientInvestment->package_id = $data['packageId'];
        $clientInvestment->order_id = $data['orderId'];
        $clientInvestment->redemption_option = $data['redemptionOption'];
        if(isset($data['redemptionPackageId'])) $clientInvestment->redemption_package_id = $data['redemptionPackageId'];
        $clientInvestment->capital = $data['capital'];
        $clientInvestment->duration = $data['duration'];
        $clientInvestment->timeline = $data['timeline'];
        $clientInvestment->interest_payments_left = ceil($data['timeline']/$data['duration']);
        if(isset($data['percentage'])) $clientInvestment->percentage = $data['percentage'];
        if(isset($data['amount'])) $clientInvestment->amount = $data['amount'];
        if(isset($data['startDate'])) {
            $clientInvestment->start_date = $data['startDate'];
            $clientInvestment->next_interest_date = (new DateTime($data['startDate']))->modify('+'.$data['duration'].' months')->format('Y-m-d');
            $clientInvestment->started = true;
        }
        if(isset($data['endDate'])) {
            $clientInvestment->end_date = $data['endDate'];
        }

        $clientInvestment->save();

        return $clientInvestment;
    }

    public function start($investment)
    {
        $investment->start_date = Date("Y-m-d");
        $investment->next_interest_date = (new DateTime($investment->start_date))->modify('+'.$investment->duration.' months')->format('Y-m-d');
        $investment->started = true;
        $investment->end_date = (new DateTime($investment->start_date))->modify('+'.$investment->timeline.' months')->format('Y-m-d');
        $investment->update();
        return $investment;
    }

    public function saveInvestment($order, $data)
    {
        $investmentData['clientId'] = $order->client_id;
        $investmentData['packageId'] = $order->package_id;
        $investmentData['orderId'] = $order->id;
        $investmentData['redemptionOption'] = $data['redemptionOption'];
        if(isset($data['redemptionPackageId'])) $investmentData['redemptionPackageId'] = $data['redemptionPackageId'];
        $investmentData['capital'] = $order->amount_payable;
        $investmentData['duration'] = $order->package->interest_return_duration;
        $investmentData['timeline'] = $order->package->interest_return_timeline;
        if($order->package->interest_return_percentage) $investmentData['percentage'] = $order->package->interest_return_percentage;
        if($order->package->interest_return_amount) $investmentData['amount'] = $order->package->interest_return_amount; 
        if($order->completed == 1) {
            $investmentData['startDate'] = Date("Y-m-d");
            $investmentData['started'] = true;
            $investmentData['endDate'] = (new DateTime($investmentData['startDate']))->modify('+'.$investmentData['timeline'].' months')->format('Y-m-d');
        }
        $clientInvestment = $this->save($investmentData);

        return $clientInvestment;
    }

    public function addMemorandumAgreement($memorandumAgreementFileId, $clientInvestment)
    {
        $clientInvestment->memorandum_agreement_file_id = $memorandumAgreementFileId;
        $clientInvestment->update();
        return $clientInvestment;
    }

    public function uploadMOU($order, $investment)
    {
        // generate MOU
        try{
            $fileService = new FileService;
            $contractService = new ContractService;
            // $uploadedFile = Helpers::generateMemorandumAgreement($order);
            // $uploadedFile = $contractService->generateMOU($order);
            // dd('generate MOU');
            $uploadedFile = "files/memorandum_agreement_{$order->id}.pdf";
            $publicFile = public_path($uploadedFile);
            // dd('generate Contract');
            if(!file_exists($publicFile)) {
                // check if it has already been moved to the cloud

                if($investment->memorandum_agreement_file_id) return null;
                $uploadedFile = $contractService->generateMOU($order);
            }

            $response = Helpers::moveUploadedFileToCloud($uploadedFile, FileTypes::PDF->value, $order->client->id, 
                                FilePurpose::MEMORANDUM_OF_AGREEMENT->value, UserType::CLIENT->value, "client-MOUs");
            if($response['success']) {
                $fileMeta = ["belongsId"=>$investment->id, "belongsType"=>ClientInvestment::$type];
                $fileService->updateFileObj($fileMeta, $response['upload']['file']);

                $this->addMemorandumAgreement($response['upload']['file']->id, $investment);

                $investment->markDocUploaded();
                // dd("got here");
                // try{
                //     // Send MOU Mail
                //     Mail::to($order->client->email)->send(new MOU($order->client, $uploadedFile));
                //     unlink($response['path']);
                // }catch(\Exception $e) {
                //     Utilities::logStuff("Error Occurred while attempting to send MOU Email..".$e);
                // }
            }
        }catch(\Exception $e) {
            Utilities::logStuff("Error Occurred while attempting to generate and upload MOU..".$e);
        }
    }

    public function getProfit($clientInvestment)
    {
        if($clientInvestment->amount) {
            return $clientInvestment->amount;
        }
        return Utilities::getPercentageAmount($clientInvestment->capital, $clientInvestment->percentage);
    }

    public function endInvestment($clientInvestment)
    {
        $amount = $this->getProfit($clientInvestment);
        $clientInvestment->ended = true;
        $clientInvestment->updated();

        return ["amount"=>$amount, "investment"=>$clientInvestment];
    }
}