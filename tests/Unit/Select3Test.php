<?php

namespace afantecsf\LivewireSelect3\Tests\Unit;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use afantecsf\LivewireSelect3\Components\Select3;
use afantecsf\LivewireSelect3\Tests\TestCase;
use afantecsf\LivewireSelect3\Tests\TestModel;
use Livewire\Livewire;

uses(TestCase::class);

beforeEach(function() {
    $this->artisan('migrate:fresh');
});

test('component mounts correctly', function () {
    Livewire::test(Select3::class, [
        'name' => 'test',
        'placeholder' => 'Select test'
    ])
        ->assertSet('name', 'test')
        ->assertSet('placeholder', 'Select test')
        ->assertSet('selectedValue', null);
});

test('static options work correctly', function () {
    $options = [
        ['id' => 1, 'name' => 'Option 1'],
        ['id' => 2, 'name' => 'Option 2']
    ];

    Livewire::test(Select3::class, [
        'name' => 'test',
        'staticOptions' => $options
    ])
        ->assertSet('options', $options);
});

test('search filters static options', function () {
    $options = [
        ['id' => 1, 'name' => 'Apple'],
        ['id' => 2, 'name' => 'Banana']
    ];

    Livewire::test(Select3::class, [
        'name' => 'test',
        'staticOptions' => $options
    ])
        ->set('search', 'App')
        ->assertCount('options', 1)
        ->assertSet('options.0.name', 'Apple');
});

test('model search works', function () {
    TestModel::factory()->createMany([
        ['name' => 'Test 1'],
        ['name' => 'Test 2']
    ]);

    Livewire::test(Select3::class, [
        'name' => 'test',
        'model' => TestModel::class
    ])
        ->set('search', 'Test')
        ->assertCount('options', 2);
});

test('empty search should clear options', function () {
    TestModel::factory()->createMany([
        ['name' => 'Test 1'],
        ['name' => 'Test 2']
    ]);

    Livewire::test(Select3::class, [
        'name' => 'test',
        'model' => TestModel::class
    ])
        ->set('search', '')
        ->assertCount('options', 0);
});

test('search respects minimum input length', function () {
    TestModel::factory()->createMany([
        ['name' => 'Test 1'],
        ['name' => 'Test 2']
    ]);

    Livewire::test(Select3::class, [
        'name' => 'test',
        'model' => TestModel::class,
        'minInputLength' => 3
    ])
        ->set('search', 'Te')
        ->assertCount('options', 0)
        ->set('search', 'Test')
        ->assertCount('options', 2);
});

test('dependent select is disabled without parent value', function () {
    Livewire::test(Select3::class, [
        'name' => 'test',
        'dependsOn' => 'parent'
    ])
        ->assertSet('isDisabled', true);
});

test('dependent select enables when parent value is set', function () {
    $component = Livewire::test(Select3::class, [
        'name' => 'test',
        'dependsOn' => 'parent'
    ]);

    $component->dispatch('select3:updated', 'parent', '1')
        ->assertSet('isDisabled', false);
});

test('dependent select filters by parent value', function () {
    // Create parent and child records
    $parent1 = TestModel::factory()->create(['name' => 'Parent 1']);
    $parent2 = TestModel::factory()->create(['name' => 'Parent 2']);

    TestModel::factory()->createMany([
        ['name' => 'Child 1', 'parent_id' => $parent1->id],
        ['name' => 'Child 2', 'parent_id' => $parent1->id],
        ['name' => 'Child 3', 'parent_id' => $parent2->id]
    ]);

    $component = Livewire::test(Select3::class, [
        'name' => 'test',
        'model' => TestModel::class,
        'dependsOn' => 'parent_id'
    ]);

    // Set parent value and search
    $component->dispatch('select3:updated', 'parent_id', $parent1->id)
        ->set('search', 'Child')
        ->assertCount('options', 2);
});

test('api endpoint fetches data correctly', function () {
    Http::fake([
        'test.com' => Http::response([
            ['id' => 1, 'name' => 'API Test 1'],
            ['id' => 2, 'name' => 'API Test 2']
        ])
    ]);

    Livewire::test(Select3::class, [
        'name' => 'test',
        'apiEndpoint' => 'test.com'
    ])
        ->set('search', 'test')
        ->assertCount('options', 2);
});

test('handles api errors gracefully', function () {
    Http::fake([
        'test.com' => Http::response(null, 500)
    ]);

    Livewire::test(Select3::class, [
        'name' => 'test',
        'apiEndpoint' => 'test.com'
    ])
        ->set('search', 'test')
        ->assertSet('errorMessage', 'API request failed: Unknown error')
        ->assertCount('options', 0);
});

test('handles api timeout gracefully', function () {
    Http::fake([
        'test.com' => Http::response()->throw(new ConnectionException('timeout'))
    ]);

    Livewire::test(Select3::class, [
        'name' => 'test',
        'apiEndpoint' => 'test.com'
    ])
        ->set('search', 'test')
        ->assertSet('errorMessage', 'Could not connect to API endpoint.')
        ->assertCount('options', 0);
});

test('filters work with additional params', function () {
    TestModel::factory()->createMany([
        ['name' => 'Test 1', 'active' => true],
        ['name' => 'Test 2', 'active' => false]
    ]);

    Livewire::test(Select3::class, [
        'name' => 'test',
        'model' => TestModel::class,
        'filterCallback' => 'activeFilter'
    ])
        ->set('search', 'Test')
        ->assertCount('options', 1);
});

test('handles invalid model class', function () {
    Livewire::test(Select3::class, [
        'name' => 'test',
        'model' => 'NonExistentModel'
    ])
        ->set('search', 'test')
        ->assertSet('errorMessage', 'Model class NonExistentModel does not exist.')
        ->assertCount('options', 0);
});

test('validates field names for sql injection', function () {
    Livewire::test(Select3::class, [
        'name' => 'test',
        'model' => TestModel::class,
        'displayField' => 'name; DROP TABLE users;'
    ])
        ->set('search', 'test')
        ->assertSet('errorMessage', 'Invalid display field: name; DROP TABLE users;');
});

test('updates parent component when value changes', function () {
    Livewire::test(Select3::class, [
        'name' => 'test'
    ])
        ->set('selectedValue', '1')
        ->assertDispatched('select3:value-updated', '1');
});

test('clears selection when parent changes', function () {
    $component = Livewire::test(Select3::class, [
        'name' => 'test',
        'dependsOn' => 'parent',
        'selectedValue' => '1'
    ]);

    $component->dispatch('select3:updated', 'parent', '2')
        ->assertSet('selectedValue', null)
        ->assertSet('search', '');
});

test('respects display and value field configuration', function () {
    TestModel::factory()->create([
        'name' => 'Custom Name',
        'id' => 123
    ]);

    Livewire::test(Select3::class, [
        'name' => 'test',
        'model' => TestModel::class,
        'displayField' => 'name',
        'valueField' => 'id'
    ])
        ->set('search', 'Custom')
        ->assertSet('options.0.value', 123)
        ->assertSet('options.0.text', 'Custom Name');
});

test('debounces search correctly', function () {
    Livewire::test(Select3::class, [
        'name' => 'test',
        'debounce' => 300
    ])
        ->assertSet('debounce', 300);
});