<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use ZipArchive;

use app\Http\Controllers\Controller;

use app\Http\Resources\ClientPurchaseResource;

use app\Services\ClientPurchaseService;

use app\Enums\ProjectType;
use app\Enums\Roles;
use app\Enums\PaymentStatus as PaymentStatusEnum;

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

        $with = ['client', 'package.project.projectType', 'paymentStatus', 'latestPaymentWithReceipt.paymentReceipt'];
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

        $this->clientPurchaseService->count = true;
        $total = $this->clientPurchaseService->purchases();

        $cap = env('CLIENT_PURCHASES_ZIP_CAP', 200);
        if ($total == 0) return Utilities::error402("No purchases found for the selected filters");
        if ($total > $cap) return Utilities::error402("This date range has {$total} matching purchases, which exceeds the limit of {$cap} for a single download. Please narrow your filters.");

        $this->clientPurchaseService->count = null;
        $purchases = $this->clientPurchaseService->purchases(['client', 'latestPaymentWithReceipt.paymentReceipt'], 0, $cap);

        $zipDir = storage_path('app/exports');
        if (!is_dir($zipDir)) mkdir($zipDir, 0755, true);
        $zipPath = $zipDir . '/client_purchase_invoices_' . time() . '.zip';

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $added = 0;
        foreach ($purchases as $purchase) {
            $receipt = $purchase->latestPaymentWithReceipt?->paymentReceipt;
            if (!$receipt || !$receipt->url) continue;

            $response = Http::get($receipt->url);
            if (!$response->successful()) continue;

            $filename = $receipt->filename ?: ('invoice-' . $purchase->id . '.pdf');
            $zip->addFromString($filename, $response->body());
            $added++;
        }
        $zip->close();

        if ($added == 0) {
            @unlink($zipPath);
            return Utilities::error402("None of the matching purchases have a generated invoice yet");
        }

        return response()->download($zipPath, 'client-purchase-invoices-' . now()->format('Y-m-d') . '.zip')->deleteFileAfterSend(true);
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
