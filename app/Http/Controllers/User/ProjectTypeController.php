<?php

namespace app\Http\Controllers\User;

use app\Http\Controllers\Controller;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use app\Http\Resources\ProjectTypeResource;

use app\Http\Requests\User\UpdateProjectType;

use app\Models\User;

use app\Services\ProjectTypeService;
use app\Services\FileService;

use app\Enums\FilePurpose;
use app\Utilities;

class ProjectTypeController extends Controller
{
    private $userActivityLogService;

    private $projectTypeService;
    private $fileService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->projectTypeService = new ProjectTypeService;
        $this->fileService = new FileService;
    }

    public function update(UpdateProjectType $request)
    {
        try{
            $data = $request->validated();
            DB::beginTransaction();
            $projectType = $this->projectTypeService->projectType($data['id']);
            if(!isset($projectType)) return Utilities::error402("Project Type not found");

            // upload photo if it exists
            if($request->hasFile('photo')) {
                $purpose = FilePurpose::PROJECT_TYPE_PHOTO->value;
                if($projectType->file_id) $oldPhotoId = $projectType->file_id;
                $res = $this->fileService->save($request->file('photo'), 'image', Auth::user()->id, $purpose, User::$userType, 'project_type-photos');
                if($res['status'] != 200) return Utilities::error402('Sorry Photo could not be uploaded '.$res['message']);

                $data['photoId'] = $res['file']->id;
                $fileMeta = ["belongsId"=>$projectType->id, "belongsType"=>"app\Models\ProjectType"];
                $this->fileService->updateFileObj($fileMeta, $res['file']);
            }
            $projectType = $this->projectTypeService->update($data, $projectType);

            // delete the old photo if it exists
            if($oldPhotoId) $this->fileService->deleteFile($oldPhotoId);

            DB::commit();
            
            try {
                $this->userActivityLogService->log(Auth::user(), "Updated Project Type");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Project Type Updated Successfully", new ProjectTypeResource($projectType));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function projectTypes()
    {
        $projectTypes = $this->projectTypeService->projectTypes();
        return Utilities::ok(ProjectTypeResource::collection($projectTypes));
    }

    public function projectType($id)
    {
        if (!is_numeric($id) || !ctype_digit($id)) return Utilities::error402("Invalid parameter ID");
        $projectType = $this->projectTypeService->projectType($id);
        if(!$projectType) return Utilities::error402("ProjectType not found");
        return Utilities::ok(new ProjectTypeResource($projectType));
    }
}
