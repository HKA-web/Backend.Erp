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

- Ringkasan
- Fitur
- Instalasi
- AI Integration
- Membuat Modul
- Permission
- Arsitektur
- Alur Kerja
- Struktur API
- Database Flow
- Cache & Redis
- Temporary Schema
- Lifecycle Status
- Prinsip Desain
- Use Case
- Cocok Untuk

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

```bash
git clone https://github.com/HKA-web/Backend.Erp.git project
cd project
```

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