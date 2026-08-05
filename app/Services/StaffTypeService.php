<?php

namespace app\Services;

use app\Notifications\APIPasswordResetNotification;
use app\Exceptions\UserNotFoundException;

use app\Models\User;
use app\Models\StaffType;

/**
 * Staff type service class
 */
class StaffTypeService
{

    public function getStaffTypes()
    {
        $types = cache()->remember('staff_types', 60 * 5, function () {
            return StaffType::all();
            // return Staff_type::where('name', '!=', 'virtual-staff')->get();
        });
        return $types;
    }

    public function getStaffTypesCount()
    {
        return StaffType::count();
    }

    public function getStaff_types()
    {
        return StaffType::all();
    }

    public function getStaffType($id)
    {
        return StaffType::find($id);
    }

    public function getStaffTypeByName($name)
    {
        return StaffType::where("name", $name)->first();
    }
}
