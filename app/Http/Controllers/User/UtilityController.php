<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Resources\StaffTypeResource;
use app\Http\Resources\RoleResource;

use app\Services\StaffTypeService;
use app\Services\RoleService;

use app\Utilities;

class UtilityController extends Controller
{
    private $userActivityLogService;

    private $staffTypeService;
    private $roleService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->staffTypeService = new StaffTypeService;
        $this->roleService = new RoleService;
    }

    public function staffTypes()
    {
        $staffTypes = $this->staffTypeService->getStaffTypes();

        return Utilities::ok(StaffTypeResource::collection($staffTypes));
    }

    public function roles()
    {
        $roles = $this->roleService->roles();

        return Utilities::ok(RoleResource::collection($roles));
    }
    
}
