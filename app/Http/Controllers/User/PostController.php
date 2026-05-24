<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\CreatePost;
use app\Http\Requests\User\UpdatePost;
use app\Http\Requests\User\TogglePostActivate;
use app\Http\Requests\React;

use app\Http\Resources\PostResource;

use app\Services\PostService;
use app\Services\FileService;
use app\Services\ReactionService;

use app\Models\User;
use app\Models\Post;

use app\Utilities;

use app\Enums\PostType;
use app\Enums\FilePurpose;
use app\Enums\ProjectFilter;

class PostController extends Controller
{
    private $postService;
    private $fileService;
    private $reactionService;

    public function __construct()
    {
        $this->postService = new PostService;
        $this->fileService = new FileService;
        $this->reactionService = new ReactionService;
    }

    public function save(CreatePost $request)
    {
        try{
            DB::beginTransaction();
            $data = $request->validated();
            $res = $this->saveFile($request->file('file'));
            if($res['status'] != 200) return Utilities::error402('Sorry File could not be uploaded '.$res['message']);

            $data['fileId'] = $res['file']->id;
            $data['userId'] = Auth::user()->id;
            $post = $this->postService->save($data);

            $fileMeta = ["belongsId"=>$post->id, "belongsType"=>"app\Models\Post"];
            $this->fileService->updateFileObj($fileMeta, $res['file']);

            DB::commit();
            // dd($post);
            return Utilities::ok(new PostResource($post));
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function update(UpdatePost $request, $postId)
    {
        try{
            $post = $this->postService->post($postId);
            if(!$post) return Utilities::error402("Post not found");

            DB::beginTransaction();
            $data = $request->validated();
            $oldFileId = null; 
            if($request->hasFile('file')) {
                $res = $this->saveFile($request->file('file'), $post->id);
                if($res['status'] != 200) return Utilities::error402('Sorry File could not be uploaded '.$res['message']);
                $data['fileId'] = $res['file']->id;
                $oldFileId = $post->file_id;
            }
        
            $post = $this->postService->update($data, $post);

            if($oldFileId) $this->fileService->deleteFile($oldFileId);
            DB::commit();
            
            return Utilities::ok(new PostResource($post));
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function toggleActivate(TogglePostActivate $request)
    {
        $post = $this->postService->post($request->validated("postId"));
        if(!$post) return Utilities::error402("Post not found");

        $post = ($post->active==0) ? $this->postService->activate($post) : $this->postService->deactivate($post);

        return Utilities::ok(new PostResource($post));
    }

    public function posts(Request $request)
    {
        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if(!is_int((int) $page) || $page <= 0) $page = 1;
        if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE');
        $offset = $perPage * ($page-1);

        $filter = [];
        if($request->query('text')) $filter["text"] = $request->query('text');
        if($request->query('type')) $filter["type"] = $request->query('type');
        if($request->query('projectTypeId')) $filter["projectTypeId"] = $request->query('projectTypeId');
        if($request->query('date')) $filter["date"] = $request->query('date');
        if($request->query('status')) {
            $validStatus = ["active" => ProjectFilter::ACTIVE->value, "inactive" => ProjectFilter::INACTIVE->value];
            if(!in_array($request->query('status'), $validStatus)) return Utilities::error402("Valid Status are: ".$validStatus['active']." and ".$validStatus['inactive']);
            $filter["status"] = $request->query('status');
        }

        $posts = $this->postService->filter($filter, [], $offset, $perPage);
        $this->postService->count = true;
        $postsCount = $this->postService->filter($filter);

        return Utilities::paginatedOkay(PostResource::collection($posts), $page, $perPage, $postsCount);

    }

    public function post($postId)
    {
        $post = $this->postService->post($postId, ['comments']);
        if(!$post) return Utilities::error402("Post not found");

        $post = $this->postService->increaseViews($post);

        return Utilities::ok(new PostResource($post));
    }

    public function delete($postId)
    {
        if (!is_numeric($postId) || !ctype_digit($postId)) return Utilities::error402("Invalid parameter postID");
        $post = $this->postService->post($postId, ['comments']);
        if(!$post) return Utilities::error402("Post not found");

        $this->postService->delete($post);

        return Utilities::okay("Post Deleted");
    }


    private function saveFile($file, $postId=null)
    {
        $purpose = FilePurpose::POST_MEDIA->value;
        $mime = $file->getMimeType();
        $filename = $file->getClientOriginalName();
        $imageMimes = ['image/jpeg', 'image/jpg', 'image/gif', 'image/png'];
        $fileType = (in_array($mime, $imageMimes)) ? "image" : "video";
        if($postId) {
            $this->fileService->belongsId = $postId;
            $this->fileService->belongsType = "app\Models\Post";
        }
        $res = $this->fileService->save($file, $fileType, Auth::user()->id, $purpose, User::$userType, 'post-media'); 
        return $res;
        // if($res['status'] != 200) return Utilities::error402('Sorry Photo could not be uploaded '.$res['message']);

        // $data['photoId'] = $res['file']->id;
        // $fileMeta = ["belongsId"=>$projectType->id, "belongsType"=>"app\Models\ProjectType"];
        // $this->fileService->updateFileObj($fileMeta, $res['file']);
    }

    public function react(React $request)
    {
        try{
            $data = $request->validated();
            if(!isset($data['postId'])) return Utilities::error402("PostId is required");

            $data['entityType'] = Post::$type;
            $data['entityId'] = $data['postId'];
            $data['userType'] = User::$userType;
            $data['userId'] = Auth::user()->id;
            $data['reaction'] = ($data['reaction'] == 'like') ? true : false;

            $reaction = $this->reactionService->userReaction($data['userId'], $data['userType'], $data['entityId'], $data['entityType']);

            $reaction = $this->reactionService->save($data, $reaction);

            return Utilities::okay("Successful");

        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }
}
