<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Core\Models\Company;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'company_id'   => Str::uuid(),
            'company_name' => fake()->name(),
        ];
    }
}
