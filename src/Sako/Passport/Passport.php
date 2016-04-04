<?php

namespace Sako\Passport;

use Route;

use Sako\Passport\Models\Role;
use Sako\Passport\Models\UserRole;
use Sako\Passport\Models\Permission;

use Sako\Passport\Exceptions\RoleNotFound;
use Sako\Passport\Exceptions\PermissionNotFound;
use Sako\Passport\Exceptions\DataMismatch;

class Passport {

    /**
     * Permission table name
     *
     * @var string
     */
    protected $permissionTable = 'passport_permissions';

    /**
     * List all permissions
     *
     * @return array
     */
    public function getPermissions()
    {
        return Permission::orderBy('alias', 'ASC')->get();
    }

    public function getPermission($permissionId)
    {
        $permission = Permission::find($permissionId);

        if (! $permission) {
            throw new PermissionNotFound(sprintf('PermissionId not found: %d', $permissionId), 404);
        }

        return $permission;
    }

    public function getPermissionByAlias($alias)
    {
        return Permission::where('alias', $alias)->first();
    }

    public function updatePermission($alias, $description)
    {
        $permission = $this->getPermission($alias);

        $permission->description = $description;

        $permission->save();

        return $permission;
    }

    /**
     * List all roles
     *
     * @return array
     */
    public function getRoles()
    {
        return Role::orderBy('title', 'ASC')->get();
    }

    /**
     * Get role
     *
     * @param  integer $roleId
     * @return object|null
     */
    public function getRole($roleId)
    {
        // all permissions
        $allPermissions = $this->getPermissions();

        // role
        $role = Role::find($roleId);

        if (! $role) {
            throw new RoleNotFound(sprintf('RoleId not found: %d', $roleId), 404);
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
        if (! array_get($data, 'title')) {
            throw new DataMismatch('Title is missing', 400);
        }

        if (! array_get($data, 'permissions') || ! is_array($data['permissions'])) {
            throw new DataMismatch('permissions should be an array', 400);
        }

        // remove inexist permission ids from list
        $permissions = Permission::whereIn('id', $data['permissions'])->lists('id');

        $role = new Role([
            'title'       => $data['title'],
            'permissions' => $permissions
        ]);

        $role->save();

        return $role;
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
        if (! array_get($data, 'title')) {
            throw new DataMismatch('title is missing', 400);
        }

        if (! array_get($data, 'permissions') || ! is_array($data['permissions'])) {
            throw new DataMismatch('permissions should be an array', 400);
        }

        $role = $this->getRole($roleId);

        $permissions = Permission::whereIn('id', $data['permissions'])->lists('id');

        $role->title       = $data['title'];
        $role->permissions = $permissions;
        $role->save();

        return $role;
    }

    /**
     * Delete role and related user-roles
     *
     * @param  integer $roleId
     * @return boolean
     */
    public function deleteRole($roleId)
    {
        $role = $this->getRole($roleId);
        $role->userRoles()->delete();
        $role->delete();

        return true;
    }

    /**
     * Get user role
     *
     * @param  integer $userId
     * @return object|null
     */
    public function getRoleForUser($userId)
    {
        $role = Role::with(['userRoles' => function($query) use ($userId) {
            $query->where('user_id', $userId);
        }])->first();

        return $role;
    }

    /**
     * Update or Create user role
     *
     * @param  integer $userId
     * @param  integer $roleId
     * @return boolean
     */
    public function setUserRole($userId, $roleId)
    {
        $userRole = UserRole::where('user_id', $userId)->first();

        $role = $this->getRole($roleId);

        if ($userRole) {
            $userRole->passport_role_id = $role->id;
            $userRole->save();
        } else {
            $userRole = new UserRole([
                'user_id'          => $userId,
                'passport_role_id' => $role->id
            ]);
            $userRole->save();
        }

        return $userRole->user;
    }

    public function unsetUserRole($userId)
    {
        $userRole = UserRole::where('user_id', $userId)->first();

        if ($userRole) {
            $userRole->delete();
            return true;
        }

        return false;
    }

    /**
     * Check user permission
     *
     * @param  integer $userId
     * @param  string  $alias
     * @return boolean
     */
    public function hasPermission($userId, $alias)
    {
        $role       = $this->getRoleForUser($userId);
        $permission = $this->getPermissionByAlias($alias);

        if ($role && $permission && in_array($permission->id, $role->permissions)) {
            return true;
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
        $routeAliases = collect();

        foreach ($routes as $route) {
            // Route info
            $routeName        = $route->getName();
            $routeAction      = $route->getAction();
            $routeMiddlewares = array_get($routeAction, 'middleware', []);

            // Add route to collections if passport filter exists in middleware
            if ($routeName && in_array('passport', $routeMiddlewares)) {
                $routeAliases->push($routeName);
            }
        }

        // Sort route aliases
        $routeAliases = $routeAliases->sortBy('alias');

        // Get permissions
        $permissions = $this->getPermissions()->lists('alias');

        // Permissions difference
        $insertedPermissions = $routeAliases->diff($permissions);
        $deletedPermissions  = $permissions->diff($routeAliases);

        // Delete unnecessary permissions
        foreach ($deletedPermissions as $alias) {
            Permission::where('alias', $alias)->delete();
        }

        // Insert new permissions
        foreach ($insertedPermissions as $alias) {
            Permission::create([
                'alias' => $alias
            ]);
        }

        return [$insertedPermissions->count(), $deletedPermissions->count()];
    }
}
