<?php

namespace Engine;

class ResponseEmitter
{
    public function emit(Response $response): void
    {
        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $response->getContent();
    }
}
