# Laravel Product Admin

Product management app built with Laravel 13 and MySQL. It has an admin dashboard (Blade + Tailwind) and a REST API (Sanctum). Only admin users can access either one.

## Requirements

- PHP 8.3+
- Composer
- MySQL
- Node.js + npm

## Setup

```bash
git clone https://github.com/AsrafAbdiah/laravel-product-admin.git
cd laravel-product-admin

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Create a MySQL database named `laravel_product_admin` (or change `DB_*` in `.env`), then:

```bash
php artisan migrate --seed
npm run build
php artisan l5-swagger:generate
php artisan serve
```

Open http://localhost:8000

**Login:** `admin@example.com` / `password`

A non-admin account (`user@example.com` / `password`) is also seeded for testing access control.

## API

Get a token first:

```
POST /api/login
{ "email": "admin@example.com", "password": "password" }
```

Then send `Authorization: Bearer <token>` and `Accept: application/json` with each request.

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/login` | Login, returns token |
| POST | `/api/logout` | Logout |
| GET | `/api/me` | Current user |
| GET | `/api/categories` | List categories |
| GET | `/api/products` | List products. Filters: `status`, `category_id`, `search`, `low_stock`, `per_page`. Sort: `sort=price` / `sort=-price` (also `name`, `stock`, `created_at`) |
| POST | `/api/products` | Create product |
| GET | `/api/products/{id}` | Product detail |
| PUT | `/api/products/{id}` | Update product |
| DELETE | `/api/products/{id}` | Delete product (soft delete) |
| DELETE | `/api/products/bulk` | Bulk delete, body: `{ "ids": [1, 2] }` |
| GET | `/api/products/export` | Export to Excel |

Swagger docs: http://localhost:8000/api/documentation

## Tests

```bash
php artisan test
```

Tests use an in-memory SQLite database.

## Design choices / assumptions

- Admin access is an `is_admin` flag on users, checked by an `admin` middleware. It's not mass-assignable.
- `ProductService` holds the product logic so the API and dashboard controllers can share it.
- Validation is done with Form Requests, and API output with API Resources.
- Products use soft deletes. Bulk delete runs as a single query.
- The category is eager loaded to avoid N+1 queries, and lists are paginated (max 100 per page).
- Excel export uses Laravel-Excel's `FromQuery`, so large exports are processed in chunks.
- Price is stored as `DECIMAL(10,2)` and returned as a string to avoid rounding issues.
- The login endpoint is rate limited (5 attempts per minute).
- Categories are seeded, since category management wasn't part of the requirements.
- Developed on MariaDB (XAMPP), which is MySQL-compatible.
- Sorting only accepts whitelisted fields (validated in the Form Request), so user input never goes straight into `orderBy`.
