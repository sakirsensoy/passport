<?php

/**
 * Permission filter
 */
Route::filter('passport', function ()
{
    $userId         = null;
    $permission     = false;
    $permissionCode = null;

    if (Auth::check())
    {
        // Get auth user id
        $userId = Auth::user()->id;

        // Get current route name
        $permissionCode = Route::currentRouteName();

        // Permission check
        if (! empty($permissionCode) && Passport::checkUserPermission($userId, $permissionCode))
        {
            $permission = true;
        }
    }

    // Permission messages
    if (! $permission)
    {
        if (Request::ajax())
        {
            return Response::make(Config::get('passport::unauthorized_message'), 401);
        }
        else
        {
            return Redirect::route(Config::get('passport::unauthorized_page'))
            ->with('passport_intended_url',    Route::current()->uri())
            ->with('passport_user_id',         $userId)
            ->with('passport_permission_code', $permissionCode);
        }
    }
});
