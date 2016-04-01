<?php

namespace Sako\Passport\Models;

use Illuminate\Database\Eloquent\Model;
use Passport;

class Role extends Model
{
    protected $table = 'passport_roles';

    public $fillable = [
        'title',
        'permissions'
    ];

    public $casts = [
        'permissions' => 'object'
    ];

    public $appends = [
        'all_permissions',
        'has_permissions'
    ];

    public function getAllPermissionsAttribute()
    {
        $allPermissions = Passport::getPermissions();

        foreach($allPermissions as $permission) {
            $permission->selected = in_array($permission->id, $this->permissions);
        }

        return $allPermissions;
    }

    public function getHasPermissionsAttribute()
    {
        return $this->all_permissions->where('selected', true)->values();
    }

    public function userRoles()
    {
        return $this->hasMany(UserRole::class, 'passport_role_id', 'id');
    }
}