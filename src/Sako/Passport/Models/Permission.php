<?php

namespace Sako\Passport\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'passport_permissions';

    public $timestamps = false;

    public $fillable = [
        'alias',
        'description'
    ];

    public function getDescriptionAttribute($value)
    {
        return $value ?: config(sprintf('passport.aliases.%s', $this->alias));
    }
}