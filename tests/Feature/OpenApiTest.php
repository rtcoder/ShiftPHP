<?php

use Console\Commands\OpenApi;
use Shift\Console\CommandRegistry;
use Shift\OpenApi\OpenApiGenerator;
use Shift\OpenApi\OpenApiLivePage;
use Shift\Routing\AttributeRouteLoader;
use Shift\Routing\Router\Router;

return [
    'command registry discovers openapi command' => function (): void {
        $registry = CommandRegistry::default();

        assertSameValue(OpenApi::class, $registry->find('openapi'), 'Registry should expose openapi command.');
        assertSameValue(OpenApi::class, $registry->find('api:docs'), 'Registry should resolve openapi alias.');
    },
    'openapi generator documents route attributes' => function (): void {
        $router = new Router();
        (new AttributeRouteLoader())->load($router, [
            TestAttributeController::class,
            DtoController::class,
        ]);

        $document = (new OpenApiGenerator())->generate($router);

        assertSameValue('3.0.3', $document['openapi'], 'OpenAPI version should be present.');
        assertArrayHasKeyValue('operationId', 'testAttributeControllerApi', $document['paths']['/test/api/{argument}']['get'], 'Path operation should include operation id.');

        $getOperation = $document['paths']['/test/api/{argument}']['get'];
        assertSameValue('path', $getOperation['parameters'][0]['in'], 'Path parameter should be documented.');
        assertSameValue('argument', $getOperation['parameters'][0]['name'], 'Path parameter name should be documented.');
        assertSameValue(true, $getOperation['parameters'][0]['required'], 'Path parameter should be required.');
        assertSameValue('query', $getOperation['parameters'][1]['in'], 'Query parameter should be documented.');
        assertSameValue('include', $getOperation['parameters'][1]['name'], 'Query parameter name should be documented.');

        $createdOperation = $document['paths']['/test/created']['post'];
        assertArrayHasKeyValue('description', 'Created', $createdOperation['responses']['201'], 'Status attribute should set response code.');
        assertSameValue('created', $createdOperation['responses']['201']['headers']['X-Test']['schema']['example'], 'Header attribute should be documented.');
        assertSameValue('string', $createdOperation['requestBody']['content']['application/json']['schema']['properties']['name']['type'], 'Body parameter should be documented.');

        $dtoOperation = $document['paths']['/dto/users']['post'];
        $dtoSchema = $dtoOperation['requestBody']['content']['application/json']['schema'];
        assertSameValue('string', $dtoSchema['properties']['email']['type'], 'DTO string field should be documented.');
        assertSameValue('integer', $dtoSchema['properties']['age']['type'], 'DTO int field should be documented.');
        assertSameValue(['email', 'age'], $dtoSchema['required'], 'DTO required fields should be documented.');
    },
    'openapi command writes output file' => function (): void {
        $root = sys_get_temp_dir() . '/shift-openapi-' . bin2hex(random_bytes(6));
        mkdir($root, 0775, true);
        $output = $root . '/openapi.json';

        try {
            ob_start();
            (new OpenApi())->execute('--output=' . $output);
            $message = ob_get_clean();

            assertFileExists($output, 'OpenAPI command should write the requested output file.');
            assertStringContains('OpenAPI document written', $message, 'OpenAPI command should print success message.');

            $document = json_decode((string) file_get_contents($output), true);
            assertSameValue('3.0.3', $document['openapi'] ?? null, 'Written OpenAPI JSON should be valid.');
            assertSameValue('OK', $document['paths']['/health']['get']['responses']['200']['description'] ?? null, 'Written OpenAPI JSON should include module routes.');
        } finally {
            removeDirectory($root);
        }
    },
    'openapi live page renders swagger-like shell' => function (): void {
        $html = (new OpenApiLivePage())->render();

        assertStringContains('ShiftPHP OpenAPI', $html, 'Live page should include the OpenAPI title.');
        assertStringContains("fetch('openapi.json')", $html, 'Live page should load generated OpenAPI JSON.');
        assertStringContains('Responses', $html, 'Live page should render response sections.');
    },
    'openapi help documents live server options' => function (): void {
        ob_start();
        (new \Console\Commands\Help())->execute('openapi');
        $output = ob_get_clean();

        assertStringContains('--live', $output, 'OpenAPI help should document live mode.');
        assertStringContains('--host=127.0.0.1', $output, 'OpenAPI help should document host option.');
        assertStringContains('--port=8088', $output, 'OpenAPI help should document port option.');
    },
];
