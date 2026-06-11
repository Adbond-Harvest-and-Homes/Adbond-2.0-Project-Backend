<?php

namespace app\Services;

use app\Exceptions\AppException;

use app\Models\Department;

class DepartmentService
{
    public function getDepartments()
    {
        return Department::all();
    }

    public function getDepartment(int $id)
    {
        return Department::find($id);
    }

    public function save(array $data)
    {
        return Department::create($data);
    }

    public function update(int $id, array $data)
    {
        $department = $this->getDepartment($id);
        if (!$department) throw new AppException(402, "Department not found");

        $department->update($data);
        return $department;
    }

    public function delete(int $id)
    {
        $department = $this->getDepartment($id);
        if (!$department) throw new AppException(402, "Department not found");

        $department->delete();
    }
}
