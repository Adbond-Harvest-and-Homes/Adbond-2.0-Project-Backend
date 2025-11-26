<?php

namespace app\Services;

use app\Exceptions\UserNotFoundException;

use Carbon\Carbon;
use PDF;

use Illuminate\Support\Facades\DB;

use app\Services\AgeGroupService;
use app\Services\PackageService;
use app\Services\ClientPackageService;

use app\Models\Client;
use app\Models\ClientNextOfKin;
use app\Models\ClientSummaryView;
use app\Models\ClientCommissionEarning;
use app\Models\ClientIdentification;

use app\Exports\ClientExport;

use app\Enums\KYCStatus;
use app\Enums\ActiveToggle;
use app\Enums\ClientPackageOrigin;
use app\Helpers;

/**
 * client service class
 */
class ClientService
{
    public $count = false;
    public $active = null;

    public function getClient($id, $with=[])
    {
        return Client::with($with)->where("id", $id)->first();
    }

    public function getClientByEmail($email, $with=[])
    {
        return Client::with($with)->where('email', $email)->first();
    }

    public function getClientByReferralCode($code)
    {
        return Client::where("referer_code", $code)->first();
    }

    public function getClientByProvider($provider_name, $provider_id)
    {
        return Client::where('provider_name', $provider_name)->where('provider_id', $provider_id)->first();
    }

    public function getClients()
    {
        return Client::orderBy('created_at', 'DESC')->get();
    }

    public function clients($with=[], $offset=0, $perPage=null)
    {
        $query = Client::with($with);
        if($this->active !== null) {
            $query = ($this->active) ? $query->where("activated", 1) : $query->where("activated", 0);
        }
        if($this->count) return $query->count();
        $query = $query->orderBy("created_at", "DESC")->offset($offset);
        return ($perPage) ? $query->limit($perPage)->get() : $query->get();
    }

    public function searchClients($string)
    {
        return Client::where('firstname', 'like', '%'.$string.'%')->orWhere('lastname', 'like', '%'.$string.'%')->get();
    }

    public function filter($filter, $with=[], $offset=0, $perPage=null)
    {
        $query = Client::with($with);
        if(isset($filter['text'])) {
            $query = $query->where(function($q) use($filter) { 
                $q->where("firstname", "LIKE", "%".$filter['text']."%")->orWhere("lastname", "LIKE", "%".$filter['text']."%")
                ->orWhere("email", "LIKE", "%".$filter['text']."%");
            });
        }
        if(isset($filter['date'])) $query = $query->whereDate("created_at", $filter['date']);
        if(isset($filter['status'])) $query = ($filter['status'] == ActiveToggle::ACTIVE->value) ? $query->where("activated", true) : $query->where("activated", false);
        if($this->count) return $query->count();
        return $query->orderBy("created_at", "DESC")->offset($offset)->limit($perPage)->get();
    }

    public function summary()
    {
        return ClientSummaryView::first();
    }

    public function totalClients()
    {
        return Client::count();
    }

    public function save($data)
    {
        $client = new Client;
        if(isset($data['title'])) $client->title = $data['title'];
        $client->firstname = $data['firstname'];
        $client->lastname = $data['lastname'];
        $client->email = $data['email'];
        $client->password =  bcrypt($data['password']);
        $client->email_verified_at = $data['emailVerifiedAt'];
        if(isset($data['othernames'])) $client->othernames = $data['othernames'];
        if(isset($data['photoId'])) $client->photo_id = $data['photoId'];
        if(isset($data['gender'])) $client->gender = $data['gender'];
        if(isset($data['phoneNumber'])) $client->phone_number = $data['phoneNumber'];
        if(isset($data['address'])) $client->address = $data['address'];
        if(isset($data['countryId'])) $client->country_id = $data['countryId'];
        if(isset($data['stateId'])) $client->state_id = $data['stateId'];
        if(isset($data['ageGroupId'])) $client->age_group_id = $data['ageGroupId'];
        if(isset($data['refererId'])) $client->referer_id = $data['refererId'];
        if(isset($data['refererType'])) $client->referer_type = $data['refererType'];
        if(isset($data['marital_status'])) $client->marital_status = $data['maritalStatus'];
        if(isset($data['employment_status'])) $client->employment_status = $data['employmentStatus'];
        if(isset($data['occupation'])) $client->occupation = $data['occupation'];
        if(isset($data['postalCode'])) $client->postal_code = $data['postalCode'];
        $client->save();
        return $client;
    }

