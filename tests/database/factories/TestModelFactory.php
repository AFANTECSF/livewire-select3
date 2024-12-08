<?php

namespace afantecsf\LivewireSelect3\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use afantecsf\LivewireSelect3\Tests\TestModel;

class TestModelFactory extends Factory
{
    protected $model = TestModel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'parent_id' => null,
            'active' => true,
        ];
    }
}