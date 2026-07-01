<?php

namespace app\Services;

// use app\Services\StaffTypeService;

use Illuminate\Support\Facades\DB;

use app\Models\Benefit;
use app\Models\Bank;
use app\Models\ResellOrder;
use app\Models\BankAccount;
use app\Models\Identification;
use app\Models\VirtualStaffCategory;

use app\Helpers;
use app\Utilities;

/**
 * user service class
 */
class UtilityService
{

    public function __construct()
    {
        // $this->staffTypeService = new StaffTypeService;
    }

    public function benefits()
    {
        return Benefit::all();
    }

    public function benefit($id)
    {
        return Benefit::find($id);
    }

    public function benefitByName($name)
    {
        return Benefit::where("name", $name)->first();
    }

    public function banks()
    {
        return Bank::all();
    }

    public function bankAccounts($active = null)
    {
        if (!$active) return BankAccount::all();
        return BankAccount::where("active", $active)->get();
    }

    public function resellOrders()
    {
        return ResellOrder::all();
    }

    public function identifications()
    {
        return Identification::all();
    }

    public function VirtualStaffCategories()
    {
        return VirtualStaffCategory::all();
    }
}
