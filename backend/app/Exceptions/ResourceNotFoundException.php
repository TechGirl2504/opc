<?php

namespace App\Exceptions;

use Exception;

class ResourceNotFoundException extends Exception
{
    protected $statusCode = 404;
    protected $resource;
    protected $resourceId;

    public function __construct(string $resource, $resourceId = null, $message = null)
    {
        $this->resource = $resource;
        $this->resourceId = $resourceId;
        
        $message = $message ?? "{$resource} not found" . ($resourceId ? " (ID: {$resourceId})" : '');
        
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function getResourceId()
    {
        return $this->resourceId;
    }
}
