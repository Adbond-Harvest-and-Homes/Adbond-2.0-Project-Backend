<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\AddDocType;

use app\Http\Resources\DocumentTypeResource;

use app\Services\DocumentTypeService;

use app\Utilities;

class DocumentTypeController extends Controller
{
    private $userActivityLogService;

    public function __construct(protected DocumentTypeService $docTypeService)
    {
        $this->userActivityLogService = new UserActivityLogService;
    }

    public function save(AddDocType $request)
    {
        $docType = $this->docTypeService->save($request->validated("name"));

        
            try {
                $this->userActivityLogService->log(Auth::user(), "Saved Document Type");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new DocumentTypeResource($docType));
    }

    public function update(AddDocType $request, $id)
    {
        $docType = $this->docTypeService->getDocumentType($id);
        if(!$docType) return Utilities::error402("Document Type not found");

        $existingType = $this->docTypeService->getByName($request->validated("name"));
        if($existingType && $existingType->id != $id) return Utilities::error402("This name already exists");

        $docType = $this->docTypeService->update($request->validated("name"), $docType);

        
            try {
                $this->userActivityLogService->log(Auth::user(), "Updated Document Type");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new DocumentTypeResource($docType));
    }

    public function docTypes()
    {
        $docTypes = $this->docTypeService->getDocumentTypes();

        return Utilities::ok(DocumentTypeResource::collection($docTypes));
    }

    public function docType(int $id)
    {
        $docType = $this->docTypeService->getDocumentType($id);
        if(!$docType) return Utilities::error402("Document Type not found");

        return Utilities::ok(new DocumentTypeResource($docType));
    }
}
