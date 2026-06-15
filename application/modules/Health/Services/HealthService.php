<?php

namespace Modules\Health\Services;

class HealthService
{
    public function status(): array
    {
        return [
            'status' => 'ok',
            'module' => 'health',
        ];
    }
}
