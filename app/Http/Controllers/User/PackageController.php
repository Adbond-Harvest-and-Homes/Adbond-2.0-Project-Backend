<?php

namespace app\Http\Controllers\User;

use app\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use app\Exceptions\AppException;

use app\Http\Resources\PackageResource;
use app\Http\Resources\FileResource;

use app\Http\Requests\User\SavePackage;
use app\Http\Requests\User\UpdatePackage;
use app\Http\Requests\User\TogglePackageActivate;
use app\Http\Requests\User\FilterPackage;
use app\Http\Requests\SaveMedia;
use app\Http\Requests\SaveMultipleMedia;
use app\Http\Requests\User\AddProductPromo;
use app\Http\Requests\User\RemoveProductPromo;

use app\Models\User;
use app\Models\Package;

use app\Services\PackageService;
use app\Services\FileService;
use app\Services\ProjectService;
use app\Services\PromoService;

use app\Enums\FilePurpose;
use app\Enums\PackageType;
use app\Enums\ProjectFilter;
use app\Enums\InvestmentRedemptionOption;

use app\Utilities;


class PackageController extends Controller
{
    private $packageService;
    private $projectService;
    private $fileService;
    private $promoService;

    public function __construct()
    {
        $this->packageService = new PackageService;
        $this->projectService = new ProjectService;
        $this->fileService = new FileService;
        $this->promoService = new PromoService;
    }

    public function allPackages(Request $request)
    {
        $this->packageService->active = 1;
        $packages = $this->packageService->packages();

        return Utilities::ok(PackageResource::collection($packages));
    }