    public function update($data, $client)
    {
        if(isset($data['identificationId'])) {
            $clientIdentification = $client->clientIdentification;
            if(!$clientIdentification) $clientIdentification = new ClientIdentification;
            $clientIdentification->client_id = $client->id;
            $clientIdentification->identification_id = $data['identificationId'];
            $clientIdentification->file_id = $data['identificationFileId'];
            $clientIdentification->save();

            if(!$client->client_identification_id) $client->client_identification_id = $clientIdentification->id;
        }

        if(isset($data['title'])) $client->title = $data['title'];
        if(isset($data['firstname'])) $client->firstname = $data['firstname'];
        if(isset($data['lastname'])) $client->lastname = $data['lastname'];
        if(isset($data['othernames'])) $client->othernames = $data['othernames'];
        // if(isset($data['email'])) $client->email = $data['email'];
        if(isset($data['photoId'])) $client->photo_id = $data['photoId'];
        if(isset($data['gender'])) $client->gender = $data['gender'];
        if(isset($data['phoneNumber'])) $client->phone_number = $data['phoneNumber'];
        if(isset($data['address'])) $client->address = $data['address'];
        if(isset($data['countryId'])) $client->country_id = $data['countryId'];
        if(isset($data['stateId'])) $client->state_id = $data['stateId'];
        // if(isset($data['referer_id'])) $client->referer_id = $data['referer_id'];
        if(isset($data['maritalStatus'])) $client->marital_status = $data['maritalStatus'];
        if(isset($data['employmentStatus'])) $client->employment_status = $data['employmentStatus'];
        if(isset($data['occupation'])) $client->occupation = $data['occupation'];
        // if(isset($data['identificationId'])) $client->identification_id = $data['identificationId'];
        // /if(array_key_exists('occupation', $data));
        if(isset($data['postalCode'])) $client->postal_code = $data['postalCode'];
        if(isset($data['ageGroupId'])) $client->age_group_id = $data['ageGroupId'];
        if(isset($data['refererCode'])) $client->referer_code = $data['refererCode'];
        if(isset($data['kycStatus'])) $client->kyc_status = $data['kycStatus'];
        if(isset($data['dob'])) {
            $ageGroupService = new AgeGroupService;
            $ageGroup = $ageGroupService->getGroupFromDob($data['dob']);
            if($ageGroup) $client->age_group_id = $ageGroup->id;
            $client->dob = $data['dob'];
        }
        $client->update();


        //Check and update the kyc status
        if($client->kyc_status == KYCStatus::NOTSTARTED->value || $client->kyc_status == KYCStatus::STARTED->value) {
            if(Helpers::kycCompleted($client)) {
                $client->kyc_status = KYCStatus::COMPLETED->value;
                $client->update();
            }elseif(Helpers::kycStarted($client)) {
                $client->kyc_status = KYCStatus::STARTED->value;
                $client->update();
            }
        }

        if($client->kyc_status == KYCStatus::COMPLETED->value) $this->uploadDocs($client);
        
        return $client;
    }

    public function changePassword($password, $client)
    {
        $client->password =  bcrypt($password);
        $client->update();
    }

    public function uploadDocs($client)
    {
        $paymentService = new PaymentService;
        $clientPackageService = new ClientPackageService;
        //upload receipts
        $payments = $paymentService->getClientPayments($client->id);
        if($payments->count() > 0) {
            foreach($payments as $payment) {
                if($payment->docs_uploaded == 0) {
                    $paymentService->uploadReceipt($payment, $client);
                    $payment->docs_uploaded == 1;
                    $payment->update();
                }
            }
        }

        $assets = $clientPackageService->clientAssets($client->id);
        if($assets->count() > 0) {
            foreach($assets as $asset) {
                if($asset->docs_uploaded == 0 && $asset->origin != ClientPackageOrigin::INVESTMENT->value) {
                    $clientPackageService->uploadContract($asset->purchase, $asset, ($asset->origin == ClientPackageOrigin::OFFER->value));
                    $asset->docs_uploaded == 1;
                    $asset->update();
                }
            }
        }
    }

    public function saveGoogleUser($data)
    {
        $client = new Client;
        $name = explode(' ', $data['name']);
        $client->firstname = $name[0];
        if(isset($name[1])) $client->lastname = $name[1];
        $client->email = $data['email'];
        $client->provider_id = $data['provider_id'];
        $client->provider_name = $data['provider_name'];
        $client->activated = 1;
        $client->email_verified_at = Carbon::now();
        $client->save();
        return $client;
    }

    public function delete($client)
    {
        $client->delete();
    }



    public function updateNextOfKin($data)
    {
        $kin = (isset($data['kin'])) ? $data['kin'] : new ClientNextOfKin;
        $kin->client_id = $data['clientId'];
        if(isset($data['title'])) $kin->title = $data['title'];
        $kin->firstname = $data['firstname'];
        $kin->lastname = $data['lastname'];
        $kin->phone_number = $data['phoneNumber'];
        $kin->relationship = $data['relationship'];
        if(isset($data['gender'])) $kin->gender = $data['gender'];
        if(isset($data['email'])) $kin->email = $data['email'];
        if(isset($data['address'])) $kin->address = $data['address'];
        if(isset($data['countryId'])) $kin->country_id = $data['countryId'];
        if(isset($data['stateId'])) $kin->state_id = $data['stateId'];
        $kin->save();
        return $kin;
    }

    public function referralEarnings($clientId)
    {
        return ClientCommissionEarning::where("client_id", $clientId)->get();
    }

    public function exportToPDF($clients, $headingConfig = null)
    {
        $export = new ClientExport($clients, $headingConfig);
        $data = [
            'headings' => $export->headings(),
            'mappedData' => $clients->map(function ($client) use ($export) {
                return $export->map($client);
            })
        ];

        $pdf = PDF::loadView('exports.pdf.clients', $data);
        return $pdf->download('clients-' . now()->format('Y-m-d') . '.pdf');
    }

}