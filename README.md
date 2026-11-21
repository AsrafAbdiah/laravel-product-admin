# Laravel Product Admin

Product and stock management app built with Laravel 13 and MySQL. It has an admin dashboard (Blade + Tailwind) and a REST API (Sanctum). Only admin users can access either one.

## Features

- **Dashboard:** product totals, low-stock alerts, inventory value, products per category and recent stock changes
- **Products:** create, edit, view, delete, with an image per product
- **Search, filter and sort:** by status, category, low stock, name, price, stock or date, with pagination
- **Bulk delete** and a **trash** where deleted products can be restored or deleted forever
- **Stock history:** every stock change is logged with who made it and why
- **Excel/CSV import** (with per-row error reporting) and **Excel export**
- **Categories:** create, rename and delete (blocked while products still use them)
- **REST API** for all of the above, documented with Swagger

## Requirements

- PHP 8.3+ (with the `gd`, `zip`, `pdo_mysql` and `fileinfo` extensions)
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
php artisan storage:link
npm run build
php artisan l5-swagger:generate
php artisan serve
```

Open http://localhost:8000

**Login:** `admin@example.com` / `password`

A non-admin account (`user@example.com` / `password`) is also seeded for testing access control. The seeder adds around 30 realistic products with two weeks of stock history, so every page has data to show.

`storage:link` makes uploaded product images reachable at `/storage/...`.

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
| GET | `/api/dashboard` | Totals, products per category, low-stock products, recent stock changes |
| GET | `/api/products` | List products. Filters: `status`, `category_id`, `search`, `low_stock`, `per_page`. Sort: `sort=price` / `sort=-price` (also `name`, `stock`, `created_at`) |
| POST | `/api/products` | Create product (JSON, or multipart with an `image`) |
| GET | `/api/products/{id}` | Product detail |
| PUT | `/api/products/{id}` | Update product. Send `remove_image=1` to clear the image. To upload a new image, send `POST` with `_method=PUT` as multipart |
| DELETE | `/api/products/{id}` | Move product to the trash (soft delete) |
| DELETE | `/api/products/bulk` | Bulk delete, body: `{ "ids": [1, 2] }` |
| GET | `/api/products/export` | Export to Excel (same filters as the list) |
| POST | `/api/products/import` | Import from `.xlsx` or `.csv` (multipart, field `file`) |
| GET | `/api/products/{id}/stock-movements` | Stock history for a product |
| GET | `/api/products/trash` | Products in the trash |
| PATCH | `/api/products/{id}/restore` | Restore from the trash |
| DELETE | `/api/products/{id}/force` | Permanently delete (product must be in the trash) |
| GET/POST | `/api/categories` | List (with product counts) / create categories |
| GET/PUT/DELETE | `/api/categories/{id}` | View / rename / delete a category |

Swagger docs: http://localhost:8000/api/documentation

### Import file format

First row is headings: **Name, Category, Description, Price, Stock, Status**.

- **Category** must be an existing category name (not case-sensitive).
- **Status** is `enabled` or `disabled` (blank means enabled).

Valid rows are imported. Rows with problems are skipped and returned with their row number and errors. A file from "Export to Excel" can be imported again; its extra columns are ignored, and the rows are added as new products. The dashboard has a "Download template" link.

## Tests

```bash
php artisan test
```

Tests use an in-memory SQLite database and a fake storage disk, so they never touch real data or files.

## Design choices / assumptions

**Structure**
- `ProductService` holds the product logic so the API and dashboard controllers share it. `DashboardService` holds the dashboard queries.
- Validation is done with Form Requests, and API output with API Resources.

**Security**
- Admin access is an `is_admin` flag on users, checked by an `admin` middleware. It's not mass-assignable.
- The login endpoint is rate limited (5 attempts per minute).
- Sorting only accepts whitelisted fields (validated in the Form Request), so user input never goes straight into `orderBy`.

**Performance**
- The category is eager loaded to avoid N+1 queries, and lists are paginated (max 100 per page).
- The dashboard totals come from a single aggregate query (`COUNT` / `SUM`), so the dashboard costs the same number of queries however many products there are.
- Bulk delete runs as a single query.
- Export (`FromQuery`) and import (`WithChunkReading`) work in chunks, so large files aren't loaded into memory at once.

**Data**
- Price is stored as `DECIMAL(10,2)` and returned as a string to avoid rounding issues.
- Products use soft deletes. "Delete forever" only works on products already in the trash (otherwise 409 Conflict), so nothing is lost with a single click. Images and stock history are removed only when a product is permanently deleted.
- A category can't be deleted while any product uses it, **including products in the trash**, because their rows still exist and the foreign key (`restrictOnDelete`) would block it. The API returns 409 with an explanation instead of a database error.

**Stock history**
- Stock changes are recorded by a model observer (`ProductObserver`), so every path (API, dashboard, import) is covered without each one remembering to log.
- The import labels its changes as `imported` through Laravel's `Context`, scoped to the import call only.

**Images**
- Images are stored on the `public` disk. When an image is replaced or removed, the old file is deleted only after the database update succeeds.

**Exports**
- Exports use `WithStrictNullComparison`; without it Laravel-Excel writes a stock of `0` as an empty cell. A round-trip test (export, then import) guards this.

**Environment**
- Developed on MariaDB (XAMPP), which is MySQL-compatible.
