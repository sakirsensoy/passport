<?php namespace Sako\Passport\Traits;

use Passport;

trait PassportModelTrait {

    public function getRoleAttribute()
    {
        return Passport::getUserRole($this->id);
    }
}
