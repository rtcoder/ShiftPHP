<?php

use Shift\Auth\AuthenticatedUser;
use Shift\Auth\AuthenticatorInterface;
use Shift\Auth\AuthorizerInterface;
use Shift\Controller;
use Shift\Middleware\MiddlewareInterface;
use Shift\Request;
use Shift\Response\JsonResponse;
use Shift\Response\Response;
use Shift\Routing\Attributes\Body;
use Shift\Routing\Attributes\BodyDto;
use Shift\Routing\Attributes\Get;
use Shift\Routing\Attributes\Header;
use Shift\Routing\Attributes\PathParam;
use Shift\Routing\Attributes\Post;
use Shift\Routing\Attributes\QueryParam;
use Shift\Routing\Attributes\RoutePrefix;
use Shift\Routing\Attributes\Status;
use Shift\Validation\RequestDto;

#[RoutePrefix('/test')]
final class TestAttributeController extends Controller
{
    #[Get('/api/{argument}')]
    public function api(#[PathParam] ?string $argument = null, #[QueryParam('include')] ?string $include = null): JsonResponse
    {
        $arguments = [];
        if ($argument !== null) {
            $arguments[] = $argument;
        }

        return $this->json([
            'data' => [
                'arguments' => $arguments,
                'include' => $include,
                'routeParams' => $this->request->getRouteParams(),
            ],
        ]);
    }

    #[Post('/created')]
    #[Status(201)]
    #[Header('X-Test', 'created')]
    public function created(#[Body('name')] string $name): array
    {
        return [
            'name' => $name,
            'created' => true,
        ];
    }
}

final class HeaderMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        return new Response(
            $response->getContent(),
            $response->getStatusCode(),
            $response->getHeaders() + ['X-Middleware' => 'class']
        );
    }
}

final class AutowiredGreetingService
{
    public function message(): string
    {
        return 'autowired';
    }
}

final class AutowiredConsumer
{
    public function __construct(public readonly AutowiredGreetingService $service)
    {
    }
}

#[RoutePrefix('/autowired')]
final class AutowiredController extends Controller
{
    public function __construct(private readonly AutowiredGreetingService $service)
    {
    }

    #[Get('/service')]
    public function service(): JsonResponse
    {
        return $this->json([
            'message' => $this->service->message(),
            'path' => $this->getRequest()->getPath(),
        ]);
    }
}

final class CreateUserDto extends RequestDto
{
    public function __construct(
        public readonly string $email,
        public readonly int $age
    ) {
    }

    public static function rules(): array
    {
        return [
            'email' => 'required|string|email',
            'age' => 'required|int|min:18',
        ];
    }
}

#[RoutePrefix('/dto')]
final class DtoController extends Controller
{
    #[Post('/users')]
    public function create(#[BodyDto] CreateUserDto $dto): array
    {
        return [
            'email' => $dto->email,
            'age' => $dto->age,
        ];
    }

    #[Post('/implicit')]
    public function implicit(CreateUserDto $dto): array
    {
        return [
            'email' => $dto->email,
            'age' => $dto->age,
        ];
    }
}

#[RoutePrefix('/auth')]
final class AuthenticatedController extends Controller
{
    #[Get('/me')]
    public function me(Request $request): array
    {
        /** @var AuthenticatedUser|null $user */
        $user = $request->getAttribute(AuthenticatedUser::class);

        return [
            'id' => $user?->id,
        ];
    }
}

final class HeaderAuthenticator implements AuthenticatorInterface
{
    public function authenticate(Request $request): ?AuthenticatedUser
    {
        return $request->getHeader('Authorization') === 'Bearer token'
            ? new AuthenticatedUser('user-1')
            : null;
    }
}

final class AllowAuthorizer implements AuthorizerInterface
{
    public function authorize(AuthenticatedUser $user, Request $request, ?string $ability = null): bool
    {
        return $ability === 'view';
    }
}

#[RoutePrefix('/errors')]
final class FailingController extends Controller
{
    #[Get('/boom')]
    public function boom(): array
    {
        throw new RuntimeException('Controller exploded');
    }
}
