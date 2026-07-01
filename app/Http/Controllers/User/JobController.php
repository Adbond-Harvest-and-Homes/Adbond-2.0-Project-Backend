<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Http\Controllers\Controller;
use app\Exceptions\AppException;

use app\Services\JobService;

use app\Http\Requests\User\CreateJobBenefit;
use app\Http\Requests\User\UpdateJobBenefit;
use app\Http\Requests\User\CreateJobRequirement;
use app\Http\Requests\User\UpdateJobRequirement;
use app\Http\Requests\User\CreateJobResponsibility;
use app\Http\Requests\User\UpdateJobResponsibility;

use app\Http\Resources\JobBenefitResource;
use app\Http\Resources\JobRequirementResource;
use app\Http\Resources\JobResponsibilityResource;

use app\Utilities;

class JobController extends Controller
{
    public function __construct(protected JobService $service) {}

    // ==========================================
    // Job Benefit Controller Methods
    // ==========================================

    public function getBenefits()
    {
        $benefits = $this->service->getBenefits();
        return Utilities::ok(JobBenefitResource::collection($benefits));
    }

    public function getBenefit(int $id)
    {
        $benefit = $this->service->getBenefit($id);
        if (!$benefit) return Utilities::error402("Job benefit not found");

        return Utilities::ok(new JobBenefitResource($benefit));
    }

    public function saveBenefit(CreateJobBenefit $request)
    {
        $data = $request->validated();
        $benefit = $this->service->saveBenefit($data);
        return Utilities::ok(new JobBenefitResource($benefit));
    }

    public function updateBenefit(UpdateJobBenefit $request, int $id)
    {
        try {
            $data = $request->validated();
            $benefit = $this->service->updateBenefit($id, $data);
            return Utilities::ok(new JobBenefitResource($benefit));
        } catch (AppException $e) {
            return Utilities::error402($e->getMessage());
        }
    }

    public function deleteBenefit(int $id)
    {
        try {
            $this->service->deleteBenefit($id);
            return Utilities::okay("Job benefit deleted successfully");
        } catch (AppException $e) {
            return Utilities::error402($e->getMessage());
        }
    }

    // ==========================================
    // Job Requirement Controller Methods
    // ==========================================

    public function getRequirements()
    {
        $requirements = $this->service->getRequirements();
        return Utilities::ok(JobRequirementResource::collection($requirements));
    }

    public function getRequirement(int $id)
    {
        $requirement = $this->service->getRequirement($id);
        if (!$requirement) return Utilities::error402("Job requirement not found");

        return Utilities::ok(new JobRequirementResource($requirement));
    }

    public function saveRequirement(CreateJobRequirement $request)
    {
        $data = $request->validated();
        $requirement = $this->service->saveRequirement($data);
        return Utilities::ok(new JobRequirementResource($requirement));
    }

    public function updateRequirement(UpdateJobRequirement $request, int $id)
    {
        try {
            $data = $request->validated();
            $requirement = $this->service->updateRequirement($id, $data);
            return Utilities::ok(new JobRequirementResource($requirement));
        } catch (AppException $e) {
            return Utilities::error402($e->getMessage());
        }
    }

    public function deleteRequirement(int $id)
    {
        try {
            $this->service->deleteRequirement($id);
            return Utilities::okay("Job requirement deleted successfully");
        } catch (AppException $e) {
            return Utilities::error402($e->getMessage());
        }
    }

    // ==========================================
    // Job Responsibility Controller Methods
    // ==========================================

    public function getResponsibilities()
    {
        $responsibilities = $this->service->getResponsibilities();
        return Utilities::ok(JobResponsibilityResource::collection($responsibilities));
    }

    public function getResponsibility(int $id)
    {
        $responsibility = $this->service->getResponsibility($id);
        if (!$responsibility) return Utilities::error402("Job responsibility not found");

        return Utilities::ok(new JobResponsibilityResource($responsibility));
    }

    public function saveResponsibility(CreateJobResponsibility $request)
    {
        $data = $request->validated();
        $responsibility = $this->service->saveResponsibility($data);
        return Utilities::ok(new JobResponsibilityResource($responsibility));
    }

    public function updateResponsibility(UpdateJobResponsibility $request, int $id)
    {
        try {
            $data = $request->validated();
            $responsibility = $this->service->updateResponsibility($id, $data);
            return Utilities::ok(new JobResponsibilityResource($responsibility));
        } catch (AppException $e) {
            return Utilities::error402($e->getMessage());
        }
    }

    public function deleteResponsibility(int $id)
    {
        try {
            $this->service->deleteResponsibility($id);
            return Utilities::okay("Job responsibility deleted successfully");
        } catch (AppException $e) {
            return Utilities::error402($e->getMessage());
        }
    }
}
