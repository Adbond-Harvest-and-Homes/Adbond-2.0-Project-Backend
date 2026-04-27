<?php

namespace app\Services;

use app\Models\Profile;

//use Cloudinary;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File as FileFacade;

use app\Enums\FilePurpose;

use app\Models\File;

class FileService 
{

    private static $userType = "app\Models\Client";
    public $belongsId = null;
    public $belongsType = null;
    public $docFile = false;

    public function getFile($id)
    {
        return File::find($id);
    }

    public function getFiles()
    {
        return File::all();
    }

    public function getSpecificFile($belongsType, $belongsId, $purpose)
    {
        return File::where("belongs_type", $belongsType)->where("belongs_id", $belongsId)->where("purpose", $purpose)->first();
    }

    public function save($file, $fileType, $user_id, $purpose, $user_type = null, $folder=null)
    {
        $uploadedFile = ($fileType=='image' || $fileType=='video') ? 
                    $this->uploadMedia($file, $fileType, $folder) 
                    : 
                    (($this->docFile) ? $this->uploadDocFile($file, $purpose, $folder) : $this->uploadDoc($file, $purpose, $folder));
        if($uploadedFile) {
            $status = $this->getStatus($uploadedFile);
            //dd($uploadedFile->offsetGet('secure_url'));
            if($status['code']==200) {
                $url = $uploadedFile->offsetGet('url');
                $secureUrl = $uploadedFile->offsetGet('secure_url');
                $publicId = $uploadedFile->offsetGet('public_id');
                $filename = $this->getFilename($publicId);
                // $uploadDate = $uploadedFile->getTimeUploaded();
                if($fileType=='image') $width = $uploadedFile->offsetGet('width'); 
                if($fileType=='image') $height = $uploadedFile->offsetGet('height');
                $size = $uploadedFile->offsetGet('bytes'); // Get the size of the uploaded file in bytes
                $rSize = $this->convertSize($size); // Get the size of the uploaded file in bytes, megabytes, gigabytes or terabytes. E.g 1.5 MB
                
                // if(is_string($file)) dd($file);
                $fileObj = new File;
                $fileObj->filename = $filename;
                $fileObj->url = $secureUrl;
                $fileObj->public_id = $publicId;
                $fileObj->purpose = $purpose;
                if($fileType=='image') $fileObj->width = $width;
                if($fileType=='image') $fileObj->height = $height;
                $fileObj->size = $size;
                $fileObj->formatted_size = $rSize;
                $fileObj->user_id = $user_id;
                if($user_type) $fileObj->user_type = $user_type;
                $fileObj->file_type = $fileType;
                $fileObj->mime_type = (is_string($file)) ? FileFacade::mimeType($file) : $file->getMimeType();
                $fileObj->original_filename = (is_string($file)) ? pathinfo($file, PATHINFO_FILENAME) : $file->getClientOriginalName();
                $fileObj->extension = (is_string($file)) ? pathinfo($file, PATHINFO_EXTENSION) : $file->getClientOriginalExtension();
                if($this->belongsId) $fileObj->belongs_id = $this->belongsId;
                if($this->belongsType) $fileObj->belongs_type = $this->belongsType;
                $fileObj->save();
                return ['status'=>200, 'file'=>$fileObj];
            }else{
                return ['status'=>$status['code'], 'message'=>$status['message'], 'file'=>$file->getClientOriginalName()];
            }
        }else{
            return ['status'=>402, 'message'=>'File upload failed', 'file'=>$file->getClientOriginalName()];
        }
        
    }

