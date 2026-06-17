<?php

use Shift\Error\HttpError;
use Shift\Response\JsonResponse;

return [
    'request parses json and headers' => function (): void {
        $request = makeRequest('POST', '/test/created', '{"name":"Shift"}');

        assertSameValue(['name' => 'Shift'], $request->getJson(), 'JSON body should parse.');
        assertSameValue('Shift', $request->input('name'), 'Input should read JSON body.');
        assertSameValue('Bearer token', $request->getHeader('Authorization'), 'Header should be available.');
    },

    'request rejects malformed json' => function (): void {
        $request = makeRequest('POST', '/test/created', '{bad');

        try {
            $request->getJson();
        } catch (HttpError $error) {
            assertSameValue(400, $error->getStatusCode(), 'Malformed JSON should return 400.');
            return;
        }

        throw new RuntimeException('Expected malformed JSON error was not thrown.');
    },

    'json response encodes payload' => function (): void {
        $response = JsonResponse::ok(['status' => 'ok']);

        assertSameValue(200, $response->getStatusCode(), 'JSON response should default to 200.');
        assertArrayHasKeyValue('Content-Type', 'application/json', $response->getHeaders(), 'JSON response should set content type.');
        assertSameValue('{"status":"ok"}', $response->getContent(), 'JSON response should encode payload.');
    },
];
