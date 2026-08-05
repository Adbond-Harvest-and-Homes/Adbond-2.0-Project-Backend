<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\UploadDoa;

use app\Http\Resources\AssetResource;

use app\Services\ClientPackageService;
use app\Services\AssetService;
use app\Services\FileService;

use app\Models\ClientPackage;
use app\Models\Client;

use app\Enums\FilePurpose;

use app\Utilities;

class AssetController extends Controller
{
    private $userActivityLogService;

    private $assetService;
    private $clientPackageService;
    private $fileService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->assetService = new AssetService;   
        $this->clientPackageService = new ClientPackageService;
        $this->fileService = new FileService;
    }

    public function saveDoa(UploadDoa $request)
    {
        try{
            $asset = $this->assetService->asset($request->validated("assetId"));
            if(!$asset) return Utilities::error402("Asset not found");

            $file = $request->file('doa');
            $fileType = Utilities::getFileType($file->getMimeType());
            $this->fileService->belongsId = $asset->id;
            $this->fileService->belongsType = ClientPackage::$type;
            $res = $this->fileService->save($file, $fileType, Auth::user()->id, FilePurpose::DEED_OF_ASSIGNMENT->value, Client::$userType, 'doa');
            if($res['status'] != 200) return Utilities::error402('Sorry file could not be uploaded: '.$res['message']);

            $asset = $this->assetService->saveDoa($res['file']->id, $asset);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Saved Asset Deed of Assignment");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return new AssetResource($asset);
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function assets(Request $request)
    {
        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if(!is_int((int) $page) || $page <= 0) $page = 1;
        if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE');
        $offset = $perPage * ($page-1);

        $filter = [];
        if($request->query('text')) $filter["text"] = $request->query('text');
        if($request->query('date')) $filter["date"] = $request->query('date');
        
        $this->assetService->filter = $filter;
        $assets = $this->assetService->assets(['client'], $offset, $perPage);

        // Get Count
        $this->assetService->count = true;
        $assetsCount = $this->assetService->assets();

        return Utilities::paginatedOkay(AssetResource::collection($assets), $page, $perPage, $assetsCount);
    }
}