    private function saveToDB($file, $uploadedFile, $fileType, $user_id=null, $user_type = null)
    {
        $url = $uploadedFile->offsetGet('url');
        $secureUrl = $uploadedFile->offsetGet('secure_url');
        $publicId = $uploadedFile->offsetGet('public_id');
        $filename = $this->getFilename($publicId);
        // $uploadDate = $uploadedFile->getTimeUploaded();
        if($fileType=='image') $width = $uploadedFile->offsetGet('width'); 
        if($fileType=='image') $height = $uploadedFile->offsetGet('height');
        $size = $uploadedFile->offsetGet('bytes'); // Get the size of the uploaded file in bytes
        $rSize = $this->convertSize($size); // Get the size of the uploaded file in bytes, megabytes, gigabytes or terabytes. E.g 1.5 MB

        $fileObj = new File;
        $fileObj->filename = $filename;
        $fileObj->url = $url;
        $fileObj->secure_url = $secureUrl;
        $fileObj->public_id = $publicId;
        if($fileType=='image') $fileObj->width = $width;
        if($fileType=='image') $fileObj->height = $height;
        $fileObj->size = $size;
        $fileObj->formatted_size = $rSize;
        if($user_id) $fileObj->user_id = $user_id;
        if($user_type) $fileObj->user_type = $user_type;
        $fileObj->file_type = $fileType;
        $fileObj->mime_type = (is_string($file)) ? mime_content_type($file) : $file->getMimeType();
        $fileObj->original_filename = (is_string($file)) ? pathinfo($file, PATHINFO_BASENAME) : $file->getClientOriginalName();
        $fileObj->extension = (is_string($file)) ? pathinfo($file, PATHINFO_EXTENSION) : $file->getClientOriginalExtension();
        $fileObj->save();
        return $fileObj;
    }

    public function update($file_id, $file, $fileType, $user_id, $purpose, $folder=null)
    {
        $uploadedFile = ($fileType=='image' || $fileType=='video') ? $this->uploadMedia($file, $fileType) : $this->uploadDoc($file, $folder);
        if($uploadedFile) {
            $status = $this->getStatus($uploadedFile);
            //dd($uploadedFile->offsetGet('secure_url'));
            if($status['code']==200) {
                $url = $uploadedFile->offsetGet('url');
                $secureUrl = $uploadedFile->offsetGet('secure_url');
                $publicId = $uploadedFile->offsetGet('public_id');
                $filename = $this->getFilename($publicId);
                // $uploadDate = $uploadedFile->getTimeUploaded();
                if($fileType=='image') $width = $uploadedFile->offsetGet('width'); 
                if($fileType=='image') $height = $uploadedFile->offsetGet('height');
                $size = $uploadedFile->offsetGet('bytes'); // Get the size of the uploaded file in bytes
                $rSize = $this->convertSize($size); // Get the size of the uploaded file in bytes, megabytes, gigabytes or terabytes. E.g 1.5 MB

                $fileObj = $this->getFile($file_id);
                if($fileObj) {
                    $fileObj->filename = $filename;
                    $fileObj->url = $url;
                    $fileObj->secure_url = $secureUrl;
                    $fileObj->public_id = $publicId;
                    if($fileType=='image') $fileObj->width = $width;
                    if($fileType=='image') $fileObj->height = $height;
                    $fileObj->size = $size;
                    $fileObj->formatted_size = $rSize;
                    $fileObj->user_id = $user_id;
                    $fileObj->file_type = $fileType;
                    $fileObj->purpose = $purpose;
                    $fileObj->mime_type = $file->getMimeType();;
                    $fileObj->original_filename = $file->getClientOriginalName();
                    $fileObj->extension = $file->getClientOriginalExtension();
                    $fileObj->update();
                    return ['status'=>200, 'file'=>$fileObj];
                }
            }else{
                return ['status'=>$status['code'], 'message'=>$status['message'], 'file'=>$file->getClientOriginalName()];
            }
        }else{
            return ['status'=>402, 'message'=>'File upload failed', 'file'=>$file->getClientOriginalName()];
        }
        
    }

    public function updateFileObj($data, $file)
    {
        if(isset($data['belongsId'])) $file->belongs_id = $data['belongsId'];
        if(isset($data['belongsType'])) $file->belongs_type = $data['belongsType'];
        if(isset($data['purpose'])) $file->purpose = $data['purpose'];
        $file->update();
    }



