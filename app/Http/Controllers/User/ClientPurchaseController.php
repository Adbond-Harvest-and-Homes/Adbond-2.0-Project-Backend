<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use ZipArchive;

use app\Http\Controllers\Controller;

use app\Http\Resources\ClientPurchaseResource;

use app\Services\ClientPurchaseService;

use app\Enums\ProjectType;
use app\Enums\Roles;
use app\Enums\PaymentStatus as PaymentStatusEnum;

use app\Jobs\SendPaymentEmail;

use app\Models\Payment;
use app\Models\PaymentStatus;

use app\Utilities;

class ClientPurchaseController extends Controller
{
    private $clientPurchaseService;

    public function __construct()
    {
        $this->clientPurchaseService = new ClientPurchaseService;
    }

    public function list(Request $request)
    {
        if (!$this->userIsAuthorized()) return Utilities::error401("You are not authorized to view client purchases");

        $filter = $this->buildFilters($request);
        if (!$filter['valid']) return Utilities::error402($filter['message']);
        $this->clientPurchaseService->filters = $filter['filter'];

        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if (!is_int((int) $page) || $page <= 0) $page = 1;
        if (!is_int((int) $perPage) || $perPage == null) $perPage = env('TRANSACTION_PAGINATION_PER_PAGE', 50);
        $offset = $perPage * ($page - 1);

        $with = ['client', 'purchase.package.project.projectType', 'purchase.paymentStatus', 'paymentReceipt'];
        $purchases = $this->clientPurchaseService->purchases($with, $offset, $perPage);

        $this->clientPurchaseService->count = true;
        $total = $this->clientPurchaseService->purchases();

        return Utilities::paginatedOkay([
            "purchases" => ClientPurchaseResource::collection($purchases),
        ], $page, $perPage, $total);
    }

    public function zip(Request $request)
    {
        if (!$this->userIsAuthorized()) return Utilities::error401("You are not authorized to download client purchase invoices");

        $filter = $this->buildFilters($request);
        if (!$filter['valid']) return Utilities::error402($filter['message']);
        $this->clientPurchaseService->filters = $filter['filter'];

        $cap = env('CLIENT_PURCHASES_ZIP_CAP', 200);

        $batch = (int) ($request->query('batch') ?? 1);
        if ($batch <= 0) $batch = 1;

        $this->clientPurchaseService->count = true;
        $total = $this->clientPurchaseService->purchases();

        if ($total == 0) return Utilities::error402("No purchases found for the selected filters");

        $totalBatches = (int) ceil($total / $cap);
        if ($batch > $totalBatches) return Utilities::error402("Batch {$batch} does not exist. There are {$totalBatches} batch(es) of up to {$cap} invoices each for the selected filters.");

        $this->clientPurchaseService->count = null;
        $offset = ($batch - 1) * $cap;
        $purchases = $this->clientPurchaseService->purchases(['client', 'paymentReceipt'], $offset, $cap);

        $zipDir = storage_path('app/exports');
        if (!is_dir($zipDir)) mkdir($zipDir, 0755, true);
        $zipPath = $zipDir . '/client_purchase_invoices_' . time() . '_batch' . $batch . '.zip';

        $receipts = [];
        foreach ($purchases as $purchase) {
            $receipt = $purchase->paymentReceipt;
            if ($receipt && $receipt->url) $receipts[$purchase->id] = $receipt;
        }

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $added = 0;
        $concurrency = env('CLIENT_PURCHASES_ZIP_CONCURRENCY', 20);
        foreach (array_chunk($receipts, $concurrency, true) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                foreach ($chunk as $id => $receipt) {
                    $pool->as($id)->timeout(20)->get($receipt->url);
                }
            });

