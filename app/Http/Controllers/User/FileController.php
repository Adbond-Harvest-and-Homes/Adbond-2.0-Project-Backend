<?php

namespace app\Http\Controllers\User;

use app\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use app\Http\Requests\SavePhoto;
use app\Http\Requests\User\SaveClientPackageDocument;
use app\Http\Requests\User\DeleteFile;

use app\Http\Resources\FileResource;
use app\Http\Resources\AssetResource;

use app\Services\FileService;
use app\Services\ClientPackageService;

use app\Enums\FilePurpose;
use app\Utilities;

class FileController extends Controller
{
    private static $userType = "app\Models\User";
    private $fileService;
    private $clientPackageService;

    public function __construct()
    {
        $this->fileService = new FileService;
        $this->clientPackageService = new ClientPackageService;
    }

    public function savePhoto(SavePhoto $request)
    {
        try{
            $purpose = $request->validated("purpose");
            
            $res = $this->fileService->save($request->file('photo'), 'image', Auth::user()->id, $purpose, self::$userType, 'user-profile-photos');
            if($res['status'] != 200) return Utilities::error402('Sorry Photo could not be uploaded: '.$res['message']);

            return Utilities::ok(new FileResource($res['file']));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to send verification mail, Please try again later or contact support');
        }
    }

    public function saveClientDocument(SaveClientPackageDocument $request, $assetId)
    {
        // try{
            DB::beginTransaction();
            $data = $request->validated();

            $clientPackage = $this->clientPackageService->clientPackage($assetId);
            if(!$clientPackage) return Utilities::error402("Asset not found");

            $purpose = $data['description'];
            $ext =  $request->file('file')->getClientOriginalExtension();
            $folder = $this->clientFolder($data['description']);
            $this->fileService->belongsId = $clientPackage->id;
            $this->fileService->belongsType = "app\Models\ClientPackage";
            $this->fileService->docFile = true;
            $res = $this->fileService->save($request->file('file'), $ext, Auth::user()->id, $purpose, self::$userType, $folder);
            $oldFileId = null;

            if($res['status'] == 200) {
                $assetData = [];
                if($purpose == FilePurpose::CONTRACT->value) {
                    $assetData['contractFileId'] = $res['file']->id;
                    $oldFileId = $clientPackage->contract_file_id;
                }
                if($purpose == FilePurpose::DEED_OF_ASSIGNMENT->value) {
                    $assetData['doaFileId'] = $res['file']->id;
                    $oldFileId = $clientPackage->doa_file_id;
                }
                if($purpose == FilePurpose::LETTER_OF_HAPPINESS->value) {
                    $assetData['happinessLetterFileId'] = $res['file']->id;
                    $oldFileId = $clientPackage->happiness_letter_file_id;
                }
                
                $clientPackage = $this->clientPackageService->update($assetData, $clientPackage);
                if($oldFileId) $this->fileService->deleteFile($oldFileId);
            }else{
                return Utilities::error402("An Error Occurred, File could not be uploaded");
            }
            DB::commit();
            return Utilities::ok(new AssetResource($clientPackage));
        // }catch(\Exception $e){
            DB::rollBack();
        //     return Utilities::error($e, 'An error occurred while trying to send verification mail, Please try again later or contact support');
        // }
    }

    private function clientFolder($purpose)
    {
        switch($purpose) {
            case FilePurpose::PAYMENT_RECEIPT->value : return 'client-receipts'; break;
            case FilePurpose::LETTER_OF_HAPPINESS->value : return  "client-letter_of_happiness"; break;
            case FilePurpose::CONTRACT->value : return "client-contracts"; break;
            case FilePurpose::DEED_OF_ASSIGNMENT->value : return "client-deed-of-assignments"; break;
        }
        return "client-documents";
    }

    public function deleteFile(DeleteFile $request)
    {
        try{
            $file = $this->fileService->getFile($request->validated("fileId"));
            if(!$file) return Utilities::error402("File not found");

            $this->fileService->deleteFile($file);

            return Utilities::okay("File deleted successfully");
        } catch(\Exception $e) {
            return Utilities::error($e, "An Error Occurred while attempting to delete file");
        }
    }
}
