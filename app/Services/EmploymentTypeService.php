<?php

namespace app\Services;

use app\Exceptions\AppException;

use app\Models\EmploymentType;

class EmploymentTypeService
{
    public function getTypes()
    {
        return EmploymentType::all();
    }

    public function getEmploymentType(int $id)
    {
        return EmploymentType::find($id);
    }

    public function save(array $data)
    {
        return EmploymentType::firstOrCreate($data);
    }

    public function update(int $id, array $data)
    {
        $employmentType = $this->getEmploymentType($id);
        if (!$employmentType) throw new AppException(402, "Employment type not found");

        $employmentType->update($data);
        return $employmentType;
    }

    public function delete(int $id)
    {
        $employmentType = $this->getEmploymentType($id);
        if (!$employmentType) throw new AppException(402, "Employment type not found");

        $employmentType->delete();
    }
}
