<?php

namespace app\Services;

use app\Exceptions\AppException;

use app\Models\JobAdvert;

class JobAdvertService
{
    public $isOpen = true;
    public $departmentId = null;
    public $employmentTypeId = null;

    public function getAdverts()
    {
        return JobAdvert::when($this->isOpen, fn($query) => $query->where("is_open", $this->isOpen))
            ->when($this->employmentTypeId, fn($query) => $query->where("employment_type_id", $this->employmentTypeId))
            ->when($this->departmentId, fn($query) => $query->where("department_id", $this->departmentId))
            ->orderBy("opened_on", "desc")->get();;
    }

    public function getAdvert(int $id)
    {
        return JobAdvert::find($id);
    }

    public function save(array $data)
    {
        return JobAdvert::create($data);
    }

    public function update(int $id, array $data)
    {
        $jobAdvert = $this->getAdvert($id);
        if (!$jobAdvert) throw new AppException(404, "Job advert not found");

        $jobAdvert->update($data);
        return $jobAdvert;
    }

    public function toggleOpen(int $id)
    {
        $jobAdvert = $this->getAdvert($id);
        if (!$jobAdvert) throw new AppException(404, "Job advert not found");

        $updateData = ["is_open" => !$jobAdvert->is_open];
        if (!$jobAdvert->is_open) $updateData['opened_on'] = now();

        $jobAdvert->update($updateData);
        return $jobAdvert;
    }

    public function delete(int $id)
    {
        $jobAdvert = $this->getAdvert($id);
        if (!$jobAdvert) throw new AppException(404, "Job advert not found");

        $jobAdvert->delete();
    }
}
