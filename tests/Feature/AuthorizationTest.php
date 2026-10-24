<?php

it('exige token en las rutas protegidas', function (string $method, string $uri) {
    $this->json($method, $uri)->assertUnauthorized();
})->with([
    ['GET', '/api/me'],
    ['POST', '/api/logout'],
    ['GET', '/api/categories'],
    ['POST', '/api/categories'],
    ['GET', '/api/products'],
    ['POST', '/api/products'],
    ['GET', '/api/products/1/movements'],
    ['POST', '/api/products/1/movements'],
    ['GET', '/api/reports/stock-valuation'],
]);

it('responde 401 en JSON aunque el cliente no envíe Accept', function () {
    $this->get('/api/me')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});
