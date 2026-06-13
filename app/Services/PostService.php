<?php

namespace app\Services;

use Maatwebsite\Excel\Facades\Excel;
use PDF;

use app\Models\Post;

use app\Enums\PostType;
use app\Enums\ProjectFilter;

use app\Helpers;

class PostService
{
    public $count = false;

    public function save($data)
    {
            $post = new Post;
            $post->topic = $data['topic'];
            $post->slug = Helpers::createSlug($data['topic']);
            $post->preview = Helpers::getTextPreview($data['content'], 30);
            $post->post_type = $data['type'];
            $post->user_id = $data['userId'];
            $post->file_id = $data['fileId'];
            $post->project_type_id = $data['projectTypeId'];
            $post->content = $data['content'];
            
            $post->save();
            return $post;
    }

    public function update($data, $post)
    {
        if(isset($data['topic'])) {
            $post->topic = $data['topic'];
            $post->slug = Helpers::createSlug($data['topic']);
        }
        if(isset( $data['type'])) $post->post_type = $data['type'];
        if(isset( $data['fileId'])) $post->file_id = $data['fileId'];
        if(isset( $data['projectTypeId'])) $post->project_type_id = $data['projectTypeId'];
        if(isset( $data['content'])) $post->content = $data['content'];

        $post->update();

        return $post;
    }

    public function increaseViews($post)
    {
        $post->views += 1;
        $post->update();

        return $post;
    }

    public function activate($post)
    {
        $post->active = true;
        $post->update();
        return $post;
    }

    public function deactivate($post)
    {
        $post->active = false;
        $post->update();
        return $post;
    }

    public function delete($post)
    {
        $post->delete();
    }

    public function posts($with=[], $offset=0, $perPage=null)
    {
        $query = Post::with($with);
        if($this->count) return $query->count();

        if($perPage==null) $perPage=env('PAGINATION_PER_PAGE');
        return $query->offset($offset)->limit($perPage)->orderBy("created_at", "DESC")->get();
    }

    public function post($id, $with=[])
    {
        return Post::with($with)->where("id", $id)->first();
    }

    public function getBySlug($slug, $with=[])
    {
        return Post::with($with)->where("slug", $slug)->first();
    }

    public function getByTopic($topic, $with=[])
    {
        $query = Post::with($with)->where("topic", $topic);

        return $query->first();
    }

    public function getByprojectType($projectTypeId, $with=[])
    {
        return Post::with($with)->where("project_type_id", $projectTypeId)->get();
    }

    public function filter($filter, $with=[], $offset=0, $perPage=null)
    {
        $query = Post::with($with);
        if(isset($filter['text'])) $query->where("topic", "LIKE", "%".$filter['text']."%");
        if(isset($filter['type'])) $query->where("post_type", "LIKE", "%".$filter['type']."%");
        if(isset($filter['projectTypeId'])) $query->where("project_type_id", $filter['projectTypeId']);
        if(isset($filter['date'])) $query = $query->whereDate("created_at", $filter['date']);
        if(isset($filter['status'])) $query = ($filter['status'] == ProjectFilter::ACTIVE->value) ? $query->where("active", true) : $query->where("active", false);
        if($this->count) return $query->count();
        return $query->orderBy("created_at", "DESC")->offset($offset)->limit($perPage)->get();
    }
}