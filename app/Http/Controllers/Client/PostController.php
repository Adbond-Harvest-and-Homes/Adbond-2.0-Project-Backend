<?php

namespace app\Http\Controllers\Client;

use Illuminate\Http\Request;
use app\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use app\Http\Requests\React;

use app\Http\Resources\PostResource;

use app\Services\PostService;
use app\Services\FileService;
use app\Services\ReactionService;

use app\Models\Client;
use app\Models\Post;

use app\Enums\ProjectFilter;

use app\Utilities;

class PostController extends Controller
{
    private $postService;
    private $reactionService;

    public function __construct()
    {
        $this->postService = new PostService;
        $this->reactionService = new ReactionService;
    }

    public function posts(Request $request)
    {
        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if(!is_int((int) $page) || $page <= 0) $page = 1;
        if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE');
        $offset = $perPage * ($page-1);

        $filter["status"] = ProjectFilter::ACTIVE->value;
        if($request->query('text')) $filter["text"] = $request->query('text');
        if($request->query('type')) $filter["type"] = $request->query('type');
        if($request->query('projectTypeId')) $filter["projectTypeId"] = $request->query('projectTypeId');
        if($request->query('date')) $filter["date"] = $request->query('date');

        $posts = $this->postService->filter($filter, [], $offset, $perPage);
        $this->postService->count = true;
        $postsCount = $this->postService->filter($filter);

        return Utilities::paginatedOkay(PostResource::collection($posts), $page, $perPage, $postsCount);

    }

    public function post($slug)
    {
        $post = $this->postService->getBySlug($slug, ['comments']);
        if(!$post) return Utilities::error402("Post not found");

        $post = $this->postService->increaseViews($post);

        return Utilities::ok(new PostResource($post));
    }

    public function react(React $request)
    {
        try{
            $data = $request->validated();
            if(!isset($data['postId'])) return Utilities::error402("PostId is required");

            $data['entityType'] = Post::$type;
            $data['entityId'] = $data['postId'];
            $data['userType'] = Client::$userType;
            $data['userId'] = Auth::guard("client")->user()->id;
            $data['reaction'] = ($data['reaction'] == 'like') ? true : false;

            $reaction = $this->reactionService->userReaction($data['userId'], $data['userType'], $data['entityId'], $data['entityType']);

            $reaction = $this->reactionService->save($data, $reaction);

            return Utilities::okay("Successful");

        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }
}
