# StockPilot API

API REST de inventario hecha con **Laravel**, **Sanctum** y **PostgreSQL**. Gestiona categorías, productos y movimientos de stock con autenticación por token.

## Qué incluye

- Autenticación con tokens (Laravel Sanctum) y límite de intentos en el login.
- CRUD de categorías y productos con Form Requests y API Resources.
- Filtros (categoría, búsqueda, stock bajo, rango de precio) y paginación.
- Movimientos de stock con transacción y bloqueo de fila (`lockForUpdate`): una salida mayor al stock disponible se rechaza con 422.
- Reporte de valorización del inventario por categoría.
- Errores siempre en JSON (401, 404, 409, 422).
- Pruebas automatizadas con Pest para cada endpoint.

## Requisitos

Docker y Docker Compose. Para correr sin Docker: PHP 8.3+, Composer y PostgreSQL.

## Inicio rápido

```bash
git clone <url-del-repositorio>
cd stockpilot-api
docker compose up -d --build
docker compose exec app php artisan db:seed   # opcional: datos de prueba
```

La API queda en `http://localhost:8000/api`. Usuario de prueba tras el seeder: `admin@stockpilot.test` / `password`.

## Sin Docker

```bash
composer install
cp .env.example .env
php artisan key:generate
# Ajusta DB_* en .env y crea la base de datos
php artisan migrate --seed
php artisan serve
```

## Pruebas

Usan una base separada (`stockpilot_test`) para no tocar tus datos.

```bash
docker compose exec app php artisan test
```

## Autenticación

Envía el token en cada petición protegida:

```
Authorization: Bearer <token>
Accept: application/json
```

## Endpoints

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/api/register` | Crea un usuario y devuelve un token |
| POST | `/api/login` | Devuelve un token (máx. 5 intentos por minuto) |
| POST | `/api/logout` | Revoca el token actual |
| GET | `/api/me` | Usuario autenticado |
| GET, POST | `/api/categories` | Listar (paginado) y crear |
| GET, PUT, DELETE | `/api/categories/{id}` | Ver, actualizar y eliminar (409 si tiene productos) |
| GET, POST | `/api/products` | Listar con filtros y crear |
| GET, PUT, DELETE | `/api/products/{id}` | Ver, actualizar y eliminar |
| GET, POST | `/api/products/{id}/movements` | Historial y registro de movimientos |
| GET | `/api/reports/stock-valuation` | Unidades y valor del stock por categoría |

### Filtros de `GET /api/products`

`category_id`, `search` (nombre o SKU), `low_stock=1`, `min_price`, `max_price`, `per_page` (1 a 100) y `page`.

### Filtros de `GET /api/products/{id}/movements`

`type` (`in` o `out`), `from`, `to` (fechas) y `per_page`.

### Ejemplo

```bash
curl -X POST http://localhost:8000/api/products/1/movements \
  -H "Authorization: Bearer <token>" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"type": "out", "quantity": 3, "reason": "Venta"}'
```

## Formato de errores

| Código | Cuándo |
|---|---|
| 401 | Falta el token o es inválido |
| 404 | El recurso no existe |
| 409 | Conflicto, por ejemplo borrar una categoría con productos |
| 422 | Validación fallida o stock insuficiente |

```json
{
  "message": "Stock insuficiente: disponible 3, solicitado 4.",
  "errors": { "quantity": ["Stock insuficiente: disponible 3, solicitado 4."] }
}
```

## Decisiones de diseño

- **El stock solo cambia mediante movimientos.** `PUT /products/{id}` ignora `stock`, así el historial siempre cuadra con el stock real.
- **Transacción y `lockForUpdate`** en `StockService`: evita que dos salidas simultáneas dejen el stock negativo.
- **`unsignedInteger` en `stock`**: la base de datos también impide valores negativos.
- **`restrictOnDelete` en categorías**: no se pueden dejar productos huérfanos.
- **Búsqueda con `ilike`**, propia de PostgreSQL, sin distinguir mayúsculas.
