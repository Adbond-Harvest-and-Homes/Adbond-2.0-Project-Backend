<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;

use app\Exceptions\AppException;

use app\Services\JobAdvertService;
use app\Http\Resources\JobAdvertResource;

use app\Utilities;
use app\Helpers;

class JobAdvertController extends Controller
{
    public function __construct(protected JobAdvertService $service) {}

    public function index(Request $request)
    {
        $this->service->isOpen = true;
        $this->service->departmentId = $request->query('departmentId', null);
        $this->service->employmentTypeId = $request->query('employmentTypeId', null);

        $adverts = $this->service->getAdverts();

        return Utilities::ok(JobAdvertResource::collection($adverts));
    }

    public function show(string $slug)
    {
        $this->service->isOpen = true;

        $advert = $this->service->getBySlug($slug, ['benefits', 'requirements', 'responsibilities']);

        if (!$advert) return Utilities::error402("Advert not found");

        return Utilities::ok(new JobAdvertResource($advert));
    }
}
