<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\AddBankAccount;
use app\Http\Requests\User\UpdateBankAccount;

use app\Http\Resources\UserBankAccountResource;

use app\Services\BankAccountService;
use app\Services\UserService;

use app\Utilities;

class UserBankAccountController extends Controller
{
    private $userActivityLogService;

    private $bankAccountService;
    private $userService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->bankAccountService = new BankAccountService;
        $this->userService = new UserService;
    }

    public function addAccount(AddBankAccount $request)
    {
        try{
            $data = $request->validated();

            $data['userId'] = Auth::user()->id;
            $bankAccount = $this->bankAccountService->save($data, false);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Added User Bank Account");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new UserBankAccountResource($bankAccount));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function bankAccounts($staffId = null)
    {
        if ($staffId && (!is_numeric($staffId) || !ctype_digit($staffId))) return Utilities::error402("Invalid parameter staffId");
        if($staffId) {
            $staff = $this->userService->getUser($staffId);
            if(!$staff) return Utilities::error402("Staff could not be found");
        }
        $userId = ($staffId) ? $staffId : Auth::user()->id;

        $accounts = $this->bankAccountService->userAccounts($userId);

        return Utilities::ok(UserBankAccountResource::collection($accounts));
    }
}
