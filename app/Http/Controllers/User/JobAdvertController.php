<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use app\Http\Controllers\Controller;

use app\Exceptions\AppException;

use app\Http\Requests\User\CreateJobAdvert;
use app\Http\Requests\User\UpdateJobAdvert;

use app\Services\JobAdvertService;
use app\Http\Resources\JobAdvertResource;

use app\Utilities;
use app\Helpers;

class JobAdvertController extends Controller
{
    public function __construct(protected JobAdvertService $service) {}

    public function index(Request $request)
    {
        $this->service->isOpen = $request->query('isOpen', 1);
        $this->service->departmentId = $request->query('departmentId', null);
        $this->service->employmentTypeId = $request->query('employmentTypeId', null);

        $adverts = $this->service->getAdverts();

        return Utilities::ok(JobAdvertResource::collection($adverts));
    }

    public function show(int $id)
    {
        $advert = $this->service->getAdvert($id);

        if (!$advert) return Utilities::error402("Advert not found");

        return Utilities::ok(new JobAdvertResource($advert));
    }

    public function save(CreateJobAdvert $request)
    {
        $data = Helpers::formatRequestToSnake($request->validated());
        // $data = collect($request->validated())->mapWithKeys(function ($value, $key) {
        //     return [Str::snake($key) => $value];
        // })->all();

        $advert = $this->service->save($data);
        return Utilities::ok(new JobAdvertResource($advert));
    }

    public function update(UpdateJobAdvert $request, int $id)
    {
        $data = Helpers::formatRequestToSnake($request->validated());
        // collect($request->validated())->mapWithKeys(function ($value, $key) {
        //     return [Str::snake($key) => $value];
        // })->all();
        $advert = $this->service->update($id, $data);
        return Utilities::ok(new JobAdvertResource($advert));
    }

    public function delete(int $id)
    {
        $this->service->delete($id);
        return Utilities::okay("Job advert deleted successfully");
    }
}
