<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('calcula la valorización por categoría y el total', function () {
    Sanctum::actingAs(User::factory()->create());

    $a = Category::factory()->create(['name' => 'A']);
    $b = Category::factory()->create(['name' => 'B']);
    Category::factory()->create(['name' => 'Vacía']);
    Product::factory()->for($a)->create(['price' => 10, 'stock' => 3]);
    Product::factory()->for($a)->create(['price' => 2.5, 'stock' => 4]);
    Product::factory()->for($b)->create(['price' => 100, 'stock' => 1]);

    $this->getJson('/api/reports/stock-valuation')
        ->assertOk()
        ->assertJsonPath('data.0.category', 'A')
        ->assertJsonPath('data.0.products_count', 2)
        ->assertJsonPath('data.0.units', 7)
        ->assertJsonPath('data.0.value', 40)
        ->assertJsonPath('data.2.value', 0)
        ->assertJsonPath('total_units', 8)
        ->assertJsonPath('total_value', 140);
});
