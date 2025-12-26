<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Validation\Validator;

class CustomValidationException extends Exception
{
    protected $validator;
    protected $statusCode = 422;

    public function __construct(Validator $validator, $message = 'Validation failed')
    {
        parent::__construct($message);
        $this->validator = $validator;
    }

    public function getValidator(): Validator
    {
        return $this->validator;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): array
    {
        return $this->validator->errors()->toArray();
    }
}
