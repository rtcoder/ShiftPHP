<?php

namespace Shift\Service;

/**
 * Interface ServiceInterface
 * @package Shift
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
