<?php namespace Sako\Passport;

use Config, DB, Route;

class Passport {

    /**
     * Permission table name
     *
     * @var string
     */
    protected $permissionTable = 'passport_permissions';

    /**
     * Role table name
     *
     * @var string
     */
    protected $roleTable = 'passport_roles';

    /**
     * User role table name
     *
     * @var string
     */
    protected $userRoleTable = 'passport_user_roles';

    /**
     * List all permissions
     *
     * @return array
     */
    public function getPermissions()
    {
        return DB::table($this->permissionTable)
        ->orderBy('code', 'ASC')
        ->get();
    }

    /**
     * List all roles
     *
     * @return array
     */
    public function getRoles()
    {
        return DB::table($this->roleTable)
        ->orderBy('title', 'ASC')
        ->get();
    }

    /**
     * Get role
     *
     * @param  integer $roleId
     * @param  boolean $allPermission
     * @return object|null
     */
    public function getRole($roleId, $allPermission = false)
    {
        // all permissions
        $permissions = $this->getPermissions();

        // role
        $role = DB::table($this->roleTable)
        ->where('id', $roleId)
        ->first();

        if ($role)
        {
            // role permissions
            $rolePermissionIds = json_decode($role->permissions);

            // design role permissions
            $rolePermissions = [];
            array_walk($permissions, function($permission) use (&$rolePermissions, $rolePermissionIds, $allPermission)
            {
                $rolePermission = (object)[
                    'id'       => $permission->id,
                    'code'     => $permission->code,
                    'selected' => array_search($permission->id, $rolePermissionIds) !== false
                ];

                if ($allPermission)
                {
                    array_push($rolePermissions, $rolePermission);
                }
                else
                {
                    if ( $rolePermission->selected ) array_push($rolePermissions, $rolePermission);
                }
            });

            // set role permissions value
            $role->permissions = $rolePermissions;
        }

        return $role;
    }

    /**
     * Create a role with permissions
     *
     * @param  array $data
     * @return boolean
     */
    public function createRole($data)
    {
        $timestamp = date('Y-m-d H:i:s');

        $insertedData = [
            'created_at' => $timestamp,
            'updated_at' => $timestamp
        ];

        if (isset($data['permissions']))
            $insertedData['permissions'] = json_encode($data['permissions']);

        if (isset($data['title']))
            $insertedData['title'] = $data['title'];

        return DB::table($this->roleTable)
        ->insert($insertedData);
    }

    /**
     * Update role with permissions
     *
     * @param  integer $roleId
     * @param  array   $data
     * @return boolean
     */
    public function updateRole($roleId, $data)
    {
        $timestamp = date('Y-m-d H:i:s');

        $updatedData = [
            'updated_at' => $timestamp
        ];

        if (isset($data['permissions']))
            $updatedData['permissions'] = json_encode($data['permissions']);

        if (isset($data['title']))
            $updatedData['title'] = $data['title'];

        $update = DB::table($this->roleTable)
        ->where('id', $roleId)
        ->update($updatedData);

        return $update > 0;
    }

    /**
     * Delete role and related user-roles
     *
     * @param  integer $roleId
     * @return boolean
     */
    public function deleteRole($roleId)
    {
        // delete role
        $deleteRole = DB::table($this->roleTable)
        ->where('id', $roleId)
        ->delete();

        // delete user role
        $deleteUserRole = DB::table($this->userRoleTable)
        ->where('passport_role_id', $roleId)
        ->delete();

        return $deleteRole > 0;
    }

    /**
     * Get user role
     *
     * @param  integer $userId
     * @return object|null
     */
    public function getUserRole($userId)
    {
        return DB::table($this->userRoleTable)
        ->join($this->roleTable, "{$this->userRoleTable}.passport_role_id", '=', "{$this->roleTable}.id")
        ->where("{$this->userRoleTable}.user_id", $userId)
        ->select(
            "{$this->roleTable}.id",
            "{$this->roleTable}.title",
            "{$this->roleTable}.created_at",
            "{$this->roleTable}.updated_at"
        )
        ->first();
    }

    /**
     * Update user role
     *
     * @param  integer $userId
     * @param  integer $roleId
     * @return boolean
     */
    public function updateUserRole($userId, $roleId)
    {
        $timestamp = date('Y-m-d H:i:s');

        $userRole = DB::table($this->userRoleTable)
        ->where('user_id', $userId)
        ->get();

        if (count($userRole) > 0)
        {
            $updateUserRole = DB::table($this->userRoleTable)
            ->where('user_id', $userId)
            ->update([
                'passport_role_id' => $roleId,
                'updated_at'       => $timestamp
            ]);

            return $updateUserRole > 0;
        }
        else
        {
            return DB::table($this->userRoleTable)
            ->insert([
                'user_id'          => $userId,
                'passport_role_id' => $roleId,
                'created_at'       => $timestamp,
                'updated_at'       => $timestamp
            ]);
        }
    }

    /**
     * Check user permission
     *
     * @param  integer $userId
     * @param  string  $permissionCode
     * @return boolean
     */
    public function checkUserPermission($userId, $permissionCode)
    {
        // user role
        $userRole = $this->getUserRole($userId);

        if ($userRole)
        {
            // get role
            $role = $this->getRole($userRole->id, false);

            // permissions
            $permissions = $role->permissions;

            // seacrh code in permissions
            foreach ($permissions as $permission)
            {
                if ($permission->code === $permissionCode)
                {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generate permissions command
     *
     * @return void
     */
    public function generatePermissionsCommand()
    {
        // Get routes
        $routes = Route::getRoutes();

        // Get route aliases
        $routeAliases = [];
        foreach ($routes as $route)
        {
            // Route info
            $routeName          = $route->getName();
            $routeAction        = $route->getAction();
            $routeBeforeFilters = isset($routeAction['before']) ? $routeAction['before'] : null;

            // Passport filter exists
            $passportFilterExists = function() use ($routeBeforeFilters)
            {
                if ($routeBeforeFilters)
                {
                    if (! is_array($routeBeforeFilters))
                    {
                        $routeBeforeFilters = explode('|', $routeBeforeFilters);
                    }

                    return array_search('passport', $routeBeforeFilters) !== false;
                }

                return false;
            };

            // Add route to collections
            if ($routeName && $passportFilterExists())
            {
                array_push($routeAliases, $routeName);
            }
        }

        // Sort route aliases
        sort($routeAliases, SORT_NATURAL | SORT_FLAG_CASE);

        // Get permissions
        $permissions = DB::table($this->permissionTable)->lists('code', 'id');

        // Permissions difference
        $deletedPermissions  = array_diff($permissions, $routeAliases);
        $insertedPermissions = array_diff($routeAliases, $permissions);

        // Delete unnecessary permissions
        if (count($deletedPermissions) > 0)
        {
            foreach ($deletedPermissions as $code)
            {
                DB::table($this->permissionTable)
                ->where('code', $code)
                ->delete();
            }
        }

        // Insert new permissions
        if (count($insertedPermissions) > 0)
        {
            DB::table($this->permissionTable)
            ->insert(array_map(function($code)
            {
                return ['code' => $code];
            }, $insertedPermissions));
        }
    }
}
