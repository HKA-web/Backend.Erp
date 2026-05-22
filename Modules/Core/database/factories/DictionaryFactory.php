<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Core\Models\Dictionary;

class DictionaryFactory extends Factory
{
    protected $model = Dictionary::class;

    public function definition(): array
    {
        return [
            'dictionary_id' => Str::uuid(),
            'dictionary_name' => fake()->name(),
        ];
    }
}
