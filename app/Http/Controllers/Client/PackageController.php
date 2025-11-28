<?php

namespace app\Http\Controllers\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Resources\PackageResource;

use app\Services\PackageService;

use app\Utilities;

class PackageController extends Controller
{
    private $packageService;

    public function __construct()
    {
        $this->packageService = new PackageService;
    }

    public function package($packageId)
    {
        if ($packageId && (!is_numeric($packageId) || !ctype_digit($packageId))) return Utilities::error402("Invalid parameter packageID");

        $package = $this->packageService->package($packageId, ['project.projectType', 'media']);

        if(!$package) return Utilities::error402("Package not found");

        return Utilities::ok(new PackageResource($package));
    }

    public function packages(Request $request, $projectId)
    {
        if (!is_numeric($projectId) || !ctype_digit($projectId)) return Utilities::error402("Invalid parameter projectID");
        $this->packageService->projectId = $projectId;

        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if(!is_int((int) $page) || $page <= 0) $page = 1;
        if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE');
        $offset = $perPage * ($page-1);

        $filter = [];
        if($request->query('countryId')) $this->packageService->countryId = $request->query('countryId');
        if($request->query('stateId')) $this->packageService->stateId = $request->query('stateId');
        if($request->query('text')) $filter["text"] = $request->query('text');
        if($request->query('date')) $filter["date"] = $request->query('date');
        if($request->query('status')) {
            $validStatus = ["active" => ProjectFilter::ACTIVE->value, "inactive" => ProjectFilter::INACTIVE->value];
            if(!in_array($request->query('status'), $validStatus)) return Utilities::error402("Valid Status are: ".$validStatus['active']." and ".$validStatus['inactive']);
            $filter["status"] = $request->query('status');
        }

        // $packages = $this->packageService->packages(['media'], $offset, $perPage);
        $packages = $this->packageService->filter($filter, ['media'], $offset, $perPage);
        $this->packageService->count = true;
        $packagesCount = $this->packageService->filter($filter);

        return ($request->has('all')) ? 
                        Utilities::ok(PackageResource::collection($packages))
                        :
                        Utilities::paginatedOkay(PackageResource::collection($packages), $page, $perPage, $packagesCount);
    }
}
