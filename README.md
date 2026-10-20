# Product Admin: Laravel Product CRUD

A product management system built with **Laravel 13** and **MySQL**. It has two parts:

1. **Admin dashboard** (Blade + Tailwind CSS + Alpine.js) for managing products in the browser.
2. **RESTful API** secured with **Laravel Sanctum** and documented with **Swagger (OpenAPI 3)**.

Only authenticated **admin** users can use either one.

---

## Features

| Area | What's included |
|---|---|
| Products | Create, read, update, soft delete, **bulk delete**, **export to Excel** |
| Listing | Pagination, filter by **status** (enabled/disabled), filter by **category**, search by name |
| Categories | `Category` model with a **one-to-many** relationship to `Product`; API responses include `category_id` and `category { id, name }` |
| Security | Sanctum token auth (API), session auth (dashboard), `admin` middleware, rate-limited login, CSRF protection, mass-assignment protection |
| Validation | Laravel **Form Requests** for every write and for list filters |
| Docs | Swagger UI at `/api/documentation` |
| Tests | 37 PHPUnit tests (feature + unit) |

---

## Requirements

- PHP **8.3+** (with `pdo_mysql`, `mbstring`, `zip`, `gd`, `fileinfo`, `intl`; `pdo_sqlite` for tests)
- Composer 2
- MySQL 8 / MariaDB 10.4+
- Node.js 20+ and npm

---

## Setup

```bash
# 1. Clone and install dependencies
git clone https://github.com/AsrafAbdiah/laravel-product-admin.git
cd laravel-product-admin
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate
```

Create an empty MySQL database, then update the `DB_*` values in `.env` if needed (defaults: database `laravel_product_admin`, user `root`, no password).

```bash
# 3. Create tables and demo data
php artisan migrate --seed

# 4. Build frontend assets
npm run build

# 5. Generate the API documentation
php artisan l5-swagger:generate

# 6. Run the app
php artisan serve
```

Open **http://localhost:8000**.

### Demo accounts (created by the seeder)

| Role | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `password` |
| Regular user (no access) | `user@example.com` | `password` |

The regular user exists so you can check that non-admins are blocked (403 on the API, login refused on the dashboard).

---

## API

Base URL: `http://localhost:8000/api`. Every request should send `Accept: application/json`.

### Authentication

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

The response contains a `token`. Send it on every other request:

```
Authorization: Bearer <token>
```

### Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/login` | Log in (admin only) and get a token. Rate limited to 5/min |
| POST | `/api/logout` | Revoke the current token |
| GET | `/api/me` | Current admin user |
| GET | `/api/categories` | List categories |
| GET | `/api/products` | List products (paginated). Query: `status`, `category_id`, `search`, `per_page` (max 100), `page` |
| POST | `/api/products` | Create a product |
| GET | `/api/products/{id}` | Product detail |
| PUT/PATCH | `/api/products/{id}` | Update a product (partial updates allowed) |
| DELETE | `/api/products/{id}` | Soft delete a product |
| DELETE | `/api/products/bulk` | Bulk soft delete. Body: `{ "ids": [1, 2, 3] }` (max 100) |
| GET | `/api/products/export` | Download `.xlsx`. Accepts the same filters as the list |

### Example product response

```json
{
  "data": {
    "id": 51,
    "name": "Wireless Mouse",
    "description": "Ergonomic mouse",
    "price": "49.90",
    "stock": 10,
    "enabled": true,
    "category_id": 1,
    "category": { "id": 1, "name": "Electronics" },
    "created_at": "2026-10-20T11:56:14+00:00",
    "updated_at": "2026-10-20T11:56:14+00:00"
  }
}
```

### Status codes

| Code | Meaning |
|---|---|
| 200 / 201 | Success / created |
| 401 | Missing or invalid token |
| 403 | Logged in, but not an admin |
| 404 | Product not found (or soft deleted) |
| 422 | Validation failed (`errors` object lists each field) |
| 429 | Too many login attempts |

### Swagger

Interactive docs are at **http://localhost:8000/api/documentation**. To try endpoints:

1. Call `POST /api/login`.
2. Click **Authorize** and paste the token.
3. Use "Try it out" on any endpoint.

After changing the API annotations, regenerate with `php artisan l5-swagger:generate`.

