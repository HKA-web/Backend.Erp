# 🚀 ERP Backend – Arsitektur Modular Enterprise

<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework"></a>
</p>

---

Backend modular kelas enterprise berbasis **Laravel 13 + PostgreSQL** dengan pendekatan:

- Master–Draft Pattern
- Database-driven business logic
- Stored Procedure & Trigger
- Modular Architecture
- AI Ready
- Siap audit & workflow kompleks

---

# 📚 Daftar Isi

- [Ringkasan](#-ringkasan)
- [Fitur](#-fitur)
- [Instalasi](#-instalasi)
- [API Documentation](#-api-documentation)
- [Multi-Tenancy SaaS](#-multi-tenancy-saas-stancl-tenancy)
- [AI Integration](#-ai-integration-laravel-ai)
- [Membuat Modul](#-membuat-modul)
- [Permission](#-manajemen-permission)
- [Arsitektur](#-arsitektur)
- [Alur Kerja](#-alur-kerja)
- [Struktur API](#-struktur-api)
- [Database Flow](#-database-flow)
- [Cache & Redis](#-cache--redis)
- [Temporary Schema](#-temporary-schema-3-level)
- [Lifecycle Status](#-lifecycle-status)
- [Prinsip Desain](#-prinsip-desain)
- [Use Case](#-use-case-nyata)
- [Cocok Untuk](#-cocok-untuk)

---

# 🧭 Ringkasan

Proyek ini menggunakan konsep modular enterprise dengan pendekatan:

- Master (Posted Data)
- Draft (Workspace)
- Business logic di database
- AI-assisted workflow

Laravel berperan sebagai:

- Validator
- Orchestrator
- API Response Layer
- AI Gateway
- Queue Dispatcher

---

# ✨ Fitur

✅ Laravel 13  
✅ Modular Architecture  
✅ PostgreSQL Optimized  
✅ Stored Procedure Driven  
✅ Draft–Commit Workflow  
✅ Redis Ready  
✅ Queue Ready  
✅ AI Integration Ready  
✅ Multi-user Safe  
✅ Audit Ready  
✅ Enterprise Workflow  
✅ Multi-Tenancy SaaS (Stancl Tenancy v3.10.0)  

---

# 🚀 Instalasi

## Kebutuhan

- PHP 8.3+
- Composer
- Laravel 13.x
- PostgreSQL
- Redis (opsional tapi direkomendasikan)
- Node.js (untuk build assets)

---

## Clone Project

---

## Install Dependency

```bash
composer install
```

---

## Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

---

## Setup Database

Atur koneksi PostgreSQL di `.env`

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=erp
DB_USERNAME=postgres
DB_PASSWORD=password
```

---

## Migrasi Database

```bash
php artisan migrate
```

---

## Seeder

```bash
php artisan module:seed Authentication
php artisan module:seed Core
```

---

## Jalankan Server

```bash
php artisan serve
```

---

# 📖 API Documentation

Project ini menggunakan Swagger UI untuk dokumentasi API. Dokumentasi di-generate secara otomatis dari routes file di setiap module.

## Generate Documentation

### Generate untuk Semua Module

```bash
php artisan api:docs:generate --all
```

### Generate untuk Module Tertentu

```bash
php artisan api:docs:generate --module={module_name}
```

## Akses Swagger UI

Buka browser dan akses:

```
http://localhost:8000/api/docs
```

## Endpoint Documentation

- **Swagger UI**: `http://localhost:8000/api/docs`
- **JSON All Modules**: `http://localhost:8000/api/docs/json`

## Lokasi File Generated

Dokumentasi JSON di-generate di:

```
Modules/{module_name}/docs/api.json
```

Contoh:
- `Modules/Authentication/docs/api.json`
- `Modules/Core/docs/api.json`

## Parameter Default untuk GET Methods

Semua endpoint GET otomatis memiliki parameter query:

- **take**: Number of records to return (default: 10)
- **skip**: Number of records to skip (default: 0)
- **filter**: Filter criteria (JSON string)
- **expand**: Related resources to expand (comma-separated)
- **fields**: Fields to return (comma-separated)

## Security

Endpoint dengan middleware `auth:sanctum` otomatis ditandai sebagai secured di Swagger UI.

---

# 🏢 Multi-Tenancy SaaS (Stancl Tenancy)

Project ini menggunakan **Stancl Tenancy v3.10.0** untuk sistem multi-tenancy dengan database terpisah per tenant.

## Teknologi Tenancy

- **Package**: Stancl Tenancy v3.10.0
- **Architecture**: Multi-database tenancy (database terpisah per tenant)
- **Tenant Model**: `Modules\Core\Models\Company` (mengimplement `TenantWithDatabase`)
- **Tenant Identifier**: `company_id` (UUID)
- **Database Manager**: PostgreSQLDatabaseManager
- **Bootstrappers**: DatabaseTenancyBootstrapper (untuk switch database connection)

## Fitur Multi-Tenancy

✅ Database terpisah per tenant  
✅ Tenant identifier menggunakan `company_id` (UUID)  
✅ Auto-scan migration & seeder tenant  
✅ Tenant-specific commands  
✅ PostgreSQL compatible  

## Struktur Database

- **Database Central**: Menyimpan data tenant (company) dan data global
- **Database Tenant**: Database terpisah per tenant dengan nama `db_{company_id}`
- **Schema Tenant**: Menggunakan schema `core` untuk tabel tenant

## Tenant Identifier

Tenant diidentifikasi menggunakan `company_id` (UUID) dari tabel `core.company`.

Contoh:
- Company ID: `a7c50a73-f6a2-4869-9635-d66d075ba075`
- Database Name: `db_a7c50a73-f6a2-4869-9635-d66d075ba075`

## Command Tenant

### List Semua Tenant

```bash
php artisan tenants:list
```

### Migrate Tenant

```bash
# Migrate semua tenant
php artisan tenants:migrate

# Migrate tenant spesifik (gunakan company_id)
php artisan tenants:migrate --tenants=a7c50a73-f6a2-4869-9635-d66d075ba075

# Preview migration (tanpa eksekusi)
php artisan tenants:migrate --pretend
```

### Rollback Migration

```bash
# Rollback semua tenant
php artisan tenants:rollback

# Rollback tenant spesifik
php artisan tenants:rollback --tenants=a7c50a73-f6a2-4869-9635-d66d075ba075

# Rollback dengan step
php artisan tenants:rollback --step=1
```

### Seed Tenant

```bash
# Seed semua tenant
php artisan tenants:seed

# Seed tenant spesifik
php artisan tenants:seed --tenants=a7c50a73-f6a2-4869-9635-d66d075ba075
```

### Migrate Fresh (Drop & Re-migrate)

```bash
php artisan tenants:migrate-fresh
```

## Struktur File Tenant

### Migration Tenant

Migration tenant diletakkan di:
- `Modules/Core/database/migrations/tenant/` (module)
- `database/migrations/tenant/` (root)

Contoh migration:
```php
// Modules/Core/database/migrations/tenant/2026_05_26_000000_create_demo_tables.php
DB::statement('CREATE SCHEMA IF NOT EXISTS core');

Schema::create('core.province', function (Blueprint $table) {
    $table->string('province_id', 50)->primary();
    $table->string('province_name', 100);
    $table->timestamps();
});
```

### Seeder Tenant

Seeder tenant diletakkan di:
- `Modules/Core/database/seeders/tenant/` (module)
- `Modules/Core/database/seeders/TenantDatabaseSeeder.php` (main seeder)

TenantDatabaseSeeder otomatis scan folder tenant dan menjalankan semua seeder yang ditemukan.

Contoh seeder:
```php
// Modules/Core/database/seeders/tenant/ProvinceSeeder.php
namespace Modules\Core\Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('core.province')->insert([
            ['province_id' => 'ID-JK', 'province_name' => 'DKI Jakarta'],
        ]);
    }
}
```

**Catatan Penting:**
- Nama file seeder harus sama dengan nama class
- Auto scan menggunakan nama file untuk menentukan class name
- Contoh: `ProvinceSeeder.php` → class `ProvinceSeeder`

## Konfigurasi

Konfigurasi tenancy ada di `config/tenancy.php`:

```php
'tenant_model' => Modules\Core\Models\Company::class,

'migration_parameters' => [
    '--path' => [
        database_path('migrations/tenant'),
        base_path('Modules/Core/database/migrations/tenant'),
    ],
    '--realpath' => true,
],

'seeder_parameters' => [
    '--class' => 'Modules\\Core\\Database\\Seeders\\TenantDatabaseSeeder',
    '--force' => true,
],
```

## Model Tenant

Model `Company` mengimplement `TenantWithDatabase` dan override method tenant:

```php
class Company extends Tenant implements TenantWithDatabase
{
    use HasDatabase;

    public function getTenantKeyName(): string
    {
        return 'company_id';
    }

    public function getTenantKey()
    {
        return $this->company_id;
    }
}
```

## Use Case SaaS

Sistem ini cocok untuk:
- SaaS multi-tenant
- ERP perusahaan
- Aplikasi dengan data terisolasi per client
- Sistem dengan kebutuhan data security tinggi

---

# 🤖 AI Integration (Laravel AI)

Project ini mendukung Laravel AI official integration.

Dokumentasi resmi:

- https://laravel.com/docs/13.x/ai

---

## Install Laravel AI

```bash
composer require laravel/ai
```

---

## Publish Config

```bash
php artisan vendor:publish --tag=ai-config
```

---

## Setup AI Provider

Contoh OpenAI:

```env
OPENAI_API_KEY=sk-xxxx
```

---

## Contoh Penggunaan AI

```php
use Illuminate\Support\Facades\AI;

$response = AI::prompt(
    'Buat ringkasan transaksi hari ini'
);

return $response->text();
```

---

## Contoh Endpoint AI

```php
Route::post('/v1/ai/chat', function (Request $request) {

    $response = AI::prompt($request->message);

    return response()->json([
        'message' => $response->text()
    ]);

});
```

---

## AI Use Cases

- AI Dashboard Summary
- AI Transaction Insight
- AI OCR Invoice
- AI Chat Assistant
- AI Reporting
- AI Workflow Assistant
- AI Knowledge Base

---

# 🛠 Membuat Modul

## Generate Module

```bash
php artisan erp:make-module {module}
```

---

## Generate Model

```bash
php artisan erp:make-model {model} {module}
```

---

## Migrasi Module

```bash
php artisan module:migrate {module}
```

---

## Seeder Module

```bash
php artisan module:seed {module}
```

---

# 🔐 Manajemen Permission

Menggunakan Spatie Laravel Permission

```php
$user->assignRole('admin');

$role->givePermissionTo('edit-user');
```

---

# 🏗 Arsitektur

## Master–Draft Pattern

- Data Master = immutable
- Tidak ada edit langsung
- Semua perubahan melalui Draft
- Commit melalui stored procedure

---

## Karakteristik

- Audit-ready
- Mendukung approval workflow
- Aman untuk multi-user
- Business logic di database
- Minim business logic di controller

---

# 🔄 Alur Kerja

# Flow API

Endpoint:

```http
POST /store
```

---

## Laravel Layer

Laravel melakukan:

- Validasi
- Authentication
- Authorization
- Simpan temporary data
- Call procedure
- Return JSON

---

## Database Layer

Stored Procedure:

```sql
CALL core.procedure_commit();
```

---

## Trigger Database

Database akan:

- Hitung saldo
- Generate jurnal
- Update stok
- Generate audit log
- Validasi relational integrity

---

## Result

```text
Database -> OK
Laravel -> JSON Response
```

---

# 📦 Contoh Modul

| Komponen     | Nama                      |
| ------------ | ------------------------- |
| Module       | Core                      |
| Model        | Dictionary                |
| Master Table | core.dictionary           |
| Temp Table   | temporary.core_dictionary |

---

# 🏛 Struktur API

# Master (Posted)

| Method | Endpoint                        |
| ------ | ------------------------------- |
| GET    | /v1/core/dictionary             |
| GET    | /v1/core/dictionary/{id}        |
| POST   | /v1/core/dictionary/{id}/revise |
| DELETE | /v1/core/dictionary/{id}        |

---

# Draft

| Method | Endpoint                               |
| ------ | -------------------------------------- |
| GET    | /v1/core/dictionary-drafts             |
| POST   | /v1/core/dictionary-drafts             |
| GET    | /v1/core/dictionary-drafts/{id}        |
| PUT    | /v1/core/dictionary-drafts/{id}        |
| DELETE | /v1/core/dictionary-drafts/{id}        |
| POST   | /v1/core/dictionary-drafts/{id}/commit |

---

# 🧠 Database Flow

# Edit Flow

1. Data master dikunci
2. Data disalin ke draft
3. User edit di workspace
4. Commit menjalankan procedure
5. Trigger melakukan business logic

---

# Delete Flow

1. Tandai delete di draft
2. Commit
3. Database handle relational cleanup

---

# 🧹 Cache & Redis

# Fungsi Redis

Digunakan untuk:

- Cache
- Queue
- Session
- Permission cache
- AI response cache

---

# Clear Cache Laravel

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

# Clear Semua Cache

```bash
php artisan optimize:clear
```

---

# Cache Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

# Reset Permission Cache

```bash
php artisan permission:cache-reset
```

---

# Redis Flush

```bash
php artisan redis:flush
```

atau:

```bash
redis-cli FLUSHALL
```

⚠️ Akan menghapus:

- cache
- session
- queue

---

# 🏁 Lifecycle Status

Contoh status workflow:

- Draft
- Submitted
- Approved
- Posted
- Rejected
- Deleted

---

# 🗂 Temporary Schema (3 Level)

# Level 1 – Orders

`temporary.sales_orders`

- temporary_id
- session_id
- master_id
- temporary_option

---

# Level 2 – Items

`temporary.sales_order_items`

- parent_temporary_id
- qty
- product_id

---

# Level 3 – Taxes

`temporary.sales_order_item_taxes`

- parent_temporary_id
- tax_percent

---

# Relasi

```text
orders
 └── items
      └── taxes
```

---

# 🧠 Prinsip Desain

## temporary_id

ID unik temporary workspace

---

## session_id

Isolasi workspace user

---

## master_id

Referensi data utama

---

## parent_temporary_id

Relasi antar level temporary

---

## temporary_option

| Value | Meaning |
| ----- | ------- |
| I     | Insert  |
| U     | Update  |
| D     | Delete  |

---

# 🎯 Kenapa Arsitektur Ini?

✅ Tidak ada edit langsung  
✅ Audit ready  
✅ Support approval workflow  
✅ Multi-user safe  
✅ Flexible tanpa redeploy  
✅ AI Ready  
✅ Enterprise scalable  

---

# 💼 Use Case Nyata

Jika ada perubahan rule:

> “Simpan Village → otomatis buat Region”

Tidak perlu:

- ubah controller
- ubah service
- deploy ulang

Cukup:

1. Update stored procedure
2. Selesai