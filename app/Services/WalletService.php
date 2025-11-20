<?php

namespace app\Services;

// use app\Services\StaffTypeService;

use app\Models\Wallet;
use app\Models\WalletBankAccount;
use app\Models\WalletTransaction;
use app\Models\WalletWithdrawalRequest;
use app\Models\ClientInvestment;

use app\Helpers;
use app\Utilities;

use app\Enums\TransactionType;
use app\Enums\WalletTransactionSource;
use app\Enums\WalletWithdrawalRequestStatus;

/**
 * user service class
 */
class WalletService
{
    public $walletId = null;
    
    public function wallets($with=[])
    {
        return Wallet::with($with)->get();
    }

    public function wallet($id, $with=[])
    {
        return Wallet::with($with)->where("id", $id)->first();
    }

    public function clientWallet($clientId, $with=[])
    {
        $wallet = Wallet::with($with)->where("client_id", $clientId)->first();
        if(!$wallet) {
            $wallet = $this->create($clientId);
        }
        return $wallet;
    }

    public function transactions($walletId, $with=[])
    {
        return WalletTransaction::with($with)->where("wallet_id", $walletId)->orderBy("created_at", "DESC")->get();
    }

    public function transaction($id, $with=[])
    {
        return WalletTransaction::with($with)->where("id", $id)->first();
    }
    
    public function getTransactionsByReference($referenceNo)
    {
        return WalletTransaction::where("reference_no", $referenceNo)->first();
    }

    public function getInvestmentTransactions($investmentId)
    {
        return WalletTransaction::where("source_type", WalletTransactionSource::INVESTMENT->value)->where("source_id", $investmentId)->orderBy("created_at", "DESC")->get();
    }

    public function getWithdrawalByReference($referenceNo, $with=[])
    {
        return WalletWithdrawalRequest::with($with)->where("reference_no", $referenceNo)->first();
    }

    public function getWithdrawalById($id, $with=[])
    {
        return WalletWithdrawalRequest::with($with)->where("id", $id)->first();
    }

    public function withdrawalRequests($with=[])
    {
        $query = WalletWithdrawalRequest::with($with);
        if($this->walletId) $query->where("wallet_id", $this->walletId);
        return $query->orderBy("created_at", "DESC")->get();
    }

    public function totalOutflows($client)
    {
        $total = 0;
        $transactions = $client?->wallet?->transactions;
        if($transactions && $transactions->count() > 0) {
            foreach($transactions as $transaction) {
                if($transaction->transaction_type == TransactionType::OUT_FLOW->value) {
                    $total += $transaction->amount;
                }
            }
        }
        return $total;
    }

    public function getTotalInvestmentAmount($investmentId)
    {
        $amount = 0;
        $transactions = $this->getInvestmentTransactions($investmentId);
        if($transactions->count() > 0) {
            foreach($transactions as $transaction) $amount += $transaction->amount;
        }
        return $amount;
    }

    public function create($clientId)
    {
        $wallet = new Wallet;
        $wallet->client_id = $clientId;
        $wallet->save();
        return $wallet;
    }

    public function getWalletBankAccount($wallet, $bankId, $accountNumber)
    {
        return WalletBankAccount::where("wallet_id", $wallet->id)->where("bank_id", $bankId)->where("account_number", $accountNumber)->first();
    }

    public function addBankAccount($wallet, $bankData)
    {
        $bankAccount = new WalletBankAccount;
        $bankAccount->bank_id = $bankData['bankId'];
        $bankAccount->account_number = $bankData['accountNumber'];
        $bankAccount->account_name = $bankData['accountName'];
        $bankAccount->wallet_id = $wallet->id;
        $bankAccount->save();
    }

    public function setTransactionPin($wallet, $pin)
    {
        $wallet->transaction_pin = $pin;
        $wallet->update();

        return $wallet;
    }