    public function saveFiles($files, $fileType, $user_id, $meta, $user_type=null)
    {
        $successFiles = [];
        $errors = [];
        $failedFiles = [];
        $codes = [];
        $response = [];
        if(count($files) > 0) {
            foreach($files as $file) {
                //$ext = $file->getClientOriginalExtension();
                if(empty($fileType)) $fileType =  $file->getClientOriginalExtension();
                $res = $this->save($file, $fileType, $user_id, $meta, $user_type);
                if($res['status']==200) {
                    $successFiles[] = $res['file'];
                }else{
                    $errors[] = $res['message'];
                    $failedFiles[] = $res['file'];
                    $codes[] = $res['status'];
                }
            }
        }
        if(empty($errors)) { 
            $response = ['status'=>200, 'files'=>$successFiles];
        }else{
            if(!empty($successFiles)) {
                $response = ['status'=>201, 'message'=>$this->getErrors($errors, $failedFiles), 'files'=>$successFiles, 'failedFiles'=>$failedFiles];
            }else{
                $response = ['status'=>402, 'message'=>$this->getErrors($errors, $failedFiles), 'files'=>$failedFiles];
            }
        }
        return $response;
    }

    public function updateFiles($files, $fileType, $user_id, $meta)
    {
        $successFiles = [];
        $errors = [];
        $failedFiles = [];
        $codes = [];
        $response = [];
        if(count($files) > 0) {
            foreach($files as $file) {
                //$ext = $file->getClientOriginalExtension();
                if(empty($fileType)) $fileType =  $file->getClientOriginalExtension();
                $res = $this->update($file['file_id'], $file['file'], $fileType, $user_id, $meta);
                if($res['status']==200) {
                    $successFiles[] = $res['file'];
                }else{
                    $errors[] = $res['message'];
                    $failedFiles[] = $res['file'];
                    $codes[] = $res['status'];
                }
            }
        }
        if(empty($errors)) { 
            $response = ['status'=>200, 'files'=>$successFiles];
        }else{
            if(!empty($successFiles)) {
                $response = ['status'=>201, 'message'=>$this->getErrors($errors, $failedFiles), 'files'=>$successFiles, 'failedFiles'=>$failedFiles];
            }else{
                $response = ['status'=>402, 'message'=>$this->getErrors($errors, $failedFiles), 'files'=>$failedFiles];
            }
        }
        return $response;
    }

    

    private function getErrors($errors, $failedFiles)
    {
        $message = '';
        foreach($errors as $key=>$error) {
            $message .= $failedFiles[$key].': '.$error.', ';
        }
        return $message;
    }

    private function uploadMedia($file, $fileType, $folder=null)
    {
        $imageUploadFolder = ($folder==null) ? env("CLOUDINARY_IMAGES") : env("CLOUDINARY_IMAGES")."/".$folder;
        $videoUploadFolder = ($folder==null) ? env("CLOUDINARY_VIDEOS") : env("CLOUDINARY_VIDEOS")."/".$folder;
        if($file->getSize() < 10000000) {
            // dd($imageUploadFolder);
            $uploadedFile = ($fileType=='image') ? 
            Cloudinary::uploadApi()->upload($file->getRealPath(), [
                        "folder" => $imageUploadFolder,
                        "sign_url" => true,
                        "eager" => [
                            ["width"=> 2000, "height"=> 1000, "crop"=> "pad", "gravity"=> "auto", "crop"=> "crop", "sign_url"=> true ],
                            ["width"=> 0.9, "height"=> 0.8, "crop"=> "scale", "sign_url"=> true ]]
                    ])
                    :
                    cloudinary()->uploadApi()->upload($file->getRealPath(), ["folder" => $videoUploadFolder, "resource_type"=>"video"]);
        }else{
            $uploadedFile = ($fileType=='image') ?
                cloudinary()->uploadApi()->upload($file->getRealPath(), [
                    "folder" => $imageUploadFolder,
                    "sign_url" => true,
                    "eager" => [
                        ["width"=> 2000, "height"=> 1000, "crop"=> "pad", "gravity"=> "auto", "crop"=> "crop", "sign_url"=> true ],
                            ["width"=> 0.9, "height"=> 0.8, "crop"=> "scale", "sign_url"=> true ]]
                ])
                :
                cloudinary()->uploadApi()->upload($file->getRealPath(), ["folder" => $videoUploadFolder, "resource_type"=>"video", "chunk_size" => 6000000]);
        }
        // dd($uploadedFile);
        return $uploadedFile;
    }

