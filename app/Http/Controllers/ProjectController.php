<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;

use app\Http\Resources\ProjectResource;
use app\Http\Resources\Min\ProjectTypeResource;

use app\Services\ProjectService;
use app\Services\ProjectTypeService;

use app\Utilities;

class ProjectController extends Controller
{
    private $projectService;
    private $projectTypeService;

    public function __construct()
    {
        $this->projectService = new ProjectService;
        $this->projectTypeService = new ProjectTypeService;
    }

    public function getTypes()
    {
        $projectTypes = $this->projectTypeService->projectTypes();
        $this->projectService->count = true;
        $projectsCount = $this->projectService->projects();
        return Utilities::ok([
            "allProjectsCount" => $projectsCount,
            "projectTypes" => ProjectTypeResource::collection($projectTypes)
        ]);
    }
    

    public function getProjects(Request $request, $projectTypeId=null)
    {
        if ($projectTypeId && (!is_numeric($projectTypeId) || !ctype_digit($projectTypeId))) return Utilities::error402("Invalid parameter projectTypeID");
        $this->projectService->typeId = $projectTypeId;

        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if(!is_int((int) $page) || $page <= 0) $page = 1;
        if(!is_int((int) $perPage) || $perPage==null) $perPage = 10;
        $offset = $perPage * ($page-1);

        $projects = $this->projectService->activeProjects(['projectType', 'packages.media'], $offset, $perPage);
        $projects->each(function ($project) {
            // $project->load('packages');
            $project->setRelation('packages.media', $project->packages(10)->where("active", 1)->get());
        });
        $this->projectService->count = true;
        $projectsCount = $this->projectService->activeProjects();

        return Utilities::paginatedOkay(ProjectResource::collection($projects), $page, $perPage, $projectsCount);
    }

    public function search(Request $request, $projectTypeId=null)
    {
        try{
            if ($projectTypeId && (!is_numeric($projectTypeId) || !ctype_digit($projectTypeId))) return Utilities::error402("Invalid parameter projectTypeID");
            $page = ($request->query('page')) ?? 1;
            $perPage = ($request->query('perPage'));
            if(!is_int((int) $page) || $page <= 0) $page = 1;
            if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE');
            $offset = $perPage * ($page-1);

            $text = ($request->query('text')) ?? null;
            $projects = $this->projectService->search($text, $projectTypeId, $offset, $perPage);
            $this->projectService->count = true;
            $projectsCount = $this->projectService->search($text, $projectTypeId);

            return Utilities::paginatedOkay(ProjectResource::collection($projects), $page, $perPage, $projectsCount);
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function getProject($projectId)
    {
        try{
            if (!is_numeric($projectId) || !ctype_digit($projectId)) return Utilities::error402("Invalid parameter projectID");
            $project = $this->projectService->project($projectId, ['packages.state', 'packages.photos']);
            if(!$project) return Utilities::error402("Project was not found");

            return Utilities::ok(new ProjectResource($project));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }
}
