<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => Sanctum::actingAs(User::factory()->create()));

it('lista categorías paginadas con conteo de productos', function () {
    Category::factory()->has(Product::factory()->count(2))->create();

    $this->getJson('/api/categories')
        ->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta'])
        ->assertJsonPath('data.0.products_count', 2);
});

it('crea una categoría', function () {
    $this->postJson('/api/categories', ['name' => 'Papelería', 'description' => 'Útiles'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Papelería');

    $this->assertDatabaseHas('categories', ['name' => 'Papelería']);
});

it('valida el nombre al crear', function () {
    $this->postJson('/api/categories', [])->assertUnprocessable()->assertJsonValidationErrors(['name']);

    Category::factory()->create(['name' => 'Oficina']);
    $this->postJson('/api/categories', ['name' => 'Oficina'])->assertUnprocessable()->assertJsonValidationErrors(['name']);
});

it('muestra una categoría', function () {
    $category = Category::factory()->create();

    $this->getJson("/api/categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $category->id);
});

it('responde 404 en JSON si la categoría no existe', function () {
    $this->getJson('/api/categories/9999')
        ->assertNotFound()
        ->assertJson(['message' => 'Recurso no encontrado.']);
});

it('actualiza una categoría y permite conservar su propio nombre', function () {
    $category = Category::factory()->create(['name' => 'Vieja']);

    $this->putJson("/api/categories/{$category->id}", ['name' => 'Vieja', 'description' => 'Nueva descripción'])
        ->assertOk()
        ->assertJsonPath('data.description', 'Nueva descripción');
});

it('no permite renombrar a un nombre ya usado', function () {
    Category::factory()->create(['name' => 'Ocupado']);
    $category = Category::factory()->create();

    $this->putJson("/api/categories/{$category->id}", ['name' => 'Ocupado'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('elimina una categoría vacía', function () {
    $category = Category::factory()->create();

    $this->deleteJson("/api/categories/{$category->id}")->assertNoContent();

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

it('responde 409 al eliminar una categoría con productos', function () {
    $category = Category::factory()->has(Product::factory())->create();

    $this->deleteJson("/api/categories/{$category->id}")->assertStatus(409);

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});
