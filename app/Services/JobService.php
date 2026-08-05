<?php

namespace app\Services;

use app\Exceptions\AppException;

use app\Models\JobBenefit;
use app\Models\JobRequirement;
use app\Models\JobResponsibility;

use app\Helpers;

class JobService
{
    // ==========================================
    // Job Benefit CRUD
    // ==========================================

    public function getBenefits()
    {
        return JobBenefit::orderBy("name", "asc")->get();
    }

    public function getBenefit(int $id)
    {
        return JobBenefit::find($id);
    }

    public function saveBenefit(array $data)
    {
        return JobBenefit::firstOrCreate($data);
    }

    public function updateBenefit(int $id, array $data)
    {
        $benefit = $this->getBenefit($id);
        if (!$benefit) throw new AppException(402, "Job benefit not found");

        $benefit->update($data);
        return $benefit;
    }

    public function deleteBenefit(int $id)
    {
        $benefit = $this->getBenefit($id);
        if (!$benefit) throw new AppException(402, "Job benefit not found");

        $benefit->delete();
    }

    // ==========================================
    // Job Requirement CRUD
    // ==========================================

    public function getRequirements()
    {
        return JobRequirement::orderBy("name", "asc")->get();
    }

    public function getRequirement(int $id)
    {
        return JobRequirement::find($id);
    }

    public function saveRequirement(array $data)
    {
        return JobRequirement::firstOrCreate($data);
    }

    public function updateRequirement(int $id, array $data)
    {
        $requirement = $this->getRequirement($id);
        if (!$requirement) throw new AppException(402, "Job requirement not found");

        $requirement->update($data);
        return $requirement;
    }

    public function deleteRequirement(int $id)
    {
        $requirement = $this->getRequirement($id);
        if (!$requirement) throw new AppException(402, "Job requirement not found");

        $requirement->delete();
    }

    // ==========================================
    // Job Responsibility CRUD
    // ==========================================

    public function getResponsibilities()
    {
        return JobResponsibility::orderBy("name", "asc")->get();
    }

    public function getResponsibility(int $id)
    {
        return JobResponsibility::find($id);
    }

    public function saveResponsibility(array $data)
    {
        return JobResponsibility::firstOrCreate($data);
    }

    public function updateResponsibility(int $id, array $data)
    {
        $responsibility = $this->getResponsibility($id);
        if (!$responsibility) throw new AppException(402, "Job responsibility not found");

        $responsibility->update($data);
        return $responsibility;
    }

    public function deleteResponsibility(int $id)
    {
        $responsibility = $this->getResponsibility($id);
        if (!$responsibility) throw new AppException(402, "Job responsibility not found");

        $responsibility->delete();
    }
}
