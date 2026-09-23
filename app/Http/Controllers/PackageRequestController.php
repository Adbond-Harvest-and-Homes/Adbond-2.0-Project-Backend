<?php

namespace app\Http\Controllers;

use app\Http\Requests\PackageRequestStoreRequest;

use app\Http\Resources\PackageRequestResource;

use app\Services\PackageRequestService;

use app\Utilities;

class PackageRequestController extends Controller
{
    private $packageRequestService;

    public function __construct()
    {
        $this->packageRequestService = new PackageRequestService;
    }

    public function store(PackageRequestStoreRequest $request)
    {
        try {
            $data = $request->validated();
            $packageRequest = $this->packageRequestService->save($data);

            return Utilities::ok(new PackageRequestResource($packageRequest));
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred while attempting to carry out this operation');
        }
    }
}
