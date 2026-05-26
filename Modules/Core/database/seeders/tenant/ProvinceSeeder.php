<?php

namespace Modules\Core\Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = [
            [
                'province_id' => 'ID-JK',
                'province_name' => 'DKI Jakarta',
                'province_code' => 'JK',
            ],
            [
                'province_id' => 'ID-JB',
                'province_name' => 'Jawa Barat',
                'province_code' => 'JB',
            ],
            [
                'province_id' => 'ID-JT',
                'province_name' => 'Jawa Tengah',
                'province_code' => 'JT',
            ],
            [
                'province_id' => 'ID-JI',
                'province_name' => 'Jawa Timur',
                'province_code' => 'JI',
            ],
            [
                'province_id' => 'ID-BA',
                'province_name' => 'Bali',
                'province_code' => 'BA',
            ],
        ];

        foreach ($provinces as $province) {
            DB::table('core.province')->updateOrInsert(
                ['province_id' => $province['province_id']],
                [
                    'province_name' => $province['province_name'],
                    'province_code' => $province['province_code'],
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Provinces seeded successfully.');
    }
}
