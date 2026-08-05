<?php

namespace app\Http\Controllers\User\Client;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Requests\AddWalletBankAccount;
use app\Http\Requests\User\ApproveWithdrawalRequest;
use app\Http\Requests\User\RejectWithdrawalRequest;

use app\Http\Resources\WalletResource;
use app\Http\Resources\WalletTransactionResource;
use app\Http\Resources\WalletWithdrawalRequestResource;

use app\Services\WalletService;

use app\Enums\WalletWithdrawalRequestStatus;

use app\Utilities;

class WalletController extends Controller
{
    private $userActivityLogService;

    private $walletService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->walletService = new WalletService;
    }

    public function linkBankAccount(AddWalletBankAccount $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            $wallet = $this->walletService->wallet($data['walletId']);
            if (!$wallet) return Utilities::error402("Wallet not found");

            $bankAccount = $this->walletService->getWalletBankAccount($wallet, $data['bankId'],  $data['accountNumber']);
            if ($bankAccount) return Utilities::error402("This bank Account is already linked to this wallet");

            $this->walletService->addBankAccount($wallet, $data);
            $wallet = $this->walletService->wallet($wallet->id, ['bankAccounts']);

            DB::commit();

            try {
                $this->userActivityLogService->log(Auth::user(), "Linked Bank Account");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new WalletResource($wallet));
        } catch (\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to send verification mail, Please try again later or contact support');
        }
    }

    public function index($clientId)
    {
        if (!is_numeric($clientId) || !ctype_digit($clientId)) return Utilities::error402("Invalid parameter clientID");
        $wallet = $this->walletService->clientWallet($clientId);

        if (!$wallet) return Utilities::error402("Wallet not found");

        return Utilities::ok(new WalletResource($wallet));
    }

    public function transactions($clientId)
    {
        try {
            if (!is_numeric($clientId) || !ctype_digit($clientId)) return Utilities::error402("Invalid parameter clientID");
            $wallet = $this->walletService->clientWallet($clientId);

            if (!$wallet) return Utilities::error402("Wallet not found");

            $transactions = $this->walletService->transactions($wallet->id);
            return Utilities::ok(WalletTransactionResource::collection($transactions));
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred while trying to send verification mail, Please try again later or contact support');
        }
    }

    public function withdrawalRequests($clientId)
    {
        try {
            if (!is_numeric($clientId) || !ctype_digit($clientId)) return Utilities::error402("Invalid parameter clientID");
            $wallet = $this->walletService->clientWallet($clientId);

            if (!$wallet) return Utilities::error402("Wallet not found");

            $this->walletService->walletId = $wallet->id;
            $requests = $this->walletService->withdrawalRequests();
            return Utilities::ok(WalletWithdrawalRequestResource::collection($requests));
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred while trying to send verification mail, Please try again later or contact support');
        }
    }

    public function withdrawalRequest($requestId)
    {
        try {
            if (!is_numeric($requestId) || !ctype_digit($requestId)) return Utilities::error402("Invalid parameter requestId");
            $withdrawalRequest = $this->walletService->getWithdrawalById($requestId, ['user']);

            if (!$withdrawalRequest) return Utilities::error402("Withdrawal Request not found");

            return Utilities::ok(new WalletWithdrawalRequestResource($withdrawalRequest));
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred while trying to send verification mail, Please try again later or contact support');
        }
    }

    public function approveRequest(ApproveWithdrawalRequest $request)
    {
        try {
            $data = $request->validated();
            $withdrawalRequest = $this->walletService->getWithdrawalById($data['requestId']);
            if (!$withdrawalRequest) return Utilities::error402("Withdrawal Request not found");
            $wallet = $withdrawalRequest->wallet;

            if (!$wallet) return Utilities::error402("The Wallet for this Withdrawal was  not found");

            if ($withdrawalRequest->status == WalletWithdrawalRequestStatus::REJECTED->value) return Utilities::error402("This request has been rejected");

            $availableAmount = $wallet->amount - $wallet->locked_amount;
            if ($withdrawalRequest->amount > $availableAmount) return Utilities::error402("The amount you want to withdraw is greater than available amount");

            DB::beginTransaction();

            $this->walletService->debit($wallet, $withdrawalRequest->amount);

            $withdrawalRequest = $this->walletService->approveWithdrawalRequest($withdrawalRequest, Auth::user()->id);

            DB::commit();

            try {
                $this->userActivityLogService->log(Auth::user(), "Approved Withdrawal Request");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Request has been approved", new WalletWithdrawalRequestResource($withdrawalRequest));
        } catch (\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to send verification mail, Please try again later or contact support');
        }
    }

    public function rejectRequest(RejectWithdrawalRequest $request)
    {
        try {
            $data = $request->validated();
            $withdrawalRequest = $this->walletService->getWithdrawalById($data['requestId']);
            if (!$withdrawalRequest) return Utilities::error402("Withdrawal Request not found");

            $withdrawalRequest = $this->walletService->rejectWithdrawalRequest($withdrawalRequest, $data['message'], Auth::user()->id);


            try {
                $this->userActivityLogService->log(Auth::user(), "Rejected Withdrawal Request");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Request has been rejected", new WalletWithdrawalRequestResource($withdrawalRequest));
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred while trying to send verification mail, Please try again later or contact support');
        }
    }
}
