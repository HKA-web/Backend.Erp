<?php

namespace Modules\Authentication\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Authentication\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        $uniquePrefix = Str::random(5) . '_' . fake()->unique(true)->userName();
        return [
            'user_id'    => Str::uuid(),
            'user_name' => fake()->name(),
            'email'     => $uniquePrefix . '@example.com',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('#user#'),
            'remember_token' => Str::random(10),
        ];
    }
}
