# Iligan Hydraulics & Auto Parts POS

A Laravel 13 point-of-sale and inventory system built for a hydraulics and auto parts business — barcode-ready POS, stock ledger, sales history, reports, product import, and database backup/restore.

## Stack

- Laravel 13 + Breeze (Blade) + Tailwind CSS + Alpine.js
- MySQL
- `maatwebsite/excel` for import/export
- `picqer/php-barcode-generator` for barcode images (EAN-13 / Code128)
- `spatie/laravel-backup` for database backups

## Setup

```bash
composer install
npm install && npm run build
cp .env.example .env   # then set DB_* and POS_* values
php artisan key:generate
php artisan migrate --seed
```

Default logins (password: `password`):

| Role    | Email                            |
|---------|-----------------------------------|
| Admin   | admin@iliganhydraulics.test      |
| Manager | manager@iliganhydraulics.test    |
| Cashier | cashier@iliganhydraulics.test    |

## Features

- **POS** (`/pos`) — barcode scan (USB scanner types + Enter) or product search, cart, discounts, tax, multiple payment methods, receipt printing.
- **Inventory** — products with part number, OEM number, fitment/compatible vehicles, brand, category, supplier, shelf location, barcode image generation. Stock is a signed-quantity ledger (`stock_movements`); every change (sale, purchase, adjustment, wastage, return, opening) is recorded via `Product::recordMovement()`.
- **Sales History** — searchable/filterable, printable invoice, void with automatic stock return (manager/admin only).
- **Reports** — Sales, Inventory Movements, Product Inventory, each with Excel export.
- **Import** — downloadable Excel template (with instructions sheet) and importer that matches existing products by barcode → SKU → name; auto-creates category/brand/supplier.
- **Settings** — company info, currency, tax rate, low-stock default, receipt footer (manager/admin only).
- **Backup & Restore** (`/settings/backup`, manager/admin only) — one-click DB backup (zip via spatie/laravel-backup), download/delete existing backups, and restore from an existing backup or an uploaded `.zip`/`.sql` file. Restore overwrites the database and requires an explicit confirmation checkbox.
- **Users** (admin only) — manage cashier/manager/admin accounts.

## Notes

- `DB_DUMP_BINARY_PATH` in `.env` must point to the directory containing `mysqldump`/`mysql` binaries (needed for backup/restore).
- Backup & restore invoke `mysqldump`/`mysql` as subprocesses. This fails under PHP's built-in `php artisan serve` on Windows (a known OS-level limitation with that dev server spawning networked subprocesses) — it works normally under Apache/Nginx + PHP-FPM (e.g. Laragon's default setup) and via CLI (`php artisan backup:run`).
