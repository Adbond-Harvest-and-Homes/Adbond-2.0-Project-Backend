<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;

use app\Http\Resources\PackageResource;

use app\Services\PackageService;
use app\Services\ProjectTypeService;
use app\Utilities;

class PackageController extends Controller
{
    private $packageService;
    private $projectTypeService;

    public function __construct()
    {
        $this->packageService = new PackageService;
        $this->projectTypeService = new ProjectTypeService;
    }

    public function getPackage($packageId)
    {
        if (!is_numeric($packageId) || !ctype_digit($packageId)) return Utilities::error402("Invalid parameter packageID");
        $package = $this->packageService->package($packageId, ['photos', 'state', 'media']);

        if(!$package) return Utilities::error402("Package not found");

        return Utilities::ok(new PackageResource($package));
    }

    public function getPackages($projectId)
    {
        if (!is_numeric($projectId) || !ctype_digit($projectId)) return Utilities::error402("Invalid parameter projectID");
        $this->packageService->projectId = $projectId;

        $packages = $this->packageService->activePackages(['state', 'packagePhotos', 'media']);

        return Utilities::ok(PackageResource::collection($packages));
    }

    public function getProjectTypePackages(Request $request, $projectTypeId)
    {
        if (!is_numeric($projectTypeId) || !ctype_digit($projectTypeId)) return Utilities::error402("Invalid parameter projectTypeID");
        
        $projectType = $this->projectTypeService->projectType($projectTypeId);
        if(!$projectType) return Utilities::error402("Project Type not found");

        $perPage = ($request->query('perPage'));
        if(!is_int((int) $perPage) || $perPage==null) $perPage = 4;

        $packages = $projectType->packages()->with(['stateModel', 'packagePhotos', 'media'])->where("packages.active", 1)->orderBy("created_at", "DESC")->limit($perPage)->get();

        return Utilities::ok(PackageResource::collection($packages));
    }
}
