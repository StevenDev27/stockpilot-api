<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => Sanctum::actingAs(User::factory()->create()));

it('crea un producto', function () {
    $category = Category::factory()->create();

    $this->postJson('/api/products', [
        'category_id' => $category->id,
        'sku' => 'ABC-001',
        'name' => 'Cuaderno',
        'price' => 12.50,
        'stock' => 10,
        'min_stock' => 3,
    ])->assertCreated()
        ->assertJsonPath('data.sku', 'ABC-001')
        ->assertJsonPath('data.category.id', $category->id);

    $this->assertDatabaseHas('products', ['sku' => 'ABC-001', 'stock' => 10]);
});

it('valida los datos al crear', function () {
    $this->postJson('/api/products', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_id', 'sku', 'name', 'price']);

    $existing = Product::factory()->create();
    $this->postJson('/api/products', [
        'category_id' => $existing->category_id,
        'sku' => $existing->sku,
        'name' => 'Repetido',
        'price' => -5,
    ])->assertUnprocessable()->assertJsonValidationErrors(['sku', 'price']);
});

it('muestra un producto con su categoría', function () {
    $product = Product::factory()->create();

    $this->getJson("/api/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonStructure(['data' => ['category' => ['id', 'name']]]);
});

it('responde 404 si el producto no existe', function () {
    $this->getJson('/api/products/9999')->assertNotFound();
});

it('actualiza un producto y permite conservar su SKU', function () {
    $product = Product::factory()->create(['sku' => 'KEEP-1']);

    $this->putJson("/api/products/{$product->id}", ['sku' => 'KEEP-1', 'name' => 'Nombre nuevo'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nombre nuevo');
});

it('ignora el campo stock al actualizar', function () {
    $product = Product::factory()->create(['stock' => 5]);

    $this->putJson("/api/products/{$product->id}", ['name' => 'X', 'stock' => 999])->assertOk();

    expect($product->fresh()->stock)->toBe(5);
});

it('elimina un producto', function () {
    $product = Product::factory()->create();

    $this->deleteJson("/api/products/{$product->id}")->assertNoContent();

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

it('filtra por categoría, búsqueda, stock bajo y rango de precio', function () {
    $a = Category::factory()->create();
    $b = Category::factory()->create();
    Product::factory()->for($a)->create(['name' => 'Cuaderno rayado', 'sku' => 'CUA-1', 'price' => 10, 'stock' => 2, 'min_stock' => 5]);
    Product::factory()->for($a)->create(['name' => 'Lapicero azul', 'sku' => 'LAP-1', 'price' => 3, 'stock' => 50, 'min_stock' => 5]);
    Product::factory()->for($b)->create(['name' => 'Silla', 'sku' => 'SIL-1', 'price' => 120, 'stock' => 9, 'min_stock' => 5]);

    $this->getJson("/api/products?category_id={$a->id}")->assertJsonCount(2, 'data');
    $this->getJson('/api/products?search=cuaderno')->assertJsonCount(1, 'data')->assertJsonPath('data.0.sku', 'CUA-1');
    $this->getJson('/api/products?low_stock=1')->assertJsonCount(1, 'data');
    $this->getJson('/api/products?min_price=5&max_price=50')->assertJsonCount(1, 'data');
});

it('pagina los resultados y valida per_page', function () {
    Product::factory()->count(3)->create();

    $this->getJson('/api/products?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 3);

    $this->getJson('/api/products?per_page=500')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
});
