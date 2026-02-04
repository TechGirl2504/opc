<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedException extends Exception
{
    protected $statusCode = 403;
    protected $action;

    public function __construct(string $action = null, $message = null)
    {
        $this->action = $action;
        $message = $message ?? ($action ? "You are not authorized to perform this action: {$action}" : 'Unauthorized access');
        
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }
}
