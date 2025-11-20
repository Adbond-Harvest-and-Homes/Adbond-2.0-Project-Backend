<?php

namespace app\Http\Controllers\Client;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use app\Http\Controllers\Controller;

use app\Http\Requests\AddWalletBankAccount;
use app\Http\Requests\Client\WalletWithdrawal;
use app\Http\Requests\Client\AddTransactionPin;
use app\Http\Requests\Client\ValidateWalletWithdrawal;

use app\Http\Resources\WalletWithdrawalRequestResource;
use app\Http\Resources\WalletResource;
use app\Http\Resources\WalletTransactionResource;

use app\Services\WalletService;
use app\Services\NotificationService;

use app\Enums\NotificationType;

use app\Utilities;

class WalletController extends Controller
{
    private $walletService;
    private $notificationService;

    public function __construct()
    {
        $this->walletService = new WalletService;
        $this->notificationService = new NotificationService;
    }

    public function index(Request $request)
    {
        $wallet = $this->walletService->clientWallet(Auth::guard("client")->user()->id);
        if(!$wallet) return Utilities::error402("Wallet not found");

        return Utilities::ok(new WalletResource($wallet));
    }

    public function LinkBankAccount(AddWalletBankAccount $request)
    {
        try{
            DB::beginTransaction();
            $data = $request->validated();
            $wallet = $this->walletService->wallet($data['walletId']);
            if(!$wallet) return Utilities::error402("Wallet not found");

            $bankAccount = $this->walletService->getWalletBankAccount($wallet, $data['bankId'],  $data['accountNumber']);
            if($bankAccount) return Utilities::error402("This bank Account is already linked to this wallet");

            $this->walletService->addBankAccount($wallet, $data);
            $wallet = $this->walletService->wallet($wallet->id, ['bankAccounts']);

            DB::commit();
            return Utilities::ok(new WalletResource($wallet));
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to perform this operation, Please try again later or contact support');
        }
    }

    public function setTransactionPin(AddTransactionPin $request)
    {
        try{
            $data = $request->validated();
            $wallet = $this->walletService->wallet($data['walletId']);
            if(!$wallet) return Utilities::error402("Wallet not found");

            $wallet = $this->walletService->setTransactionPin($wallet, $data['pin']);
            return Utilities::okay("Transaction Pin Set");
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to perform this operation, Please try again later or contact support');
        }
    }

    public function transactions()
    {
        try{
            $wallet = Auth::guard("client")->user()->wallet;

            if(!$wallet) return Utilities::error402("Wallet not found");

            $transactions = $this->walletService->transactions($wallet->id);
            return Utilities::ok(WalletTransactionResource::collection($transactions));

        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to send verification mail, Please try again later or contact support');
        }
    }

    public function transaction($transactionId)
    {
        if (!is_numeric($transactionId) || !ctype_digit($transactionId)) return Utilities::error402("Invalid parameter transactionID");

        $transaction = $this->walletService->transaction($transactionId);
    }

    public function validateWithdrawal(ValidateWalletWithdrawal $request)
    {
        $wallet = Auth::guard("client")->user()->wallet;
        if(!$wallet) return Utilities::error402("Wallet not found for this client");

        $availableAmount = $wallet->amount - $wallet->locked_amount;
        if($request->validated('amount') > $availableAmount) return Utilities::error402("Insufficient Available funds");

        return Utilities::okay("successful");
    }

    public function withdraw(WalletWithdrawal $request)
    {
        try{
            $data = $request->validated();
            $wallet = Auth::guard("client")->user()->wallet;

            if(!$wallet) return Utilities::error402("Wallet not found");

            if($data['pin'] != $wallet->transaction_pin) return Utilities::error402("Incorrect Pin");

            $availableAmount = $wallet->amount - $wallet->locked_amount;
            if($data['amount'] > $availableAmount) return Utilities::error402("Insufficient Available funds");

            $withdrawalRequest = $this->walletService->generateWithdrawalRequest($wallet, $data['amount']);

            $this->notificationService->save($withdrawalRequest, NotificationType::WALLET_WITHDRAWAL_REQ->value,  Auth::guard("client")->user());

            return Utilities::ok(new WalletWithdrawalRequestResource($withdrawalRequest));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to perform this operation, Please try again later or contact support');
        }
    }

    public function withdrawalRequests()
    {
        $wallet = Auth::guard("client")->user()->wallet;
        if(!$wallet) $wallet = $this->walletService->clientWallet(Auth::guard("client")->user()->id);
        $this->walletService->walletId = $wallet->id;
        $withdrawalRequests = $this->walletService->withdrawalRequests();

        return Utilities::ok(WalletWithdrawalRequestResource::collection($withdrawalRequests));
    }
}
