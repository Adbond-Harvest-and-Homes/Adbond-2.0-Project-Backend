<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use Carbon\Carbon;

use app\Http\Requests\User\CreateSiteTourSchedule;
use app\Http\Requests\User\UpdateSiteTourSchedule;

use app\Http\Resources\SiteTourScheduleResource;
use app\Http\Resources\SiteTourBookedScheduleResource;

use app\Services\SiteTourService;
use app\Services\PackageService;

use app\Utilities;

class SiteTourController extends Controller
{
    private $userActivityLogService;

    private $siteTourService;
    private $packageService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->siteTourService = new SiteTourService;
        $this->packageService = new PackageService;
    }

    public function createSchedule(CreateSiteTourSchedule $request)
    {
        try{
            $data = $request->validated();
            $package = $this->packageService->package($data['packageId']);
            if(!$package) return Utilities::error402("package not found");

            $data['projectId'] = $package->project_id;
            $data['projectTypeId'] = $package?->project?->project_type_id;
            if(!$data['projectTypeId']) return Utilities::error402("Project Type Not found");

            if(isset($data['availableDate'])) {
                if($this->siteTourService->getScheduleByDate($package->id, $data['availableDate'], $data['availableTime'])) {
                    return Utilities::error402("This Schedule already exists");
                }
            }

            if(isset($data['recurrent'])) {
                if($this->siteTourService->getRecurrent($package->id, $data['availableTime'], $data['recurrentDay'])) {
                    return Utilities::error402("This Schedule already exists");
                }
            }

            $siteTourSchedule = $this->siteTourService->save($data);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Created Site Tour Schedule");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new SiteTourScheduleResource($siteTourSchedule));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function updateSchedule(UpdateSiteTourSchedule $request, $id)
    {
        try{
            if (!is_numeric($id) || !ctype_digit($id)) return Utilities::error402("Invalid parameter ID");
            $data = $request->validated();

            $schedule = $this->siteTourService->schedule($id);
            if(!$schedule) return Utilities::error402("Schedule not found");

            if(isset($data['packageId'])) {
                $package = $this->packageService->package($data['packageId']);
                if(!$package) return Utilities::error402("package not found");

                $data['projectId'] = $package->project_id;
                $data['projectTypeId'] = $package?->project?->project_type_id;
                if(!$data['projectTypeId']) return Utilities::error402("Project Type Not found");
            }

            $siteTourSchedule = $this->siteTourService->update($data, $schedule);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Updated Site Tour Schedule");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new SiteTourScheduleResource($siteTourSchedule));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function deleteSchedule($id)
    {
        if (!is_numeric($id) || !ctype_digit($id)) return Utilities::error402("Invalid parameter ID");

        $schedule = $this->siteTourService->schedule($id);
        if(!$schedule) return Utilities::error402("Schedule not found");

        $this->siteTourService->delete($schedule);

        
            try {
                $this->userActivityLogService->log(Auth::user(), "Deleted Site Tour Schedule");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Site Tour Schedule Deleted Successfully");
    }

    public function schedules(Request $request)
    {
        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if(!is_int((int) $page) || $page <= 0) $page = 1;
        if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE');
        $offset = $perPage * ($page-1);

        $schedules = $this->siteTourService->schedules([], $offset, $perPage);

        return Utilities::ok(SiteTourScheduleResource::collection($schedules));
    }

    public function schedule($id)
    {
        if (!is_numeric($id) || !ctype_digit($id)) return Utilities::error402("Invalid parameter ID");
        $schedule = $this->siteTourService->schedule($id);
        if(!$schedule) return Utilities::error402("Schedule not found");

        return Utilities::ok(new SiteTourScheduleResource($schedule));
    }

    public function bookedSchedules()
    {
        $booked = $this->siteTourService->bookedSchedules(['bookings']);

        return Utilities::ok(SiteTourBookedScheduleResource::collection($booked));
    }
}
