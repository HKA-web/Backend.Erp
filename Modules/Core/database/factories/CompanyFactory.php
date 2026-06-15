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
        $modelUpper = strtoupper('Company');
        $moduleUpper = strtoupper('Core');
        
        \Modules\Core\Models\Sequence::firstOrCreate(
            ['sequence_name' => $modelUpper],
            [
                'sequence_id' => \Illuminate\Support\Str::uuid(),
                'prefix' => "{$modelUpper}-{YYYY}{MM}-",
                'suffix' => "{$moduleUpper}",
                'padding' => 4,
                'current_number' => 0,
                'reset_type' => 'MONTHLY',
                'last_reset_date' => now(),
            ]
        );

        return [
            'company_id' => Str::uuid(),
            'tenant_id' => null,
            'company_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'website' => 'www.google.com',
            'province_id' => DB::table('core.province')->inRandomOrder()->value('province_id'),
            'city_id' => DB::table('core.city')->inRandomOrder()->value('city_id'),
            'district_id' => DB::table('core.district')->inRandomOrder()->value('district_id'),
            'village_id' => DB::table('core.village')->inRandomOrder()->value('village_id'),
            'properties' => json_encode([]),
            'enable' => true,
            'readonly' => false,
            'is_removed' => false,
            'status' => 'DRAFT',
        ];
    }
}
