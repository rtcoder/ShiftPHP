<?php

use Shift\App;
use Shift\Routing\AttributeRouteLoader;
use Shift\Routing\Router\Router;
use Shift\Validation\ValidationException;
use Shift\Validation\Validator;

return [
    'validator returns validated typed data' => function (): void {
        $validated = (new Validator())->validate(
            ['email' => 'dev@example.com', 'age' => '21', 'active' => 'true'],
            [
                'email' => 'required|email',
                'age' => 'required|int|min:18',
                'active' => 'bool',
            ]
        );

        assertSameValue('dev@example.com', $validated['email'], 'Validator should keep valid email.');
        assertSameValue(21, $validated['age'], 'Validator should cast integers.');
        assertSameValue(true, $validated['active'], 'Validator should cast booleans.');
    },

    'validator throws validation exception' => function (): void {
        try {
            (new Validator())->validate(['email' => 'bad'], ['email' => 'required|email', 'age' => 'required|int']);
        } catch (ValidationException $exception) {
            assertSameValue(422, $exception->getStatusCode(), 'Validation errors should use HTTP 422.');
            assertSameValue(true, isset($exception->getErrors()['email']), 'Validation errors should include invalid fields.');
            assertSameValue(true, isset($exception->getErrors()['age']), 'Validation errors should include missing required fields.');
            return;
        }

        throw new RuntimeException('Expected validation exception was not thrown.');
    },

    'app binds body dto parameters' => function (): void {
        $router = new Router();
        (new AttributeRouteLoader())->load($router, [DtoController::class]);
        $emitter = new CapturingEmitter();

        $app = new App(makeRequest('POST', '/dto/users', '{"email":"dev@example.com","age":"22"}'), $router, $emitter);
        $app->start();

        $payload = json_decode($emitter->content, true);

        assertSameValue(200, $emitter->statusCode, 'Valid DTO payload should pass.');
        assertSameValue('dev@example.com', $payload['email'] ?? null, 'DTO should expose validated email.');
        assertSameValue(22, $payload['age'] ?? null, 'DTO should expose cast age.');
    },

    'app auto-binds request dto parameters by type' => function (): void {
        $router = new Router();
        (new AttributeRouteLoader())->load($router, [DtoController::class]);
        $emitter = new CapturingEmitter();

        $app = new App(makeRequest('POST', '/dto/implicit', '{"email":"dev@example.com","age":"22"}'), $router, $emitter);
        $app->start();

        $payload = json_decode($emitter->content, true);

        assertSameValue(200, $emitter->statusCode, 'Implicit DTO payload should pass.');
        assertSameValue(22, $payload['age'] ?? null, 'Implicit DTO should be bound by type.');
    },

    'app emits validation errors as json' => function (): void {
        $router = new Router();
        (new AttributeRouteLoader())->load($router, [DtoController::class]);
        $emitter = new CapturingEmitter();

        $app = new App(makeRequest('POST', '/dto/users', '{"email":"bad","age":15}'), $router, $emitter);
        $app->start();

        $payload = json_decode($emitter->content, true);

        assertSameValue(422, $emitter->statusCode, 'Invalid DTO payload should return 422.');
        assertSameValue('Validation failed', $payload['error']['message'] ?? null, 'Validation response should include message.');
        assertSameValue(true, isset($payload['error']['context']['errors']['email']), 'Validation response should include field errors.');
    },
];
