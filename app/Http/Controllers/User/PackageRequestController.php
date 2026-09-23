<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;

use app\Http\Controllers\Controller;

use app\Http\Resources\PackageRequestResource;

use app\Services\PackageRequestService;

use app\Utilities;

class PackageRequestController extends Controller
{
    private $packageRequestService;

    private $allowedStatuses = ['pending', 'contacted', 'closed'];

    public function __construct()
    {
        $this->packageRequestService = new PackageRequestService;
    }

    public function list(Request $request)
    {
        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if (!is_int((int) $page) || $page <= 0) $page = 1;
        if (!is_int((int) $perPage) || $perPage == null) $perPage = env('PAGINATION_PER_PAGE');
        $offset = $perPage * ($page - 1);

        $status = $request->query('status');
        if ($status && !in_array($status, $this->allowedStatuses)) return Utilities::error402("Invalid parameter status");

        $this->packageRequestService->status = $status;
        $this->packageRequestService->packageId = $request->query('packageId');

        $requests = $this->packageRequestService->requests($offset, $perPage);

        $this->packageRequestService->count = true;
        $total = $this->packageRequestService->requests();

        return Utilities::paginatedOkay(PackageRequestResource::collection($requests), $page, $perPage, $total);
    }

    public function request($requestId)
    {
        if (!is_numeric($requestId) || !ctype_digit($requestId)) return Utilities::error402("Invalid parameter requestID");

        $packageRequest = $this->packageRequestService->request($requestId);
        if (!$packageRequest) return Utilities::error402("Package request not found");

        return Utilities::ok(new PackageRequestResource($packageRequest));
    }

    public function updateStatus(Request $request, $requestId)
    {
        if (!is_numeric($requestId) || !ctype_digit($requestId)) return Utilities::error402("Invalid parameter requestID");

        $status = $request->input('status');
        if (!$status || !in_array($status, $this->allowedStatuses)) return Utilities::error402("Invalid parameter status");

        $packageRequest = $this->packageRequestService->request($requestId);
        if (!$packageRequest) return Utilities::error402("Package request not found");

        $packageRequest = $this->packageRequestService->updateStatus($packageRequest, $status);

        return Utilities::ok(new PackageRequestResource($packageRequest));
    }
}
