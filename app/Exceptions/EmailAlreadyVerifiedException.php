<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class EmailAlreadyVerifiedException extends Exception
{
    public function __construct()
    {
        parent::__construct(message: 'Email already verified.', code: Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
