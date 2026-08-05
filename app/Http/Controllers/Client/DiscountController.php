<?php

namespace app\Http\Controllers\Client;

use Illuminate\Http\Request;
use app\Http\Controllers\Controller;

use app\Http\Resources\InstallmentDiscountResource;

use app\Services\DiscountService;

use app\Enums\DiscountType;

use app\Utilities;

class DiscountController extends Controller
{
    
    protected $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    public function installments()
    {
        $installments = $this->discountService->getInstallmentDurations();

        return Utilities::ok(InstallmentDiscountResource::collection($installments));
    }
}
