<?php

use App\Models\User;

it('registra un usuario y devuelve un token', function () {
    $this->postJson('/api/register', [
        'name' => 'Steven',
        'email' => 'steven@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated()
        ->assertJsonStructure(['user' => ['id', 'email'], 'token']);

    $this->assertDatabaseHas('users', ['email' => 'steven@test.com']);
});

it('rechaza un registro inválido', function () {
    User::factory()->create(['email' => 'steven@test.com']);

    $this->postJson('/api/register', [
        'name' => 'Steven',
        'email' => 'steven@test.com',
        'password' => 'password123',
        'password_confirmation' => 'distinta',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('inicia sesión con credenciales correctas', function () {
    $user = User::factory()->create(['password' => 'password123']);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password123'])
        ->assertOk()
        ->assertJsonStructure(['user', 'token']);
});

it('rechaza credenciales incorrectas', function () {
    $user = User::factory()->create(['password' => 'password123']);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'incorrecta'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('devuelve el usuario autenticado en /me', function () {
    $user = User::factory()->create(['password' => 'password123']);
    $token = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password123'])->json('token');

    $this->withToken($token)->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('email', $user->email);
});

it('cierra sesión y revoca el token', function () {
    $user = User::factory()->create(['password' => 'password123']);
    $token = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password123'])->json('token');

    $this->withToken($token)->postJson('/api/logout')->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});
