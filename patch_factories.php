<?php
$files = [
    'Modules/Authentication/database/factories/UserFactory.php' => ['User', 'Authentication'],
    'Modules/Core/database/factories/VillageFactory.php' => ['Village', 'Core'],
    'Modules/Core/database/factories/MenuFactory.php' => ['Menu', 'Core'],
    'Modules/Core/database/factories/ProvinceFactory.php' => ['Province', 'Core'],
    'Modules/Core/database/factories/CityFactory.php' => ['City', 'Core'],
    'Modules/Core/database/factories/CompanyFactory.php' => ['Company', 'Core'],
    'Modules/Core/database/factories/DictionaryFactory.php' => ['Dictionary', 'Core'],
    'Modules/Core/database/factories/DistrictFactory.php' => ['District', 'Core']
];

foreach ($files as $file => $info) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    if (strpos($content, 'Sequence::firstOrCreate') !== false) continue;
    
    $model = strtoupper($info[0]);
    $module = strtoupper($info[1]);
    
    $insert = <<<PHP
        \$modelUpper = strtoupper('$info[0]');
        \$moduleUpper = strtoupper('$info[1]');
        
        \Modules\Core\Models\Sequence::firstOrCreate(
            ['sequence_name' => \$modelUpper],
            [
                'sequence_id' => \Illuminate\Support\Str::uuid(),
                'prefix' => "{\$modelUpper}-{YYYY}{MM}-",
                'suffix' => "-{\$moduleUpper}",
                'padding' => 4,
                'current_number' => 0,
                'reset_type' => 'MONTHLY',
                'last_reset_date' => now(),
            ]
        );

        return [
PHP;

    $content = str_replace('        return [', $insert, $content);
    file_put_contents($file, $content);
    echo "Patched $file\n";
}
