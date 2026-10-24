<?php

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => Sanctum::actingAs(User::factory()->create()));

it('registra una entrada y aumenta el stock', function () {
    $product = Product::factory()->create(['stock' => 5]);

    $this->postJson("/api/products/{$product->id}/movements", ['type' => 'in', 'quantity' => 10, 'reason' => 'Compra'])
        ->assertCreated()
        ->assertJsonPath('data.type', 'in')
        ->assertJsonPath('data.quantity', 10);

    expect($product->fresh()->stock)->toBe(15);
    $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'in', 'quantity' => 10]);
});

it('registra una salida y descuenta el stock', function () {
    $product = Product::factory()->create(['stock' => 20]);

    $this->postJson("/api/products/{$product->id}/movements", ['type' => 'out', 'quantity' => 8])
        ->assertCreated();

    expect($product->fresh()->stock)->toBe(12);
});

it('rechaza una salida mayor al stock y no cambia nada', function () {
    $product = Product::factory()->create(['stock' => 3]);

    $this->postJson("/api/products/{$product->id}/movements", ['type' => 'out', 'quantity' => 4])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity']);

    expect($product->fresh()->stock)->toBe(3);
    $this->assertDatabaseCount('stock_movements', 0);
});

it('permite sacar exactamente todo el stock', function () {
    $product = Product::factory()->create(['stock' => 3]);

    $this->postJson("/api/products/{$product->id}/movements", ['type' => 'out', 'quantity' => 3])
        ->assertCreated();

    expect($product->fresh()->stock)->toBe(0);
});

it('valida el tipo y la cantidad', function () {
    $product = Product::factory()->create();

    $this->postJson("/api/products/{$product->id}/movements", ['type' => 'otro', 'quantity' => 0])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'quantity']);
});

it('responde 404 si el producto no existe', function () {
    $this->postJson('/api/products/9999/movements', ['type' => 'in', 'quantity' => 1])->assertNotFound();
    $this->getJson('/api/products/9999/movements')->assertNotFound();
});

it('lista el historial con filtro por tipo', function () {
    $product = Product::factory()->create();
    StockMovement::factory()->for($product)->create(['type' => 'in']);
    StockMovement::factory()->count(2)->for($product)->create(['type' => 'out']);

    $this->getJson("/api/products/{$product->id}/movements")->assertOk()->assertJsonCount(3, 'data');
    $this->getJson("/api/products/{$product->id}/movements?type=out")->assertJsonCount(2, 'data');
});

it('valida el rango de fechas del historial', function () {
    $product = Product::factory()->create();

    $this->getJson("/api/products/{$product->id}/movements?from=2026-10-10&to=2026-10-01")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['to']);
});
