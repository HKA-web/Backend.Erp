<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Core\Models\City;
use Modules\Core\Models\Company;
use Modules\Core\Models\District;
use Modules\Core\Models\Menu;
use Modules\Core\Models\Province;
use Modules\Core\Models\Village;
use App\Models\Tenant;
use Spatie\Permission\Models\Permission;

class CoreDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Province::factory()->create();
        City::factory()->create();
        District::factory()->create();
        Village::factory()->create();
        
        $domain = fake()->unique()->userName().'.com';
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'data' => json_encode([
                'company_name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => fake()->phoneNumber(),
                'address' => fake()->address(),
                'website' => $domain,
            ]),
        ]);
        Company::factory()->create([
            'tenant_id' => $tenant->id,
            'website' => $domain,
        ]);
        $tenant->domains()->create([
            'domain' => $domain,
        ]);

        Menu::factory()->create();

        // Create permissions
        $actions = ['lookup', 'view', 'add', 'edit', 'delete'];
        $resources = ['province', 'city', 'district', 'village', 'company', 'dictionary', 'menu', 'option'];
        
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "core.{$action}.{$resource}", 'guard_name' => 'api']);
            }
        }
    }
}