            foreach ($chunk as $id => $receipt) {
                $response = $responses[$id] ?? null;
                if (!$response || $response instanceof \Throwable || !$response->successful()) continue;

                $filename = $receipt->filename ?: ('invoice-' . $id . '.pdf');
                $zip->addFromString($filename, $response->body());
                $added++;
            }
        }
        $zip->close();

        if ($added == 0) {
            @unlink($zipPath);
            return Utilities::error402("None of the matching purchases in this batch have a generated invoice yet");
        }

        return response()->download(
            $zipPath,
            "client-purchase-invoices-" . now()->format('Y-m-d') . "-batch-{$batch}-of-{$totalBatches}.zip",
            [
                'X-Total-Records' => $total,
                'X-Total-Batches' => $totalBatches,
                'X-Current-Batch' => $batch,
            ]
        )->deleteFileAfterSend(true);
    }

    public function sendInvoice(Request $request, $id)
    {
        if (!$this->userIsAuthorized()) return Utilities::error401("You are not authorized to send client purchase invoices");

        $payment = Payment::with(['client', 'paymentReceipt'])->where('id', $id)->where('confirmed', true)->first();
        if (!$payment) return Utilities::error402("Invoice not found");
        if (!$payment->paymentReceipt || !$payment->paymentReceipt->url) return Utilities::error402("No invoice has been generated for this purchase yet");
        if (!$payment->client || !$payment->client->email) return Utilities::error402("This client has no email on file");

        SendPaymentEmail::dispatch($payment, $payment->paymentReceipt->url);

        return Utilities::okay("Invoice is being sent to {$payment->client->email}");
    }

    public function sendInvoices(Request $request)
    {
        if (!$this->userIsAuthorized()) return Utilities::error401("You are not authorized to send client purchase invoices");

        $ids = $request->input('ids');
        if (!is_array($ids) || count($ids) == 0) return Utilities::error402("ids must be a non-empty array of purchase ids");

        $cap = env('CLIENT_PURCHASES_EMAIL_CAP', 200);
        if (count($ids) > $cap) return Utilities::error402("You can only send up to {$cap} invoices at a time");

        $payments = Payment::with(['client', 'paymentReceipt'])->whereIn('id', $ids)->where('confirmed', true)->get()->keyBy('id');

        $sent = [];
        $skipped = [];
        foreach ($ids as $id) {
            $payment = $payments->get($id);
            if (!$payment) {
                $skipped[] = ['id' => $id, 'reason' => 'Invoice not found'];
                continue;
            }
            if (!$payment->paymentReceipt || !$payment->paymentReceipt->url) {
                $skipped[] = ['id' => $id, 'reason' => 'No invoice has been generated for this purchase yet'];
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

    private function buildFilters(Request $request)
    {
        $filter = [];

        $start = $request->query('start');
        $end = $request->query('end');
        if (!$start || !$end) return ['valid' => false, 'message' => "start and end dates are required"];
        $filter['start'] = $start;
        $filter['end'] = $end;

        if ($request->query('text')) $filter['text'] = $request->query('text');

        if ($request->query('projectType')) {
            $validTypes = [ProjectType::LAND->value, ProjectType::AGRO->value, ProjectType::HOMES->value];
            if (!in_array($request->query('projectType'), $validTypes)) return ['valid' => false, 'message' => "Valid Project Types are: " . implode(', ', $validTypes)];
            $filter['projectType'] = $request->query('projectType');
        }

        if ($request->query('status')) {
            $validStatuses = [PaymentStatusEnum::AWAITING_PAYMENT->value, PaymentStatusEnum::PENDING->value, PaymentStatusEnum::DEPOSIT->value, PaymentStatusEnum::COMPLETE->value];
            if (!in_array($request->query('status'), $validStatuses)) return ['valid' => false, 'message' => "Valid Statuses are: " . implode(', ', $validStatuses)];
            $status = PaymentStatus::where('name', $request->query('status'))->first();
            if (!$status) return ['valid' => false, 'message' => "Invalid status"];
            $filter['status'] = $status->id;
        }

        return ['valid' => true, 'filter' => $filter];
    }

    private function userIsAuthorized()
    {
        $user = Auth::user();
        return $user && $user->role && in_array($user->role->name, [
            Roles::SUPER_ADMIN->value,
            Roles::ADMIN->value,
            Roles::OPERATION_ACCOUNTING->value,
        ]);
    }
}
