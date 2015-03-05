<?php namespace Sako\Passport\Commands;

use Config, DB, Route;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Console\Command;

class PermissionGeneratorCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'passport:generate-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate permissions from route names';

    /**
     * Permission table name
     *
     * @var string
     */
    protected $permissionTable = 'passport_permissions';

    /**
     * Fire
     *
     * @return void
     */
    public function fire()
    {
        if ($this->confirm("Do you want to permissions update? [yes|no]"))
        {
            $this->generatePermissions();
            $this->info('Permissions update completed.');
        }
    }

    /**
     * Generate permissions
     *
     * @return void
     */
    public function generatePermissions()
    {
        // Get routes
        $routes = Route::getRoutes();

        // Get route aliases
        $routeAliases = [];
        foreach ($routes as $route)
        {
            $routeName = $route->getName();

            if ($routeName)
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
