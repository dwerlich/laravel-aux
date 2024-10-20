<?php

namespace LaravelAux\Commands;

use Illuminate\Console\Command;

class AuthMethodCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:createAuth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a methods for Authentication';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Prepare structure
        $model = ucfirst($this->argument('model'));
        $this->makeMigration($model);
        $this->makeModel($model);
        $this->makeRepository($model);
        $this->makeService($model);
        $this->makeRequest($model);
        $this->makeController($model);
        $this->appendRoute($model);

        // Success Message
        $this->info('Vê se segue os padrões heein!');
    }
}
