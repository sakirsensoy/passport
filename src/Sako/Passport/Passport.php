<?php namespace Sako\Passport;

use Config, DB;

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
                    if ( $rolePermission['selected'] ) array_push($rolePermissions, $rolePermission);
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
        $title       = $data['title'];
        $permissions = json_encode($data['permissions']);
        $createdAt   = date('Y-m-d H:i:s');
        $updatedAt   = $createdAt;

        return DB::table($this->roleTable)
        ->insert([
            'title'       => $title,
            'permissions' => $permissions,
            'created_at'  => $createdAt,
            'updated_at'  => $updatedAt
        ]);
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
        $title       = $data['title'];
        $permissions = json_encode($data['permissions']);
        $updatedAt   = date('Y-m-d H:i:s');

        $update = DB::table($this->roleTable)
        ->where('id', $roleId)
        ->update([
            'title'       => $title,
            'permissions' => $permissions,
            'updated_at'  => $updatedAt
        ]);

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
            "{$this->userRoleTable}.id",
            "{$this->userRoleTable}.user_id",
            "{$this->userRoleTable}.passport_role_id as role_id",
            "{$this->roleTable}.title as role_title",
            "{$this->userRoleTable}.created_at",
            "{$this->userRoleTable}.updated_at"
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
            $role = $this->getRole($userRole->role_id, false);

            // permissions
            $permissions = $role->permissions;

            // seacrh code in permissions
            foreach ($permissions as $permission)
            {
                if ($permission['code'] === $permissionCode)
                {
                    return true;
                }
            }
        }

        return false;
    }
}
