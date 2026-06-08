<?php

namespace app\Http\Controllers\User;

use app\Http\Controllers\Controller;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use app\Http\Resources\UserResource;

use app\Services\UserService;

class UserController extends Controller
{
    private $userActivityLogService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
    }

    //
}
