<?php

namespace Modules\Health\Controllers;

use Engine\Controller;
use Engine\Response\JsonResponse;
use Engine\Routing\Attributes\Get;
use Engine\Routing\Attributes\RoutePrefix;
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
