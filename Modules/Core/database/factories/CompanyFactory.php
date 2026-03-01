<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Models\Company;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'company_id'   => Str::uuid(),
            'company_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'website' => 'www.google.com',
            'province_id' => DB::table('core.province')->inRandomOrder()->value('province_id'),
            'city_id' => DB::table('core.city')->inRandomOrder()->value('city_id'),
            'district_id' => DB::table('core.district')->inRandomOrder()->value('district_id'),
            'village_id' => DB::table('core.village')->inRandomOrder()->value('village_id'),
        ];
    }
}
