<?php

namespace App\DataTransferObjects\Auth;

use Holiq\ActionData\Foundation\DataTransferObject;

readonly class RegisterData extends DataTransferObject
{
    final public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
