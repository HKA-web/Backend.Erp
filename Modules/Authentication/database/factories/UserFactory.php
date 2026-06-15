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
        $uniquePrefix = Str::random(5).'_'.fake()->unique(true)->userName();

        $modelUpper = strtoupper('User');
        $moduleUpper = strtoupper('Authentication');
        
        \Modules\Core\Models\Sequence::firstOrCreate(
            ['sequence_name' => $modelUpper],
            [
                'sequence_id' => \Illuminate\Support\Str::uuid(),
                'prefix' => "{$modelUpper}-{YYYY}{MM}-",
                'suffix' => "-{$moduleUpper}",
                'padding' => 4,
                'current_number' => 0,
                'reset_type' => 'MONTHLY',
                'last_reset_date' => now(),
            ]
        );

        return [
            'user_id' => Str::uuid(),
            'user_name' => fake()->name(),
            'email' => $uniquePrefix.'@example.com',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('#user#'),
            'remember_token' => Str::random(10),
        ];
    }
}
