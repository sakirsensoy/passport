<?php namespace Sako\Passport\Traits;

use Sako\Passport\Passport;

trait PassportModelTrait {

    public function getRoleAttribute()
    {
        return Passport::getUserRole($this->id);
    }
}
