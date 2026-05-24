<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\CreateUser;
use app\Http\Requests\User\UpdateUser;
use app\Http\Requests\User\ChangePassword;

use app\Http\Resources\UserResource;

use app\Mail\NewStaff;

use app\Services\UserService;
use app\Services\UserActivityLogService;
use app\Services\UserProfileService;

use app\Utilities;

use app\Models\Role;

class StaffController extends Controller
{
    private $userService;
    private $userActivityLogService;
    private $userProfileService;

    public function __construct()
    {
        $this->userService = new UserService;
        $this->userActivityLogService = new UserActivityLogService;
        $this->userProfileService = new UserProfileService;
    }

    public function save(CreateUser $request)
    {
        try{
            $data = $request->validated();
            $data['password'] = '12345';
            $user = $this->userService->save($data, Auth::user()->id);

            try{
                Mail::to($user->email)->send(new NewStaff($user, $data['password']));
            }catch(\Exception $e){
                Utilities::logStuff($e, 'An error occurred while trying to process the request, Please try again later or contact support');
            }

            return Utilities::ok(new UserResource($user));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function update(UpdateUser $request, $userId)
    {
        try{
            if (!is_numeric($userId) || !ctype_digit($userId)) return Utilities::error402("Invalid parameter userId");

            $user = $this->userService->getUser($userId);
            if(!$user) return Utilities::error402("User not found");

            $data = $request->validated();
            $user = $this->userService->update($data, $user);

            return Utilities::ok(new UserResource($user));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function reset($userId)
    {
        if (!is_numeric($userId) || !ctype_digit($userId)) return Utilities::error402("Invalid parameter userId");

        $user = $this->userService->getUser($userId);
        if(!$user) return Utilities::error402("User not found");

        $this->userService->reset($user);

        return Utilities::okay("Staff Password has been reset");
    }

    public function users()
    {
        $users = $this->userService->getUsers(Auth::user());

        return Utilities::ok(UserResource::collection($users));
    }

    public function user($userId)
    {
        if (!is_numeric($userId) || !ctype_digit($userId)) return Utilities::error402("Invalid parameter userId");

        $user = $this->userService->getUser($userId);
        if(!$user) return Utilities::error402("User not found");

        return Utilities::ok(new UserResource($user));
    }

    public function activities()
    {
        $userLogs = $this->userActivityLogService->getLogs();

        return Utilities::ok($userLogs);
    }

    public function delete($userId)
    {
        if(Auth::user()->role_id != Role::SuperAdmin()->id) return Utilities::error402("You are not Authorized to perform this operation");

        if (!is_numeric($userId) || !ctype_digit($userId)) return Utilities::error402("Invalid parameter userId");

        $user = $this->userService->getUser($userId);
        if(!$user) return Utilities::error402("User not found");

        $this->userService->delete($user);

        return Utilities::okay("Staff has been removed successfully");
    }
}
