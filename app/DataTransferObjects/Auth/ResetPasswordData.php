<?php

namespace App\DataTransferObjects\Auth;

use Holiq\ActionData\Foundation\DataTransferObject;

readonly class ResetPasswordData extends DataTransferObject
{
    final public function __construct(
        public string $email,
        public string $token,
        public string $password,
    ) {}
}