    public function creditInvestmentProfit($wallet, $clientInvestment, $amount, $end=false)
    {
        // Utilities::logStuff("about to credit profits");
        $transaction = new WalletTransaction;
        $transaction->reference_no = $this->generateReferenceNo();
        // Utilities::logStuff("reference generated");
        $transaction->wallet_id = $wallet->id;
        $transaction->amount = $amount;
        $transaction->balance = $wallet->amount + $amount;
        $transaction->transaction_type = TransactionType::IN_FLOW->value;
        $transaction->source_type = WalletTransactionSource::INVESTMENT->value;
        $transaction->source_id = $clientInvestment->id;
        $transaction->confirmed = true;
        $transaction->save();

        // Utilities::logStuff("transaction saved");

        $wallet->amount = $transaction->balance;
        $wallet->total = $wallet->total + $amount;
        if($end) {
            $investmentAmount = $this->getTotalInvestmentAmount($clientInvestment->id);
            $wallet->locked_amount = $wallet->locked_amount - ($investmentAmount - $amount);
        }else{
            $wallet->locked_amount = $wallet->locked_amount + $amount;
        }
        $wallet->update();
        // Utilities::logStuff("Profit credited");

        return $wallet;
    }

    public function creditCommissionEarning($wallet, $clientCommission)
    {
        $transaction = new WalletTransaction;
        $transaction->reference_no = $this->generateReferenceNo();
        $transaction->wallet_id = $wallet->id;
        $transaction->amount = $clientCommission->amount_after_tax;
        $transaction->balance = $wallet->amount + $transaction->amount;
        $transaction->transaction_type = TransactionType::IN_FLOW->value;
        $transaction->source_type = WalletTransactionSource::COMMISSION->value;
        $transaction->source_id = $clientCommission->id;
        $transaction->confirmed = true;
        $transaction->save();

        $wallet->amount = $transaction->balance;
        $wallet->total = $wallet->total + $transaction->amount;

        $wallet->update();

        return $wallet;
    }

    public function generateWithdrawalRequest($wallet, $amount)
    {
        $withdrawalRequest = new WalletWithdrawalRequest;
        $withdrawalRequest->reference_no = $this->generateWithdrawalReferenceNo();
        $withdrawalRequest->wallet_id = $wallet->id;
        $withdrawalRequest->amount = $amount;
        $withdrawalRequest->status = WalletWithdrawalRequestStatus::PENDING->value;
        $withdrawalRequest->save();

        return $withdrawalRequest;
    }

    public function approveWithdrawalRequest($request, $userId)
    {
        $request->status = WalletWithdrawalRequestStatus::APPROVED->value;
        $request->user_id = $userId;
        $request->save();

        return $request;
    }

    public function debit($wallet, $amount)
    {
        $wallet->amount = $wallet->amount - $amount;

        $transaction = new WalletTransaction;
        $transaction->reference_no = $this->generateReferenceNo();
        $transaction->wallet_id = $wallet->id;
        $transaction->amount = $amount;
        $transaction->balance = $wallet->amount;
        $transaction->transaction_type = TransactionType::OUT_FLOW->value;
        $transaction->confirmed = true;
        $transaction->save();

        $wallet->update();

        return $wallet;
    }

    public function rejectWithdrawalRequest($request, $message, $userId)
    {
        $request->status = WalletWithdrawalRequestStatus::REJECTED->value;
        $request->rejected_reason = $message;
        $request->user_id = $userId;
        $request->save();

        return $request;
    }

    private function generateReferenceNo()
    {
        do{
            $referenceNo = Utilities::generateRandomNumber(12);
            $exists = $this->getTransactionsByReference($referenceNo);
        }while($exists);
        return $referenceNo;
    }

    private function generateWithdrawalReferenceNo()
    {
        do{
            $referenceNo = Utilities::generateRandomNumber(12);
            $exists = $this->getWithdrawalByReference($referenceNo);
        }while($exists);
        return $referenceNo;
    }
    

}
