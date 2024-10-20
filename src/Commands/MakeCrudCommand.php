<?php

namespace LaravelAux\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeCrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a basic CRUD (Controller, Service, Repository, Request...)';

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

    /**
     * Method to append Routes to api.php file (Laravel)
     *
     * @param string $model
     */
    private function appendRoute(string $model): void
    {
        $plural = strtolower(Str::plural($model));
        $route = <<<EOF

use App\Http\Controllers\Api\{$model}Controller;

/*
|--------------------------------------------------------------------------|
| {$model} Routes                                                          |
|--------------------------------------------------------------------------|
*/
Route::resource('{$plural}', {$model}Controller::class);
EOF;
        file_put_contents(base_path('routes/api.php'), $route . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Method to make Controller based on passed Model
     *
     * @param string $model
     */
    private function makeController(string $model): void
    {
        $service = $model . 'Service';
        $request = $model . 'Request';
        $controller = <<<EOF
<?php

namespace App\Http\Controllers\Api;

use App\Services\\$service;
use App\Http\Requests\\$request;

class {$model}Controller extends BaseController
{
    /**
     * {$model}Controller constructor.
     *
     * @param {$service} \$service
     * @param {$request} \$request
     */
    public function __construct({$service} \$service)
    {
        parent::__construct(\$service, new {$request});
    }
}
EOF;
        file_put_contents(app_path("Http/Controllers/Api/{$model}Controller.php"), $controller);
    }

    /**
     * Method to make Request based on passed Model
     *
     * @param string $model
     */
    private function makeRequest(string $model): void
    {
        $request = <<<EOF
<?php

namespace App\Http\Requests;

class {$model}Request extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'title' => 'required',
            'description' => 'required'
        ];
    }

    /**
     * Validation messages
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute é obrigatório',
        ];
    }

    /**
     * Attributes Name
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'title' => 'Título',
            'description' => 'Descrição'
        ];
    }
}
EOF;
        file_put_contents(app_path("Http/Requests/{$model}Request.php"), $request);
    }

    /**
     * Method to make Service based on passed Model
     *
     * @param string $model
     */
    private function makeService(string $model): void
    {
        $repository = $model . 'Repository';
        $service = <<<EOF
<?php

namespace App\Services;

use App\Repositories\\$repository;

class {$model}Service extends BaseService
{
    /**
     * {$model}Service constructor.
     *
     * @param {$repository} \$repository
     */
    public function __construct({$repository} \$repository)
    {
        parent::__construct(\$repository);
    }
}
EOF;
        file_put_contents(app_path("Services/{$model}Service.php"), $service);
    }

    /**
     * Method to make Repository based on passed Model
     *
     * @param string $model
     */
    private function makeRepository(string $model): void
    {
        $repository = <<<EOF
<?php

namespace App\Repositories;

use App\Models\\$model;

class {$model}Repository extends BaseRepository
{
    /**
     * {$model}Repository constructor.
     *
     * @param {$model} \$model
     */
    public function __construct({$model} \$model)
    {
        parent::__construct(\$model);
    }
}
EOF;
        file_put_contents(app_path("Repositories/{$model}Repository.php"), $repository);
    }

    /**
     * Method to make Eloquent Model
     *
     * @param string $model
     */
    private function makeModel(string $model): void
    {
        $modelContent = <<<EOF
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class {$model} extends Model
{
    use SoftDeletes;

    protected \$guarded = [
        'id'
    ];

    protected \$fillable = [
        'title', 'description'
    ];
}
EOF;
        file_put_contents(app_path("Models/{$model}.php"), $modelContent);
    }

    /**
     * Method to make Migration based on Model
     *
     * @param string $model
     */
    public function makeMigration(string $model): void
    {
        $plural = Str::plural($model);
        $lower = strtolower($plural);
        $migration = <<<EOF
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('{$lower}', function (Blueprint \$table) {
            \$table->id();
            \$table->string('title');
            \$table->string('description');
            \$table->timestamps();
            \$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('{$lower}');
    }
};
EOF;
        file_put_contents(database_path("migrations/" . date('Y_m_d_His') . "_create_{$lower}_table.php"), $migration);
    }
}
