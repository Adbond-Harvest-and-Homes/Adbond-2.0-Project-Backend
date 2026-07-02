<?php

namespace app\Http\Controllers\User;

use app\Http\Controllers\Controller;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use app\Http\Requests\User\SaveProject;
use app\Http\Requests\User\UpdateProject;
use app\Http\Requests\User\AddProjectLocation;
use app\Http\Requests\User\ToggleProjectActivate;
use app\Http\Requests\User\FilterProject;
use app\Http\Requests\User\AddProductPromo;
use app\Http\Requests\User\RemoveProductPromo;

use app\Http\Resources\ProjectResource;
use app\Http\Resources\Min\ProjectTypeResource;

use app\Services\ProjectService;
use app\Services\FileService;
use app\Services\ProjectTypeService;
use app\Services\MetricService;
use app\Services\PromoService;

use app\Enums\ProjectFilter;
use app\Enums\MetricType;

use app\Models\Project;

use app\Utilities;

class ProjectController extends Controller
{
    private $userActivityLogService;

    private $projectService;
    private $projectTypeService;
    private $fileService;
    private $metricService;
    private $promoService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->projectService = new ProjectService;
        $this->projectTypeService = new ProjectTypeService;
        $this->fileService = new FileService;
        $this->metricService = new MetricService;
        $this->promoService = new PromoService;
    }

    public function save(SaveProject $request)
    {
        try{
            DB::beginTransaction();
            $data = $request->validated();
            $project = $this->projectService->save($data);
            // $this->projectService->addLocation($project, $data);

            //Add the Project metric
            $this->metricService->addProjectMetric(MetricType::BOTH->value);

            DB::commit();
            
            try {
                $this->userActivityLogService->log(Auth::user(), "Saved Project");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Project created Successfully", new ProjectResource($project));
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function update(UpdateProject $request)
    {
        try{
            $data = $request->validated();
            $project = $this->projectService->project($data['id']);
            if(!$project) return Utilities::error402("Project not found");
            if(isset($data['name'])) {
                if($this->projectService->projectNameExists($data['name'], $project)) return Utilities::error402("Project name already exists for this project Type");
            }
            $project = $this->projectService->update($data, $project);
            
            try {
                $this->userActivityLogService->log(Auth::user(), "Updated Project");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Project Updated Successfully", new ProjectResource($project));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function addPromo(AddProductPromo $request)
    {
        try{
            $data = $request->validated();

            $project = $this->projectService->project($data['productId']);
            if(!$project) return Utilities::error402("Project not found");

            $product['type'] = Project::$type;
            $product['id'] = $data['productId'];
            $product['promoId'] = $data['promoId'];

            $promoProduct = $this->promoService->savePromoProduct($product);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Added Promo to Project");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Promo has been Added to the Project Successfully");

        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function removePromo(RemoveProductPromo $request)
    {
        try{
            $data = $request->validated();
            $project = $this->projectService->project($data['productId']);
            if(!$project) return Utilities::error402("Project not found");

            $promoProduct = $this->promoService->getPromoProductByDetail($data["promoId"], Project::$type, $data['productId']);
            if(!$promoProduct) return Utilities::error402("Promo Product not found");

            $this->promoService->removePromoProduct($promoProduct);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Removed Promo from Project");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Promo has been Removed to the Project Successfully");

        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    // public function addLocation(AddProjectLocation $request)
    // {
    //     try{
    //         $data = $request->validated();
    //         $project = $this->projectService->project($data['projectId']);
    //         if(!$project) return Utilities::error402("Project not found");
    //         $projectLocation = $this->projectService->projectLocationByState($project->id, $data['stateId']);
    //         if($projectLocation) return Utilities::error402("This Project already exists in this State");
    //         $this->projectService->addLocation($project, $data);

    //         return Utilities::okay("Location has been added to Project Successfully");
    //     }catch(\Exception $e){
    //         return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
    //     }
    // }

    public function types()
    {
        $projectTypes = $this->projectTypeService->projectTypes();
        return Utilities::ok(ProjectTypeResource::collection($projectTypes));
    }

    public function summary($projectTypeId)
    {
        if (!is_numeric($projectTypeId) || !ctype_digit($projectTypeId)) return Utilities::error402("Invalid parameter projectTypeID");
        $projectType = $this->projectTypeService->projectType($projectTypeId);

        // $this->projectService->count = true;
        // $this->projectService->typeId = $projectTypeId;

        $activeCount = $projectType->activeProjects->count(); //$this->projectService->filter(['status'=>'active']);
        $inactiveCount = $projectType->inactiveProjects->count(); //$this->projectService->filter(['status'=>'active']);
        $projectsCount = $projectType->projects->count(); //$this->projectService->projects($projectTypeId);
        $packagesCount = $projectType->packages->count();

        return [
            "projectsCount" => $projectsCount,
            "activeCount" => $activeCount,
            "inactiveCount" => $inactiveCount,
            "packagesCount" => $packagesCount
        ];
        
    }

    public function allProjects()
    {
        $this->projectService->status = ProjectFilter::ACTIVE->value;
        $this->projectService->all = true;
        $projects = $this->projectService->projects();

        return Utilities::ok(ProjectResource::collection($projects));
    }

    public function projects(Request $request, $projectTypeId)
    {
        if (!is_numeric($projectTypeId) || !ctype_digit($projectTypeId)) return Utilities::error402("Invalid parameter projectTypeID");

        $summaryData = $this->summary($projectTypeId);

        $this->projectService->typeId = $projectTypeId;

        if($request->has('all')) $this->projectService->all - true;

        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if(!is_int((int) $page) || $page <= 0) $page = 1;
        if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE');
        $offset = $perPage * ($page-1);

        $filter = [];
        if($request->query('text')) $filter["text"] = $request->query('text');
        if($request->query('date')) $filter["date"] = $request->query('date');
        if($request->query('status')) {
            $validStatus = ["active" => ProjectFilter::ACTIVE->value, "inactive" => ProjectFilter::INACTIVE->value];
            if(!in_array($request->query('status'), $validStatus)) return Utilities::error402("Valid Status are: ".$validStatus['active']." and ".$validStatus['inactive']);
            $filter["status"] = $request->query('status');
        }

        $with = ($request->has('all')) ? [] : ["projectType", "packages"];
    
        $projects = $this->projectService->filter($filter, $with, $offset, $perPage);

        // $projects = $this->projectService->projects(["projectType"], $offset, $perPage);
        $this->projectService->count = true;
        $projectsCount = $this->projectService->filter($filter);

        $meta = ($request->has('all')) ? [] : [
            "page" => $page,
            "perPage" => $perPage,
            "pages" => ceil($projectsCount/$perPage),
            "total" => $projectsCount
        ];

        return Utilities::ok([
            "summary" => $summaryData,
            "projects" => ProjectResource::collection($projects),
            "meta" => $meta
        ]);
        // return Utilities::paginatedOkay(ProjectResource::collection($projects), $page, $perPage, $projectsCount);
    }

    public function project($id)
    {
        $project = $this->projectService->project($id, ['packages']);
        if(!$project) return Utilities::error402("Project not found");

        $project->load("projectType");

        return Utilities::ok(new ProjectResource($project));
    }

    public function activate(ToggleProjectActivate $request)
    {
        try{
            $id = $request->validated("id");
            $project = $this->projectService->project($id);
            if(!$project) return Utilities::error402("Project not found");

            if($project->active) return Utilities::error402("Project is already active");

            DB::beginTransaction();

            $project = $this->projectService->activate($project);

            //Add new Project Metric;
            $this->metricService->addProjectMetric(MetricType::ACTIVE->value);

            DB::commit();

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Activated Project");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Project Activated Successfully", new ProjectResource($project));
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function deactivate(ToggleProjectActivate $request)
    {
        try{
            $id = $request->validated("id");
            $project = $this->projectService->project($id);
            if(!$project) return Utilities::error402("Project not found");

            if(!$project->active) return Utilities::error402("Project is already inactive");

            DB::beginTransaction();
            
            $project = $this->projectService->deactivate($project);

            //Add new Project Metric;
            $this->metricService->addProjectMetric(MetricType::ACTIVE->value, true, false);

            DB::commit();

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Deactivated Project");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Project Deactivated Successfully", new ProjectResource($project));
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function delete(ToggleProjectActivate $request)
    {
        try{
            $id = $request->validated("id");
            $project = $this->projectService->project($id);
            if(!$project) return Utilities::error402("Project not found");

            if($project->active) return Utilities::error402("Cannot delete an active Project");
            if(!$project->canDelete()) return Utilities::error402("Cannot delete a Project with Packages");

            DB::beginTransaction();

            $this->projectService->delete($project);

            // Add new Project Metric;
            $this->metricService->addProjectMetric(MetricType::TOTAL->value, false, true);

            DB::commit();

            try {
                $this->userActivityLogService->log(Auth::user(), "Deleted Project");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Project Deleted Successfully");
        } catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function filter(FilterProject $request, $projectTypeId)
    {
        try{
            if (!is_numeric($projectTypeId) || !ctype_digit($projectTypeId)) return Utilities::error402("Invalid parameter projectTypeID");
            $page = ($request->query('page')) ?? 1;
            $perPage = ($request->query('perPage'));
            if(!is_int((int) $page) || $page <= 0) $page = 1;
            if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE');
            $offset = $perPage * ($page-1);

            $filter = $request->validated();
            $this->projectService->typeId = $projectTypeId;
            $projects = $this->projectService->filter($filter, [], $offset, $perPage);
            $this->projectService->count = true;
            $projectsCount = $this->projectService->filter($filter);

            return Utilities::paginatedOkay(ProjectResource::collection($projects), $page, $perPage, $projectsCount);
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function search(Request $request, $projectTypeId)
    {
        try{
            if (!is_numeric($projectTypeId) || !ctype_digit($projectTypeId)) return Utilities::error402("Invalid parameter projectTypeID");
            $this->projectService->typeId = $projectTypeId;
            $page = ($request->query('page')) ?? 1;
            $perPage = ($request->query('perPage'));
            if(!is_int((int) $page) || $page <= 0) $page = 1;
            if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE');
            $offset = $perPage * ($page-1);

            $text = ($request->query('text')) ?? null;
            $projects = $this->projectService->search($text, $offset, $perPage);
            $this->projectService->count = true;
            $projectsCount = $this->projectService->search($text);

            return Utilities::paginatedOkay(ProjectResource::collection($projects), $page, $perPage, $projectsCount);
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    
    public function export(Request $request, $projectTypeId)
    {
        try {
            if (!is_numeric($projectTypeId) || !ctype_digit($projectTypeId)) return Utilities::error402("Invalid parameter projectTypeID");
            // Get projects with necessary relations
            $this->projectService->typeId = $projectTypeId;
            $projects = $this->projectService->projects();

            $type = ($request->query('type')) ?? null;
            if(!$type) return Utilities::error402("Type is required");

            // Custom heading configuration (optional)
            $headingConfig = [
                'headings' => [
                    'Project ID',
                    'Project Name',
                    'Project Type',
                    'Description',
                    'Current Status',
                    'Total Packages',
                    'Creation Date'
                ],
                'columns' => [
                    'identifier',
                    'name',
                    'project_type',
                    'description',
                    'status',
                    'package_count',
                    'created_at'
                ]
            ];

            // Handle different export types
            switch ($type) {
                case 'excel':
                    return $this->projectService->exportToExcel($projects, $headingConfig);
                case 'pdf':
                    
            try {
                $this->userActivityLogService->log(Auth::user(), "Exported Projects list");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return $this->projectService->exportToPDF($projects, $headingConfig);
                default:
                    return Utilities::error402("Invalid export type");
            }
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred during export');
        }
    }

    
}
