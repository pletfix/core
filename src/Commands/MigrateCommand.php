<?php

namespace Core\Commands;

use Core\Services\Command;

class MigrateCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database migrations.';

//    /**
//     * Possible arguments of the command.
//     *
//     * @var array
//     */
//    protected $arguments = [
//        'name' => ['type' => 'string', 'description' => ''],
//    ];

    /**
     * Possible options of the command.
     *
     * @var array
     */
    protected $options = [
        'rollback' => ['type' => 'bool', 'short' => 'r',  'description' => 'Rollback the last database migration'],
        'reset'    => ['type' => 'bool', 'short' => null, 'description' => 'Rollback all database migrations'],
    ];

    /**
     * @inheritdoc
     */
    protected function handle()
    {
        if ($this->input('reset')) {
            migrator()->reset();
            $this->line('Database successfully reset.');
        }
        else if ($this->input('rollback')) {
            migrator()->rollback();
            $this->line('Last database migration successfully rollback.');
        }
        else {
            migrator()->run();
            $this->line('Database successfully migrated.');
        }
    }
}