---

## Admin dashboard

Log in at `/login` with the admin account.

- **Products list:** search, filter by category and status, paginate, select rows (or the whole page) for **bulk delete**, and **Export to Excel**. The export respects the current filters.
- **Create / Edit:** a shared form with server-side validation messages.
- **Detail page:** shows all fields including the category, with Edit and Delete actions.

---

## Running tests

```bash
php artisan test
```

Tests use an in-memory SQLite database (see `phpunit.xml`), so they don't touch your MySQL data. They cover:

- API auth: login, wrong password, non-admin blocked, guest gets 401, logout, rate limiting
- Product API: create, validation, pagination, status/category/search filters, detail with category, update, soft delete, bulk delete, Excel export
- Dashboard: login, access control, filtering, create, validation errors, edit, delete, bulk delete
- Unit: relationships, query scopes, soft delete behaviour, price casting

---

## Project structure

```
app/
├── Exports/ProductsExport.php          Laravel-Excel export (FromQuery, chunked)
├── Http/
│   ├── Controllers/
│   │   ├── Api/                        AuthController, ProductController, CategoryController
│   │   ├── Auth/LoginController.php    Dashboard session login
│   │   └── Web/ProductController.php   Dashboard pages
│   ├── Middleware/EnsureUserIsAdmin.php
│   ├── Requests/                       Form Request validation classes
│   └── Resources/                      API Resources (JSON shape)
├── Models/                             User, Category, Product
├── OpenApi/Schemas.php                 Shared Swagger schemas
└── Services/ProductService.php         Product logic shared by API and dashboard
```

---

## Design choices and assumptions

**Architecture**
- **MVC with a small service layer.** The API and the dashboard need the same filtering, pagination and delete logic. Instead of duplicating it in two controllers, `ProductService` holds it and both controllers stay thin. I kept it to one service on purpose: a repository layer on top of Eloquent would add indirection without benefit at this size.
- **Form Requests** handle all validation, so controllers only deal with already-valid data. `UpdateProductRequest` extends the store rules and makes every field optional, so `PUT` and `PATCH` both work.
- **API Resources** control the JSON shape, so database columns are never exposed by accident.

**Security**
- **Sanctum personal access tokens** for the API: simple, first-party, and a good fit for an admin client or Postman/Swagger. The dashboard uses normal session auth with CSRF protection.
- **Admin-only access** uses an `is_admin` flag checked by the `admin` middleware on every protected route. Non-admins can't get an API token at all. `is_admin` is **not** mass-assignable, so it can't be set through a request.
- **Login is rate limited** (5 attempts per minute) and the session is regenerated after login to prevent session fixation.
- API 404s return a generic message instead of internal model class names.

**Performance**
- The category is **eager loaded** with only the needed columns (`with('category:id,name')`), which avoids N+1 queries.
- **Indexes** on `products.enabled` and `products.category_id` (from the foreign key) support the filters.
- Lists are always **paginated**, and `per_page` is capped at 100.
- **Bulk delete** runs one `UPDATE ... WHERE id IN (...)` query instead of one query per product.
- The **Excel export** uses `FromQuery`, so Laravel-Excel reads rows in chunks rather than loading the whole table into memory.

**Data**
- **Soft deletes:** deleted products keep their data (`deleted_at` is set) and disappear from all lists, details and exports. Bulk delete only accepts ids that exist and aren't already deleted.
- **Price** is stored as `DECIMAL(10,2)` and returned as a string (`"49.90"`) to avoid floating-point rounding errors with money. Validation allows at most 2 decimal places.
- A category can't be deleted while it still has products (`restrictOnDelete`). Category management wasn't in the scope, so categories are seeded.
- New products default to **enabled** if `enabled` isn't sent.
- The currency is shown as RM (Malaysian Ringgit) in the dashboard.

**Assumptions**
- "Admin" is a flag on the users table rather than a full roles/permissions system, since the brief only needs one role. It could be swapped for `spatie/laravel-permission` if more roles are needed.
- There is no public registration. Admin accounts are created by the seeder (or `php artisan tinker`).
- Developed against MariaDB 10.4 (XAMPP), which is MySQL-compatible. Nothing MariaDB-specific is used.
