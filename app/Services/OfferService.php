<?php

namespace app\Services;

use app\Models\Offer;
use app\Models\PaymentStatus;

use app\Services\FileService;
use app\Services\ClientPackageService;

use app\Enums\OfferApprovalStatus;
use app\Enums\FileTypes;
use app\Enums\FilePurpose;

use app\Helpers;

class OfferService
{
    public $clientId = null;
    public $mine = true;
    public $filter = null;
    public $count = null;
    public $sales = null;

    public function save($data)
    {
        $offer = new Offer;

        $offer->client_id = $data['clientId'];
        $offer->package_id = $data['packageId'];
        $offer->client_package_id = $data['assetId'];
        $offer->units = $data['units'];
        $offer->project_id = $data['projectId'];
        $offer->price = $data['price'];
        $offer->package_price = $data['packagePrice'];
        if(isset($data['resellOrderId'])) {
            $offer->resell_order_id = $data['resellOrderId'];
            $offer->payment_status_id = PaymentStatus::pending()->id;
        }
        if(isset($data['active'])) $offer->active = $data['active'];
        // $offer->payment_status_id = PaymentStatus::pending()->id;

        $offer->save();

        return $offer;
    }

    public function update($data, $offer)
    {
        if(isset($data['price'])) {
            $offer->price = $data['price'];
            if($offer->approved == 0) $offer->approved = null;
        }
        if(isset($data['paymentStatusId'])) $offer->payment_status_id = $data['paymentStatusId'];
        $offer->update();

        return $offer;
    }

    public function bidAccepted($offer, $bid)
    {
        $offer->accepted_bid_id = $bid->id;
        $offer->active = false;

        $offer->update();

        return $offer;
    }

    public function offers($with=[], $offset=0, $perPage=null)
    {
        $query = Offer::with($with);
        if($this->clientId) {
            ($this->mine) ? $query->where("client_id", $this->clientId) : $query->where("client_id", "!=", $this->clientId);
        }
        if($this->sales == false) $query->whereNull("resell_order_id")->whereNull("accepted_bid_id");
        if($this->sales == true) $query->whereNotNull("resell_order_id")->OrWhereNotNull("accepted_bid_id");
        if($this->filter && is_array($this->filter)) {
            $filter = $this->filter;
            if(isset($filter['text'])) {
                $query->whereHas('package', function($packageQuery) use($filter) {
                    $packageQuery->where("name", "LIKE", "%".$filter['text']."%")
                    ->orWhereHas('project', function($projectQuery) use($filter) {
                        $projectQuery->where("name", "LIKE", "%".$filter['text']."%");
                    });
                });
            }
            if(isset($filter['date'])) $query = $query->whereDate("created_at", $filter['date']);
            
            if(isset($filter['status'])) {
                switch($filter['status']) {
                    case OfferApprovalStatus::PENDING->value : $query->whereNull("approved"); break;
                    case OfferApprovalStatus::APPROVED->value : $query->where("approved", 1); break;
                    case OfferApprovalStatus::REJECTED->value : $query->where("approved", 0); break;
                }
            }
        }
        if($this->count) return $query->count();
        return $query->orderBy("created_at", "DESC")->offset($offset)->limit($perPage)->get();
    }

    //offers that are ready to be completed
    public function readyOffers($with=[])
    {
        $query = Offer::with($with);

        if($this->clientId) {
            if($this->mine) {
                $query->where("client_id", $this->clientId);
            }else{
                $query = $query->whereHas("acceptedBid", function($bidQuery) {
                    $bidQuery->where("client_id", $this->clientId);
                });
            }
        }
        
        $query = $query->whereNotNull("resell_order_id")->orWhere("payment_status_id", PaymentStatus::complete()->id);

        return $query->get();
    }

    public function getOffersByAssetId($clientPackageId)
    {
        return Offer::where("client_package_id", $clientPackageId)->first();
    }

    public function offer($id, $with=[])
    {
        return Offer::with($with)->where("id", $id)->first();
    }

    public function approve($offer, $userId)
    {
        $offer->approved = true;
        $offer->approval_date = now();
        $offer->user_id = $userId;
        $offer->payment_status_id = PaymentStatus::pending()->id;
        $offer->update();

        return $offer;
    }

    public function reject($offer, $reason, $userId)
    {
        $offer->approved = false;
        $offer->rejected_reason = $reason;
        $offer->user_id = $userId;
        $offer->update();

        return $offer;
    }

    public function completeOffer($offer, $payment)
    {
        $contractFileId = null;
        $contractFileObj = null;
        $letterOfHappinessFileId = null;
        $letterOfHappinessFileObj = null;
        $fileService = new FileService;
        $clientInvestmentService = new ClientInvestmentService;
        $clientPackageService = new ClientPackageService;
        // try{
        //     // generate and save contract
        //     Helpers::generateContract($offer);
        //     // dd('generate receipt');
        //     $uploadedContract = "files/contract_{$offer->id}.pdf";
            
        //     $response = Helpers::moveUploadedFileToCloud($uploadedContract, FileTypes::PDF->value, $offer->acceptedBid->client->id, 
        //     FilePurpose::CONTRACT->value, "app\Models\Client", "client-contracts");
            
        //     if($response['success']) {
        //         $contractFileId = $response['upload']['file']->id;
        //         $contractFileObj = $response['upload']['file'];
        //     }
            
        // }catch(\Exception $e) {
        //     Utilities::logStuff("Error Occurred while attempting to generate and upload contract..".$e);
        // }

        
        // generate and save letter of happiness
        // try{
        //     // generate and save contract
        //     Helpers::generateLetterOfHappiness($payment);
        //     // dd('generate receipt');
        //     $uploadedLetter = "files/letter_of_happiness_{$offer->id}.pdf";
            
        //     $response = Helpers::moveUploadedFileToCloud($uploadedLetter, FileTypes::PDF->value, $offer->acceptedBid->client->id, 
        //     FilePurpose::LETTER_OF_HAPPINESS->value, "app\Models\Client", "client-letter_of_happiness");
        //     if($response['success']) {
        //         $letterOfHappinessFileId = $response['upload']['file']->id;
        //         $letterOfHappinessFileObj = $response['upload']['file'];
        //     }
            
        // }catch(\Exception $e) {
        //     Utilities::logStuff("Error Occurred while attempting to generate and upload letter of happiness..".$e);
        // }

        // mark the order as complete
        $offer->completed = true;
        $offer->update();


        // save the clientPackage and return it
        $clientPackageService = new ClientPackageService;
        $files = [];
        // if($contractFileId) $files['contractFileId'] = $contractFileId;
        // if($letterOfHappinessFileId) $files['happinessLetterFileId'] = $letterOfHappinessFileId;
        // dd($files);
        $clientPackage = $clientPackageService->saveClientPackageOffer($offer, $files);

        $clientPackageService->uploadContract($offer, $clientPackage, true);
        $clientPackageService->uploadLetterOfHappiness($payment, $clientPackage);

        // $fileMeta = ["belongsId"=>$clientPackage->id, "belongsType"=>"app\Models\ClientPackage"];
        // if($contractFileObj) $fileService->updateFileObj($fileMeta, $contractFileObj);
        // if($letterOfHappinessFileObj) $fileService->updateFileObj($fileMeta, $letterOfHappinessFileObj);

        return $offer;
    }
}