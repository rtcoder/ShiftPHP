<?php

namespace Engine\Service;

/**
 * Interface ServiceInterface
 * @package Engine
 */
interface ServiceInterface
{
    /**
     * Initialize the service
     */
    public function initialize(): void;

    /**
     * Check if service is ready
     */
    public function isReady(): bool;

    /**
     * Get service name
     */
    public function getName(): string;
} 