    private function uploadDoc($file, $purpose, $folder=null)
    {
        $uploaded = in_array($purpose, [FilePurpose::CONTRACT->value, FilePurpose::LETTER_OF_HAPPINESS->value, FilePurpose::PAYMENT_RECEIPT->value, FilePurpose::MEMORANDUM_OF_AGREEMENT->value]);
        if(!$uploaded) {
            $filename = time().$file->getClientOriginalName();
            $upload = Storage::disk('local')->putFileAs('files', $file, $filename);
            $upload = storage_path('app/' . $upload);
        }else{
            $upload = $file;
        }
        // dd($upload);
        if($upload) {
            $uploadFolder = ($folder==null) ? env("CLOUDINARY_DOCS") : env("CLOUDINARY_DOCS")."/".$folder;
            
            $uploadedFile = cloudinary()->uploadApi()->upload(
                $upload,  
                [
                    // "public_id" => $file->getClientOriginalName(),
                    "folder" => $uploadFolder,
                    "resource_type" => "raw",
                    "transformation" => [
                        "flags" => 'attachment'
                    ],
                    // "format" => "pdf"
                ]
            );
            return $uploadedFile;
        }else{
            return false;
        }
    }

    private function uploadDocFile($file, $purpose, $folder=null)
    {
        // $uploaded = in_array($purpose, [FilePurpose::CONTRACT->value, FilePurpose::LETTER_OF_HAPPINESS->value, FilePurpose::PAYMENT_RECEIPT->value]);
        // if(!$uploaded) {
        //     $filename = time().$file->getClientOriginalName();
        //     $upload = Storage::disk('local')->putFileAs('files', $file, $filename);
        // }else{
        //     $upload = $file;
        // }
        // if($upload) {
            $uploadFolder = ($folder==null) ? env("CLOUDINARY_DOCS") : env("CLOUDINARY_DOCS")."/".$folder;
            
            $uploadedFile = cloudinary()->uploadApi()->upload(
                $file->getRealPath(),  
                [
                    // "public_id" => $file->getClientOriginalName(),
                    "folder" => $uploadFolder,
                    "resource_type" => "raw",
                    "transformation" => [
                        "flags" => 'attachment'
                    ],
                    // "format" => "pdf"
                ]
            );
            // unlink($upload);
            return $uploadedFile;
        // }else{
        //     return false;
        // }
    }

    public function deleteFiles($filesIds)
    {
        foreach($filesIds as $fileId) {
            $this->deleteFile($fileId);
        }
    }

    public function deleteFile($fileId)
    {
        $file = File::find($fileId);
        if($file) $file->delete();
    }

    private function getStatus($uploadedFile)
    {
        $statusString = $uploadedFile->headers['Status'][0];
        $status = explode(' ', $statusString);
        return ['code'=>$status[0], 'message'=>$status[1]];
    }

    private function getFilename($publicId)
    {
        $arr = explode('/', $publicId);
        $count = count($arr);
        return $arr[$count-1];
    }

    private function convertSize($size)
    {
        $formatted = '';
        $len = strlen($size);
        if($len < 4) $formatted = $size.'Bytes'; 
        if($len > 3 && $len < 7) $formatted = round((float)($size/1024), 1).'KB';
        if($len > 6 && $len < 11) $formatted = round((float)(($size/1024)/1024), 1).'MB';
        if($len > 10 && $len < 14) $formatted = round((float)((($size/1024)/1024)/1024), 1).'GB';
        //return (float)$formatted;
        return $formatted;
    }

}



?>