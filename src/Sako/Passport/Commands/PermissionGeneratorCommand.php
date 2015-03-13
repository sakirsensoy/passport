<?php namespace Sako\Passport\Commands;

use Passport;

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
     * Fire
     *
     * @return void
     */
    public function fire()
    {
        if ($this->input->getOption('force') || $this->confirm("Do you want to permissions update? [yes|no]"))
        {
            Passport::generatePermissionsCommand();
            $this->info('Permissions update completed.');
        }
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