    public function packages(Request $request, $projectId)
    {
        if (!is_numeric($projectId) || !ctype_digit($projectId)) return Utilities::error402("Invalid parameter projectID");
        $this->packageService->projectId = $projectId;

        if($request->has('all')) $this->projectService->all - true;

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

    public function package($id)
    {
        if (!is_numeric($id) || !ctype_digit($id)) return Utilities::error402("Invalid parameter iD");

        $package = $this->packageService->package($id, ['media']);

        if(!$package) return Utilities::error402("Package not found");

        return Utilities::ok(new PackageResource($package));
    }

    public function save(SavePackage $request)
    {
        try{
            DB::beginTransaction();
            $data = $request->validated();
            // dd($data);
            $data['userId'] = Auth::user()->id;
            // if brochure file is uploaded
            if($request->hasFile('brochureFile')) { 
                $brochureUpload = $this->uploadBrochure($request->file('brochureFile'));
                if($brochureUpload['success']) {
                    $data['brochureFileId'] = $brochureUpload['file']->id;
                }else{
                    Utilities::logStuff("unable to upload brochure.. ".$brochureUpload['message']);
                }
            }
            $package = $this->packageService->save($data);

            if($request->hasFile('brochureFile')) { 
                // update the file object
                $fileMeta = ["belongsId"=>$package->id, "belongsType"=>"app\Models\Package"];
                $this->fileService->updateFileObj($fileMeta, $brochureUpload['file']);
            }

            // if(isset($data['packagePhotoIds'])) $this->packageService->savePhotos($data['packagePhotoIds'], $package);
            $package = $this->packageService->package($package->id);
            // dd($package->benefits);
            DB::commit();

            return Utilities::ok(new PackageResource($package));
        }catch(AppException $e){
            DB::rollBack();
            throw $e;
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to save Package, Please try again later or contact support');
        }
    }

    public function saveMedia(SaveMedia $request)
    {
        // dd($request->file('media')->getMimeType());
        // phpinfo();
        // try{
            $mime = $request->file('media')->getMimeType();
            $imageMimes = ['image/jpeg', 'image/jpg', 'image/gif', 'image/png'];
            $fileType = (in_array($mime, $imageMimes)) ? "image" : "video";
            $purpose = (in_array($mime, $imageMimes)) ? FilePurpose::PACKAGE_PHOTO->value : FilePurpose::PACKAGE_VIDEO->value;
            $res = $this->fileService->save($request->file('media'), $fileType, Auth::user()->id, $purpose, User::$userType, 'package-media');  
            if($res['status'] == 200) return Utilities::ok(new FileResource($res['file']));
            
            return Utilities::error402($res['message']);
        // }catch(\Exception $e){
        //     return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        // }
    }

    public function saveMultipleMedia(SaveMultipleMedia $request)
    {
        // dd($request->file('media')->getMimeType());
        // phpinfo();
        // try{
        $successfulIds = [];
        $failedFiles = [];
        foreach($request->file('media') as $file) {
            $mime = $file->getMimeType();
            $filename = $file->getClientOriginalName();
            $imageMimes = ['image/jpeg', 'image/jpg', 'image/gif', 'image/png'];
            $fileType = (in_array($mime, $imageMimes)) ? "image" : "video";
            $purpose = (in_array($mime, $imageMimes)) ? FilePurpose::PACKAGE_PHOTO->value : FilePurpose::PACKAGE_VIDEO->value;
            $res = $this->fileService->save($file, $fileType, Auth::user()->id, $purpose, User::$userType, 'package-media');  
            if($res['status'] == 200) {
                $successfulIds[] = $res['file']->id;
            }else{
                $failedFiles[] = $filename;
                Utilities::logStuff("Error attempting to save Package media.. ".$res['message']);
            }
        }
            
        return Utilities::ok([
            "successfulIds" => $successfulIds,
            "failedFiles" => $failedFiles
        ]);
        // }catch(\Exception $e){
        //     return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        // }
    }

    public function update(UpdatePackage $request, $packageId)
    {
        try{
            DB::beginTransaction();
            $data = $request->validated();
            // Get The package being updated
            $package = $this->packageService->package($packageId);
            if(!$package) return Utilities::error402("package not found");

            if($package->type == PackageType::NON_INVESTMENT->value || (isset($data['type']) && $data['type'] == PackageType::NON_INVESTMENT->value)){
                if(isset($data["interestReturnDuration"]) && $data["interestReturnDuration"]) return Utilities::error402("Package is not an investment package, hence cannot have Interest Return");
                if(isset($data["interestReturnTimeline"]) && $data["interestReturnTimeline"]) return Utilities::error402("Package is not an investment package, hence cannot have Interest Return");
                if(isset($data["interestReturnPercentage"]) && $data["interestReturnPercentage"]) return Utilities::error402("Package is not an investment package, hence cannot have Interest Return");
                if(isset($data["interestReturnAmount"]) && $data["interestReturnAmount"]) return Utilities::error402("Package is not an investment package, hence cannot have Interest Return");
                if(isset($data["redemptionOptions"]) && $data["redemptionOptions"]) return Utilities::error402("Package is not an investment package, hence cannot have Redemption Options");
                if(isset($data["redemptionPackageId"]) && $data["redemptionPackageId"]) return Utilities::error402("Package is not an investment package, hence cannot have Redemption Package");
            }

            if($package->type == PackageType::INVESTMENT->value || (isset($data['type']) && $data['type'] == PackageType::INVESTMENT->value)){
                if(!$package->interest_duration && !isset($data["interestReturnDuration"])) return Utilities::error402("Interest Return Duration is required");
                if(!$package->interest_return_timeline && !isset($data["interestReturnTimeline"])) return Utilities::error402("Interest Return Timeline is Required");
                if((!$package->interest_return_percentage && !isset($data["interestReturnAmount"])) && !isset($data["interestReturnPercentage"])) return Utilities::error402("Interest Return Percentage is Required");
                if((!$package->interest_return_amount && !isset($data["interestReturnPercentage"])) && !isset($data["interestReturnAmount"])) return Utilities::error402("Interest Return Amount is required when the Percentage is not set");
                if(!$package->redemption_options && !isset($data["redemptionOptions"])) return Utilities::error402("Redemption Options is required");
                if((!$package->redemption_package_id && !empty(array_intersect($data["redemptionOptions"], [
                    InvestmentRedemptionOption::PROFIT_ONLY->value,
                    InvestmentRedemptionOption::PROPERTY->value
                ]))) && !isset($data["redemptionPackageId"])) return Utilities::error402("Redemption Package is required");
                
            }

            // if brochure file is uploaded
            if($request->hasFile('brochureFile')) { 
                $brochureUpload = $this->uploadBrochure($request->file('brochureFile'), $package);
                if($brochureUpload['success']) {
                    $data['brochureFileId'] = $brochureUpload['file']->id;
                }else{
                    Utilities::logStuff("unable to upload brochure.. ".$brochureUpload['message']);
                }
            }

            if(isset($data['packageMediaIds']) && !empty($data['packageMediaIds'])) {
                // Get the Media Ids of the Package
                $currentMediaIds = $this->packageService->getPackageMediaIds($package);

                // Get the Files that were added
                $newMediaIds = array_diff($data['packageMediaIds'], $currentMediaIds);
                // Get the Files that were deleted
                $removedMediaIds = array_diff($currentMediaIds, $data['packageMediaIds']);
                $data['packageMediaIds'] = $newMediaIds;
            }
            // Add and Delete files as necessary
            // if(!empty($newMediaIds)) $this->packageService->saveMedias($newMediaIds, $package);

            $package = $this->packageService->update($data, $package);
            if(!empty($removedMediaIds)) $this->fileService->deleteFiles($removedMediaIds);

            $package = $this->packageService->package($package->id, ['project', 'media']);
            DB::commit();

            return Utilities::ok(new PackageResource($package));
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function filter(FilterPackage $request, $projectId)
    {
        try{
            if (!is_numeric($projectId) || !ctype_digit($projectId)) return Utilities::error402("Invalid parameter projectID");
            $page = ($request->query('page')) ?? 1;
            $perPage = ($request->query('perPage'));
            if(!is_int((int) $page) || $page <= 0) $page = 1;
            if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE');
            $offset = $perPage * ($page-1);

            $filter = $request->validated();
            $this->packageService->projectId = $projectId;
            $packages = $this->packageService->filter($filter, [], $offset, $perPage);
            $this->packageService->count = true;
            $packagesCount = $this->packageService->filter($filter);

            return Utilities::paginatedOkay(PackageResource::collection($packages), $page, $perPage, $packagesCount);
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function search(Request $request, $projectId=null)
    {
        try{
            if($projectId != null) {
                if (!is_numeric($projectId) || !ctype_digit($projectId)) return Utilities::error402("Invalid parameter projectID");
                $this->packageService->projectId = $projectId;
            }

            $page = ($request->query('page')) ?? 1;
            $perPage = ($request->query('perPage'));
            if(!is_int((int) $page) || $page <= 0) $page = 1;
            if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE');
            $offset = $perPage * ($page-1);

            $text = ($request->query('text')) ?? null;
            $packages = $this->packageService->search($text, $offset, $perPage);
            $this->packageService->count = true;
            $packagesCount = $this->packageService->search($text);

            return Utilities::paginatedOkay(PackageResource::collection($packages), $page, $perPage, $packagesCount);
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    
    public function export(Request $request, $projectId)
    {
        try {
            if (!is_numeric($projectId) || !ctype_digit($projectId)) return Utilities::error402("Invalid parameter projectID");
            $this->packageService->projectId = $projectId;
            $project  = $this->projectService->project($projectId);

            if(!$project) return Utilities::error402("Project not found, Project Id provided is invalid");

            $packages = $this->packageService->packages();

            $type = ($request->query('type')) ?? null;
            if(!$type) return Utilities::error402("Type is required");

            // Handle different export types
            switch ($type) {
                case 'excel':
                    return $this->packageService->exportToExcel($packages);
                case 'pdf':
                    return $this->packageService->exportToPDF($packages, $project->name);
                default:
                    return Utilities::error402("Invalid export type");
            }
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred during export');
        }
    }

    public function markAsSoldOut(TogglePackageActivate $request)
    {
        try{
            $id = $request->validated("id");
            $package = $this->packageService->package($id);
            if(!$package) return Utilities::error402("Package not found");

            if($package->sold_out) return Utilities::error402("Package is already sold out");
            $package = $this->packageService->markAsSoldOut($package);

            return Utilities::okay("Package is marked out sold out", new PackageResource($package));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function markAsInStock(TogglePackageActivate $request)
    {
        try{
            $id = $request->validated("id");
            $package = $this->packageService->package($id);
            if(!$package) return Utilities::error402("Package not found");

            if(!$package->sold_out) return Utilities::error402("Package is already not Sold Out");
            $package = $this->packageService->markAsBackInStock($package);

            return Utilities::okay("Package is marked as back in Stock", new PackageResource($package));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function activate(TogglePackageActivate $request)
    {
        try{
            $id = $request->validated("id");
            $package = $this->packageService->package($id);
            if(!$package) return Utilities::error402("Package not found");

            if($package->active) return Utilities::error402("Package is already active");
            $package = $this->packageService->activate($package);

            return Utilities::okay("Package Activated Successfully", new PackageResource($package));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function deactivate(TogglePackageActivate $request)
    {
        try{
            $id = $request->validated("id");
            $package = $this->packageService->package($id);
            if(!$package) return Utilities::error402("Package not found");

            if(!$package->active) return Utilities::error402("Package is already inactive");
            $package = $this->packageService->deactivate($package);

            return Utilities::okay("Package Deactivated Successfully", new PackageResource($package));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function delete(TogglePackageActivate $request)
    {
        try{
            $id = $request->validated("id");
            $package = $this->packageService->package($id);
            if(!$package) return Utilities::error402("Package not found");

            if($package->active) return Utilities::error402("Cannot delete an active Package");
            if($package->assets->count() > 0) return Utilities::error402("Cannot delete this Package");
            $this->packageService->delete($package);

        } catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function addPromo(AddProductPromo $request)
    {
        try{
            $data = $request->validated();

            $package = $this->packageService->package($data['productId']);
            if(!$package) return Utilities::error402("Package not found");

            $product['type'] = Package::$type;
            $product['id'] = $data['productId'];
            $product['promoId'] = $data['promoId'];

            $promoProduct = $this->promoService->savePromoProduct($product);

            return Utilities::okay("Promo has been Added to the Package Successfully");

        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function removePromo(RemoveProductPromo $request)
    {
        try{
            $data = $request->validated();
            $package = $this->packageService->package($data['productId']);
            if(!$package) return Utilities::error402("Package not found");

            $promoProduct = $this->promoService->getPromoProductByDetail($data["promoId"], Package::$type, $data['productId']);
            if(!$promoProduct) return Utilities::error402("Promo Product not found");

            $this->promoService->removePromoProduct($promoProduct);

            return Utilities::okay("Promo has been Removed to the Package Successfully");

        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    private function uploadBrochure($file, $package=null)
    {
        $oldFileId = null;
        $purpose = FilePurpose::PACKAGE_BROCHURE->value;
        $message = null;
        if($package) {
            if($package && $package->brochure_file_id) $oldFileId = $package->brochure_file_id;
            $this->fileService->belongsId = $package->id;
            $this->fileService->belongsType = "app\Models\Package";
        }
        $mimeType = $file->getMimeType;
        $fileType = explode('/', $mimeType)[0]; 
        
        $res = $this->fileService->save($file, $fileType, Auth::user()->id, $purpose, User::$userType, 'package-brochures');
        if($res['status'] != 200) $message = $res['message'];

        // delete the old file if it exists
        if($oldFileId) $this->fileService->deleteFile($oldFileId);

        return ($message) ? ["success" => false, "message" => $message] : [ "success" => true, "file" => $res['file']];
    }
}
