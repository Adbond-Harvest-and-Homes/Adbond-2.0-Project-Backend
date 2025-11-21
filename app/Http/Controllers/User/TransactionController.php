<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Http\Controllers\Controller;

use app\Http\Resources\TransactionResource;

use app\Services\TransactionService;

use app\Models\PaymentMode;

use app\Enums\ProjectType;

use app\Utilities;

class TransactionController extends Controller
{
    private $transactionService;

    public function __construct()
    {
        $this->transactionService = new TransactionService;
    }

    public function transactions(Request $request)
    {
        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if(!is_int((int) $page) || $page <= 0) $page = 1;
        if(!is_int((int) $perPage) || $perPage==null) $perPage = env('TRANSACTION_PAGINATION_PER_PAGE', 50);
        $offset = $perPage * ($page-1);

        $filter = [];
        if($request->query('status')) {
            $validStatuses = ['pending', 'successful', 'failed'];
            $validStatusesString = '';
            foreach($validStatuses as $valid) $validStatusesString .= $valid.', ';
            if(!in_array($request->query('status'), $validStatuses)) return Utilities::error402("Valid Statuses are: ".$validStatusesString);
            switch($request->query('status')) {
                case "pending" : $filter['status'] = null; break;
                case "successful" : $filter['status'] = 1; break;
                case "failed" : $filter['status'] = 0; break;
            }
        }
        if($request->query('text')) $filter["text"] = $request->query('text');
        if($request->query('date')) $filter["date"] = $request->query('date');
        if($request->query('projectType')) {
            $validTypes = [ProjectType::LAND->value, ProjectType::AGRO->value, ProjectType::HOMES->value];
            $validTypesString = '';
            foreach($validTypes as $valid) $validTypesString .= $valid.', ';
            if(!in_array($request->query('projectType'), $validTypes)) return Utilities::error402("Valid Project Types are: ".$validTypesString);
            $filter["projectType"] = $request->query('projectType');
        }
        if($request->query('paymentMethod')) {
            $validPaymentMethods = ['cash', 'card'];
            $validPaymentMethodsString = '';
            foreach($validPaymentMethods as $valid) $validPaymentMethodsString .= $valid.', ';
            if(!in_array($request->query('paymentMethod'), $validPaymentMethods)) return Utilities::error402("Valid Payment Methods are: ".$validPaymentMethodsString);
            switch($request->query('paymentMethod')) {
                case "cash" : $filter['paymentMethod'] = PaymentMode::bankTransfer()->id; break;
                case "card" : $filter['paymentMethod'] = PaymentMode::bankTransfer()->id; break;
            }
        }
        $this->transactionService->filters = $filter;

        $transactions = $this->transactionService->transactions(['client'], $offset, $perPage);
        // $pending = $transactions->filter(fn($transaction) => $transaction->confirmed === null);
        // $successful = $transactions->filter(fn($transaction) => $transaction->confirmed == 1);
        // $failed = $transactions->filter(fn($transaction) => $transaction->confirmed == 0);

        $this->transactionService->count = true;
        $transactionsCount = $this->transactionService->transactions();

        $this->transactionService->filters = ['status'=>1];
        $successfulCount = $this->transactionService->transactions();

        $this->transactionService->filters = ['status'=>0];
        $failedCount = $this->transactionService->transactions();

        $this->transactionService->filters = ['status'=>null];
        $pendingCount = $this->transactionService->transactions();

        if(isset($filter['status'])) {
            switch($filter['status']) {
                case null : $defaultTotal = $pendingCount; break;
                case 1 : $defaultTotal = $successfulCount; break;
                case 0 : $defaultTotal = $failedCount; break;
            }
        }else{
            $defaultTotal = $transactionsCount;
        }
        return Utilities::paginatedOkay([
            "transactions" => TransactionResource::collection($transactions),
            "transactionsCount" => $transactionsCount,
            "successfulCount" => $successfulCount,
            "pendingCount" => $pendingCount,
            "failedCount" => $failedCount
        ], $page, $perPage, $defaultTotal);
    }

    public function transaction($transactionId)
    {
        if (!is_numeric($transactionId) || !ctype_digit($transactionId)) return Utilities::error402("Invalid parameter transactionID");

        $transaction = $this->transactionService->transaction($transactionId);

        return Utilities::ok(new TransactionResource($transaction));
    }
}
