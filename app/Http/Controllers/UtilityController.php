<?php

namespace app\Http\Controllers;

use app\Http\Resources\BankAccountResource;
use Illuminate\Http\Request;

use app\Http\Resources\BenefitResource;
use app\Http\Resources\BankResource;
use app\Http\Resources\WalletBankAccountResource;
use app\Http\Resources\ResellOrderResource;
use app\Http\Resources\ClientIdentificationResource;
use app\Http\Resources\CountryResource;

use app\Services\UtilityService;
use app\Services\CountryService;
use app\Utilities;

class UtilityController extends Controller
{
    private $utilityService;
    protected $countryService;

    public function __construct()
    {
        $this->utilityService = new UtilityService;
        $this->countryService = new CountryService;
    }

    public function benefits()
    {
        $benefits = $this->utilityService->benefits();

        return BenefitResource::collection($benefits);
    }

    public function banks()
    {
        $banks = $this->utilityService->banks();

        return Utilities::ok(BankResource::collection($banks));
    }

    public function bankAccounts()
    {
        $accounts = $this->utilityService->bankAccounts();

        return Utilities::ok(BankAccountResource::collection($accounts));
    }

    public function activeBankAccounts()
    {
        $accounts = $this->utilityService->bankAccounts(1);

        return Utilities::ok(BankAccountResource::collection($accounts));
    }

    public function resellOrders()
    {
        $resellOrders = $this->utilityService->resellOrders();
        return Utilities::ok(ResellOrderResource::collection($resellOrders));
    }

    public function identifications()
    {
        $identifications = $this->utilityService->identifications();
        return Utilities::ok(ClientIdentificationResource::collection($identifications));
    }

    public function countries()
    {
        $countries = $this->countryService->countries();

        return Utilities::ok(CountryResource::collection($countries));
    }
}
