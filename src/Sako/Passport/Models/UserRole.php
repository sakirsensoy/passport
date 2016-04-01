<?php

namespace Sako\Passport\Models;

use Illuminate\Database\Eloquent\Model;
use Sako\Passport\Exceptions\UserModelRequired;

class UserRole extends Model
{
    protected $table = 'passport_user_roles';

    public $casts = [
        'user_id'          => 'integer',
        'passport_role_id' => 'integer'
    ];

    public $fillable = [
        'user_id',
        'passport_role_id'
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'passport_role_id');
    }

    public function user()
    {
        $relation = config('passport.user_model');

        if (! class_exists($relation)) {
            throw new UserModelRequired('user_model misconfigured in package config. class not found: ' . $relation);
        }

        return $this->belongsTo($relation, 'user_id');
    }
}