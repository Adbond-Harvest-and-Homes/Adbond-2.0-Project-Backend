<?php

namespace app\Services;

use app\Notifications\APIPasswordResetNotification;
use app\Exceptions\UserNotFoundException;

use app\Models\User;
use app\Models\Role;

/**
 * Role service class
 */
class RoleService
{

    public function roles()
    {
        $roles = cache()->remember('roles', 60 * 5, function () {
            return Role::where("id", "!=", Role::SuperAdmin()->id)->get();
        });
        return $roles;
    }

    public function getRolesCount()
    {
        return Role::count();
    }

    public function getRoles()
    {
        return Role::all();
    }

    public function getRole($id)
    {
        return Role::find($id);
    }

    public function getRoleByName($name)
    {
        return Role::role($name);
    }

}
