<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Resources\TransactionResource;

use app\Services\TransactionService;

use app\Jobs\SendPaymentEmail;

use app\Models\Order;
use app\Models\Payment;
use app\Models\PaymentMode;

use app\Enums\ProjectType;
use app\Enums\Roles;

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

    public function transactions(Request $request)
    {
        $this->applyTransactionRestrictions();

        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if (!is_int((int) $page) || $page <= 0) $page = 1;
        if (!is_int((int) $perPage) || $perPage == null) $perPage = env('TRANSACTION_PAGINATION_PER_PAGE', 50);
        $offset = $perPage * ($page - 1);

        $filter = [];
        if ($request->query('status')) {
            $validStatuses = ['pending', 'successful', 'failed'];
            $validStatusesString = '';
            foreach ($validStatuses as $valid) $validStatusesString .= $valid . ', ';
            if (!in_array($request->query('status'), $validStatuses)) return Utilities::error402("Valid Statuses are: " . $validStatusesString);
            switch ($request->query('status')) {
                case "pending":
                    $filter['status'] = null;
                    break;
                case "successful":
                    $filter['status'] = 1;
                    break;
                case "failed":
                    $filter['status'] = 0;
                    break;
            }
        }
        if ($request->query('text')) $filter["text"] = $request->query('text');
        if ($request->query('date')) $filter["date"] = $request->query('date');
        if ($request->query('projectType')) {
            $validTypes = [ProjectType::LAND->value, ProjectType::AGRO->value, ProjectType::HOMES->value];
            $validTypesString = '';
            foreach ($validTypes as $valid) $validTypesString .= $valid . ', ';
            if (!in_array($request->query('projectType'), $validTypes)) return Utilities::error402("Valid Project Types are: " . $validTypesString);
            $filter["projectType"] = $request->query('projectType');
        }
        if ($request->query('paymentMethod')) {
            $validPaymentMethods = ['cash', 'card'];
            $validPaymentMethodsString = '';
            foreach ($validPaymentMethods as $valid) $validPaymentMethodsString .= $valid . ', ';
            if (!in_array($request->query('paymentMethod'), $validPaymentMethods)) return Utilities::error402("Valid Payment Methods are: " . $validPaymentMethodsString);
            switch ($request->query('paymentMethod')) {
                case "cash":
                    $filter['paymentMethod'] = PaymentMode::bankTransfer()->id;
                    break;
                case "card":
                    $filter['paymentMethod'] = PaymentMode::bankTransfer()->id;
                    break;
            }
        }
        $this->transactionService->filters = $filter;

        $transactions = $this->transactionService->transactions(['client'], $offset, $perPage);
        // $pending = $transactions->filter(fn($transaction) => $transaction->confirmed === null);
        // $successful = $transactions->filter(fn($transaction) => $transaction->confirmed == 1);
        // $failed = $transactions->filter(fn($transaction) => $transaction->confirmed == 0);

        $this->transactionService->count = true;
        $transactionsCount = $this->transactionService->transactions();

        $this->transactionService->filters = ['status' => 1];
        $successfulCount = $this->transactionService->transactions();

        $this->transactionService->filters = ['status' => 0];
        $failedCount = $this->transactionService->transactions();

        $this->transactionService->filters = ['status' => null];
        $pendingCount = $this->transactionService->transactions();

        if (isset($filter['status'])) {
            switch ($filter['status']) {
                case null:
                    $defaultTotal = $pendingCount;
                    break;
                case 1:
                    $defaultTotal = $successfulCount;
                    break;
                case 0:
                    $defaultTotal = $failedCount;
                    break;
            }
        } else {
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

        $this->applyTransactionRestrictions();
        $transaction = $this->transactionService->transaction($transactionId);

        if (!$transaction) return Utilities::error402("Transaction not found");

        return Utilities::ok(new TransactionResource($transaction));
    }

    public function sendInvoice(Request $request, $id)
    {
        if (!$this->userIsAuthorizedToSendInvoices()) return Utilities::error401("You are not authorized to send transaction invoices");

        $payment = Payment::with(['client', 'paymentReceipt'])->where('id', $id)->where('purchase_type', Order::$type)->where('confirmed', true)->first();
        if (!$payment) return Utilities::error402("Transaction not found");
        if (!$payment->paymentReceipt || !$payment->paymentReceipt->url) return Utilities::error402("No invoice has been generated for this transaction yet");
        if (!$payment->client || !$payment->client->email) return Utilities::error402("This client has no email on file");

        SendPaymentEmail::dispatch($payment, $payment->paymentReceipt->url);

        return Utilities::okay("Invoice is being sent to {$payment->client->email}");
    }

    public function sendInvoices(Request $request)
    {
        if (!$this->userIsAuthorizedToSendInvoices()) return Utilities::error401("You are not authorized to send transaction invoices");

        $ids = $request->input('ids');
        if (!is_array($ids) || count($ids) == 0) return Utilities::error402("ids must be a non-empty array of transaction ids");

        $cap = env('CLIENT_PURCHASES_EMAIL_CAP', 200);
        if (count($ids) > $cap) return Utilities::error402("You can only send up to {$cap} invoices at a time");

        $payments = Payment::with(['client', 'paymentReceipt'])->whereIn('id', $ids)->where('purchase_type', Order::$type)->where('confirmed', true)->get()->keyBy('id');

        $sent = [];
        $skipped = [];
        foreach ($ids as $id) {
            $payment = $payments->get($id);
            if (!$payment) {
                $skipped[] = ['id' => $id, 'reason' => 'Transaction not found'];
                continue;
            }
            if (!$payment->paymentReceipt || !$payment->paymentReceipt->url) {
                $skipped[] = ['id' => $id, 'reason' => 'No invoice has been generated for this transaction yet'];
                continue;
            }
            if (!$payment->client || !$payment->client->email) {
                $skipped[] = ['id' => $id, 'reason' => 'This client has no email on file'];
                continue;
            }

            SendPaymentEmail::dispatch($payment, $payment->paymentReceipt->url);
            $sent[] = $id;
        }

        return Utilities::okay(count($sent) . " invoice(s) are being sent", ["sent" => $sent, "skipped" => $skipped]);
    }

    private function userIsAuthorizedToSendInvoices()
    {
        $user = Auth::user();
        return $user && $user->role && in_array($user->role->name, [
            Roles::SUPER_ADMIN->value,
            Roles::ADMIN->value,
            Roles::OPERATION_ACCOUNTING->value,
        ]);
    }

    private function applyTransactionRestrictions()
    {
        $user = Auth::user();
        $isAdmin = $user && $user->role && in_array($user->role->name, [
            Roles::SUPER_ADMIN->value,
            Roles::ADMIN->value,
            Roles::HUMAN_RESOURCE->value,
            Roles::OPERATION_ACCOUNTING->value
        ]);

        if (!$isAdmin) {
            $this->transactionService->user = $user;
        }
    }
}
