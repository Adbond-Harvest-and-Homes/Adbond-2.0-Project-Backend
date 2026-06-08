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
use app\Services\CommissionService;
use app\Services\UserActivityLogService;
use app\Services\UserProfileService;

use app\Utilities;

use app\Models\Role;

class StaffController extends Controller
{
    private $userService;
    private $userActivityLogService;
    private $userProfileService;

    public function __construct(protected CommissionService $commissionService)
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
            $registeredBy = null;
            if(isset($data['referalCode'])) {
                $user = $this->userService->getUserByStaffRefererCode($data['referalCode']);
                if(!$user) return Utilities::error402("Invalid Referer Code");
                $registeredBy = $user->id;
            }
            $user = $this->userService->save($data, $registeredBy);

            try{
                Mail::to($user->email)->send(new NewStaff($user, $data['password']));
            }catch(\Exception $e){
                Utilities::logStuff($e, 'An error occurred while trying to process the request, Please try again later or contact support');
            }

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Added Staff");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
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

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Updated Staff");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

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

        
            try {
                $this->userActivityLogService->log(Auth::user(), "Reset Staff Password");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

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

    public function myTeam()
    {
        // dd(Auth::user()->id);
        $team = $this->userService->getMyTeam(Auth::user()->id);
        // dd($team);
        $sales = $this->userService->getMyTeamSales(Auth::user()->id);

        $meta = [
            'totalSales' => $sales['total_sales'],
            'totalSalesCount' => $sales['sales_count'],
            'teamTotal' => $team->count(),
            'totalTeamCommission' => $this->commissionService->getTotalStaffIndirectEarnings(Auth::user()->id),
            // 'totalClients' => $sales['total_clients']
        ];

        return Utilities::ok([
            "team" => UserResource::collection($team),
            "meta" => $meta
        ]);
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

        
            try {
                $this->userActivityLogService->log(Auth::user(), "Deleted Staff");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Staff has been removed successfully");
    }
}
