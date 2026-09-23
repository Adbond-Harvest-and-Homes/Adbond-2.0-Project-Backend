<?php

namespace app\Services;

use app\Models\PackageRequest;

class PackageRequestService
{
    public $count = null;
    public $status = null;
    public $packageId = null;

    public function save($data)
    {
        $request = new PackageRequest;

        $request->name = $data['name'];
        $request->phone = $data['phone'];
        $request->email = $data['email'];
        $request->package_id = $data['package_id'];

        $request->save();

        return $request;
    }

    public function requests($offset = 0, $perPage = null)
    {
        $query = PackageRequest::with(['package', 'package.project'])->orderBy("created_at", "DESC");

        if ($this->status) $query->where("status", $this->status);
        if ($this->packageId) $query->where("package_id", $this->packageId);

        if ($this->count) return $query->count();

        if ($perPage == null) $perPage = env('PAGINATION_PER_PAGE');
        return $query->offset($offset)->limit($perPage)->get();
    }

    public function request($requestId)
    {
        return PackageRequest::with(['package', 'package.project'])->find($requestId);
    }

    public function updateStatus($request, $status)
    {
        $request->status = $status;
        $request->update();

        return $request;
    }
}
