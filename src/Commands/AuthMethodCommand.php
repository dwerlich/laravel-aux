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
        $this->makeController();


        // Success Message
        $this->info('Sistema de autenticação criado!');
    }

    public function makeController()
    {
        $controller = <<<EOF
<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController
{
    /**
     * @var UserService
     */
    protected \$service;

    /**
     * AuthController constructor.
     *
     * @param UserService \$service
     */
    public function __construct(UserService \$service)
    {
        \$this->service = \$service;
    }

    /**
     * Authenticates a user and issues a token.
     *
     * @param Request \$request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function auth(Request \$request): JsonResponse
    {
        \$loginUserData = \$request->validate([
            'email'=>'required|string|email',
            'password'=>'required|min:8'
        ]);

        \$user = User::where('email',\$loginUserData['email'])
            ->where('status', 1)
            ->first();

        if(!\$user || !Hash::check(\$loginUserData['password'],\$user->password)){
            return response()->json([
                'message' => 'E-mail ou senha inválidos!'
            ],401);
        }

        \$expirationMinutes = config('sanctum.expiration', 180);

        \$token = \$user->createToken('AdminToken', [], Carbon::now()->addMinutes(\$expirationMinutes))->plainTextToken;
        return response()->json([
            'token' => \$token,
        ]);
    }

    /**
     * Get the authenticated user.
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        return response()->json(auth()->user());
    }

    /**
     * Method to invalidate the token.
     *
     * @param Request \$request
     * @return JsonResponse
     */
    public function logout(Request \$request): JsonResponse
    {
        auth()->guard('admin')->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout efetuado.'], 200);
    }
}

EOF;
        file_put_contents(app_path("Http/Controllers/Api/AuthController.php"), $controller);

    }
}
