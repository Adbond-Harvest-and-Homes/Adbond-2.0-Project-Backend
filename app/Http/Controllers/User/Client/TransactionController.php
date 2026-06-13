<?php

namespace app\Http\Controllers\User\Client;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Resources\TransactionResource;

use app\Services\TransactionService;
use app\Utilities;

class TransactionController extends Controller
{
    private $userActivityLogService;

    private $transactionService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->transactionService = new TransactionService;
    }

    public function transactions(Request $request, $clientId)
    {
        if (!is_numeric($clientId) || !ctype_digit($clientId)) return Utilities::error402("Invalid parameter clientID");

        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if(!is_int((int) $page) || $page <= 0) $page = 1;
        if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE');
        $offset = $perPage * ($page-1);

        $this->transactionService->clientId = $clientId;
        $transactions = $this->transactionService->transactions([], $offset, $perPage);

        $this->transactionService->count = true;
        $transactionsCount = $this->transactionService->transactions();

        return Utilities::paginatedOkay(TransactionResource::collection($transactions), $page, $perPage, $transactionsCount);
    }

    public function transaction($transactionId)
    {
        if (!is_numeric($transactionId) || !ctype_digit($transactionId)) return Utilities::error402("Invalid parameter transactionID");

        $transaction = $this->transactionService->transaction($transactionId);

        return Utilities::ok(new TransactionResource($transaction));
    }
}
