<?php

namespace Modules\Health\Controllers;

use Shift\Controller;
use Shift\Response\JsonResponse;
use Shift\Routing\Attributes\Get;
use Shift\Routing\Attributes\RoutePrefix;
use Modules\Health\Services\HealthService;

#[RoutePrefix('/health')]
class HealthController extends Controller
{
    #[Get('')]
    public function index(): JsonResponse
    {
        /** @var HealthService $health */
        $health = $this->getContainer()->resolve(HealthService::class);

        return $this->json($health->status());
    }
}
