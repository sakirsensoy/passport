<?php

namespace Sako\Passport\Commands;

use Passport;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Console\Command;
use Route;

class PermissionListCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'passport:list-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate permissions from route names';

    /**
     * Fire
     *
     * @return void
     */
    public function fire()
    {
        $permissions = Passport::getPermissions();
        $routes      = Route::getRoutes();

        $headers = ['permission alias', 'methods', 'endpoint'];
        $rows    = [];

        foreach ($permissions as $permission) {
            $columns = [];
            list($methods, $endpoint) = $this->getRoute($routes, $permission->alias);

            array_push($columns, $permission->alias);
            array_push($columns, $methods);
            array_push($columns, '/'. $endpoint);

            array_push($rows, $columns);
        }
        $this->table($headers, $rows);
    }

    private function getRoute($routes, $alias)
    {
        foreach ($routes as $route) {
            $routeName = $route->getName();
            $methods   = $route->getMethods();
            $methods   = implode('|', $methods);

            if ($routeName == $alias) {
                return [$methods, $route->getUri()];
            }
        }

        return [null, null];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('force', '-f', InputOption::VALUE_NONE, 'Force the operation to run when in production.')
        );
    }
}
