<?php

namespace Sako\Passport\Traits;

use Passport;

trait PassportModelTrait {

    public function getRoleAttribute()
    {
        return Passport::getRoleForUser($this->id);
    }

    public function hasPermission($alias)
    {
        return Passport::hasPermission($this->id, $alias);
    }
}
