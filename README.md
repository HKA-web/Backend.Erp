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

Backend modular kelas enterprise berbasis **Laravel + PostgreSQL** dengan pendekatan:

- Master–Draft Pattern
- Database-driven business logic
- Stored Procedure & Trigger
- Siap audit & workflow kompleks

---

# 📚 Daftar Isi

- Ringkasan
- Instalasi
- Membuat Modul
- Permission
- Arsitektur
- Alur Kerja
- Struktur API
- Database Flow
- Cache & Redis
- Temporary Schema
- Kenapa Arsitektur Ini

---

# 🧭 Ringkasan

Proyek ini menggunakan konsep modular dengan pendekatan:

- Master (Posted Data)
- Draft (Workspace)
- Business logic di database

Laravel hanya berperan sebagai:

- Validator
- Orchestrator
- API Response Layer

---

# 🚀 Instalasi

## Kebutuhan

- PHP 8.2+
- Composer
- Laravel 12.x
- PostgreSQL
- Redis (opsional tapi direkomendasikan)

---

## Setup

```bash
git clone https://github.com/HKA-web/Backend.Erp.git project
cd project
```

```bash
composer install
```

```bash
cp .env.example .env
php artisan key:generate
```

```bash
php artisan migrate
```

```bash
php artisan module:seed Authentication
php artisan module:seed Core
```

```bash
php artisan serve
```

---

# 🛠 Membuat Modul

```bash
php artisan erp:make-module {module}
```

```bash
php artisan erp:make-model {model} {module}
```

```bash
php artisan module:migrate {module}
```

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

- Data Master = immutable (tidak bisa diedit langsung)
- Semua perubahan melalui Draft
- Commit melalui stored procedure

---

## Karakteristik

- Tidak ada edit langsung ke Master
- Audit-ready
- Mendukung approval
- Aman untuk multi-user
- Business logic di database

---

# 🔄 Alur Kerja

## Flow API

Endpoint:

```
POST /store
```

### Laravel

- Validasi
- Simpan ke temporary table
- Call procedure

```sql
CALL core.procedure_commit();
```

---

### Database

Trigger akan:

- Hitung saldo
- Generate jurnal
- Update stok

---

### Result

- DB → OK
- Laravel → JSON Response

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

## Master (Posted)

| Method | Endpoint                        |
| ------ | ------------------------------- |
| GET    | /v1/core/dictionary             |
| GET    | /v1/core/dictionary/{id}        |
| POST   | /v1/core/dictionary/{id}/revise |
| DELETE | /v1/core/dictionary/{id}        |

---

## Draft

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

## Edit Flow

1. Data Master dikunci
2. Disalin ke Draft
3. User edit di Draft
4. Commit → Procedure jalan

---

## Delete Flow

1. Tandai delete di Draft
2. Commit
3. Database handle logic

---

# 🧹 Cache & Redis

## Kenapa penting?

Digunakan untuk:

- Cache
- Session
- Queue
- Permission cache

---

## Clear Cache Laravel

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Clear Semua Cache (Rekomendasi)

```bash
php artisan optimize:clear
```

---

## Cache Ulang (Production)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Clear Permission Cache

```bash
php artisan permission:cache-reset
```

---

## Redis Clear (Safe)

```bash
php artisan cache:clear
```

---

## Redis Flush (Danger)

```bash
php artisan redis:flush
```

atau:

```bash
redis-cli FLUSHALL
```

⚠️ Akan menghapus semua:

- cache
- session
- queue

---

# 🏁 Lifecycle Status

Contoh:

- Draft
- Submitted
- Approved
- Posted
- Deleted

---

# 🗂 Temporary Schema (3 Level)

## Level 1 – Orders

`temporary.sales_orders`

- temporary_id
- session_id
- master_id
- temporary_option

---

## Level 2 – Items

`temporary.sales_order_items`

- parent_temporary_id → orders
- qty
- product_id

---

## Level 3 – Taxes

`temporary.sales_order_item_taxes`

- parent_temporary_id → items
- tax_percent

---

## Relasi

```
orders
 └── items
      └── taxes
```

---

# 🧠 Prinsip Desain

### temporary_id

ID unik di temporary

### session_id

Isolasi user

### master_id

Referensi ke data asli

### parent_temporary_id

Relasi antar level

### temporary_option

| Value | Meaning |
| ----- | ------- |
| I     | Insert  |
| U     | Update  |
| D     | Delete  |

---

# 🎯 Kenapa Arsitektur Ini?

✅ Tidak ada edit langsung
✅ Audit ready
✅ Support workflow
✅ Multi-user safe
✅ Flexible tanpa redeploy

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

---

# 🏢 Cocok Untuk

- ERP System
- Enterprise App
- Finance System
- Multi-user transactional system

---
