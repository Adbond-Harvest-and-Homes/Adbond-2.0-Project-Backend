<?php

namespace app\Services;

use app\Exceptions\AppException;

use app\Models\JobAdvert;

use app\Helpers;

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

    public function getAdvert(int $id, $with = [])
    {
        return JobAdvert::with($with)->where("id", $id)->first();
    }

    public function getBySlug(string $slug, $with = [])
    {
        return JobAdvert::with($with)->where("slug", $slug)->when($this->isOpen, fn($query) => $query->where("is_open", $this->isOpen))->first();
    }

    public function save(array $data)
    {
        $benefits = $data['benefits'] ?? null;
        $requirements = $data['requirements'] ?? null;
        $responsibilities = $data['responsibilities'] ?? null;

        unset($data['benefits'], $data['requirements'], $data['responsibilities']);

        $data['slug'] = Helpers::createSlug($data['title']);
        $jobAdvert = JobAdvert::firstOrCreate($data);

        if ($benefits !== null) {
            $jobAdvert->benefits()->sync($benefits);
        }
        if ($requirements !== null) {
            $jobAdvert->requirements()->sync($requirements);
        }
        if ($responsibilities !== null) {
            $jobAdvert->responsibilities()->sync($responsibilities);
        }

        return $jobAdvert;
    }

    public function update(int $id, array $data)
    {
        $jobAdvert = $this->getAdvert($id);
        if (!$jobAdvert) throw new AppException(404, "Job advert not found");

        $benefits = $data['benefits'] ?? null;
        $requirements = $data['requirements'] ?? null;
        $responsibilities = $data['responsibilities'] ?? null;

        unset($data['benefits'], $data['requirements'], $data['responsibilities']);

        $jobAdvert->update($data);

        if ($benefits !== null) {
            $jobAdvert->benefits()->sync($benefits);
        }
        if ($requirements !== null) {
            $jobAdvert->requirements()->sync($requirements);
        }
        if ($responsibilities !== null) {
            $jobAdvert->responsibilities()->sync($responsibilities);
        }

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
