# 🚀 ERP Backend – Panduan Pemula

<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

---

## 📚 Daftar Isi

1. [Apa Ini](#apa-ini)
2. [Mulai Cepat](#mulai-cepat)
3. [Instalasi Lengkap](#instalasi-lengkap)
4. [API Documentation](#api-documentation)
5. [Laravel Scout - Full-Text Search](#laravel-scout---full-text-search)
6. [Multi-Tenancy](#multi-tenancy)
7. [Struktur Project](#struktur-project)
8. [Konsep Utama](#konsep-utama)
9. [FAQ](#faq)

---

## 🎯 Apa Ini?

ERP Backend adalah sistem modular berbasis **Laravel 13 + PostgreSQL** yang dirancang untuk:

- **SaaS Multi-Tenant** - Satu backend untuk banyak perusahaan
- **Draft-Commit Workflow** - Edit aman dengan approval
- **Business Logic di Database** - Stored procedure untuk performa
- **AI Integration Ready** - Siap untuk fitur AI

### Kenapa Arsitektur Ini?

✅ **Aman** - Data tidak bisa diedit langsung, harus lewat draft  
✅ **Audit Ready** - Semua perubahan tercatat di history  
✅ **Multi-User Safe** - Banyak user bisa bekerja bersamaan  
✅ **Flexible** - Ubah rule bisnis tanpa deploy ulang  
✅ **Enterprise Ready** - Skalabel untuk data besar

---

## 🚀 Mulai Cepat

### Prasyarat

- PHP 8.3+
- Composer
- PostgreSQL
- Node.js (opsional)

### 3 Langkah Mulai

```bash
# 1. Install dependency
composer install

# 2. Setup environment
cp .env.example .env
php artisan key:generate

# 3. Setup database
php artisan migrate
php artisan module:seed Authentication
php artisan module:seed Core

# 4. Jalankan server
php artisan serve
```

Server berjalan di: `http://localhost:8000`

API Docs di: `http://localhost:8000/api/docs`

---

## 📖 Instalasi Lengkap

### 1. Clone Project

```bash
git clone <repository-url>
cd Backend.Erp
```

### 2. Install Dependency

```bash
composer install
```

### 3. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Setup Database

Edit file `.env` dan atur koneksi PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=erp
DB_USERNAME=postgres
DB_PASSWORD=password
```

### 5. Migrasi Database

```bash
php artisan migrate
```

### 6. Seed Data

```bash
php artisan module:seed Authentication
php artisan module:seed Core
```

### 7. Jalankan Server

```bash
php artisan serve
```

Server berjalan di: `http://localhost:8000`

---

## � API Documentation

### Apa Itu API Documentation?

Dokumentasi API dibuat otomatis menggunakan Swagger UI. Ini memudahkan developer untuk:

- Melihat semua endpoint yang tersedia
- Mencoba API langsung dari browser
- Memahami parameter yang dibutuhkan
- Melihat contoh response

### Generate Documentation

#### Generate Semua Module

```bash
php artisan api:docs:generate --all
```

#### Generate Module Tertentu

```bash
php artisan api:docs:generate --module=Core
```

### Akses Swagger UI

Buka browser dan akses:

```
http://localhost:8000/api/docs
```

### Endpoint Penting

- **Swagger UI**: `http://localhost:8000/api/docs`
- **JSON Documentation**: `http://localhost:8000/api/docs/json`

### Parameter Default GET

Semua endpoint GET otomatis punya parameter:

| Parameter | Deskripsi | Default |
|-----------|-----------|---------|
| `take` | Jumlah record yang diambil | 10 |
| `skip` | Jumlah record yang dilewati | 0 |
| `filter` | Filter criteria (JSON string) | - |
| `expand` | Related resources yang ingin di-load | - |
| `fields` | Fields yang ingin ditampilkan | - |

Contoh penggunaan:
```
GET /v1/core/province?take=1&skip=1&filter=["is_removed","=",false]&fields=province_name,city.city_name,city.district.district_name&expand=city,city.district
```

### Authentication

Endpoint dengan middleware `auth:sanctum` butuh token. Gunakan endpoint `/api/v1/auth/login` untuk mendapatkan token.

---

## 🔍 Laravel Scout - Full-Text Search

### Apa Itu Laravel Scout?

Laravel Scout adalah package resmi Laravel untuk **full-text search** yang memudahkan pencarian data dengan performa tinggi.

### Fitur

- **Driver-based** - Mendukung PostgreSQL, dll
- **Model Integration** - Tambah trait `Searchable` ke Eloquent model
- **Auto Sync** - Otomatis sync data ke search index saat create/update/delete
- **Simple API** - `Model::search('query')->get()`

### Konfigurasi

Driver sudah dikonfigurasi menggunakan **PostgreSQL full-text search** di `config/scout.php`:

```php
'driver' => env('SCOUT_DRIVER', 'database'),
```

### Cara Menggunakan

#### 1. Tambah Trait ke Model

```php
use Laravel\Scout\Searchable;

class Province extends Model
{
    use Searchable;
}
```

#### 2. Import Data ke Search Index

```bash
# Import model
php artisan scout:import "Modules\Core\Models\Province"

# Import semua core models
php artisan scout:import "Modules\Core\Models\*"
```

#### 3. Gunakan di API

Search terintegrasi dalam parameter `filter`:

```
GET /api/v1/core/provinces?search=jawa
```

Atau dengan filter lain:

```
GET /api/v1/core/provinces?search=jawa&filter=["enable","=",true]
```

### Model yang Sudah Support

Berikut model yang sudah mendukung Scout search:

- ✅ Province
- ✅ City
- ✅ District
- ✅ Village
- ✅ Company
- ✅ Dictionary
- ✅ Menu

### Perbedaan dengan Filter Biasa

| Fitur | Filter Biasa | Scout Search |
|-------|-------------|--------------|
| Pencarian | Exact/partial match | Full-text search |
| Performa | Good untuk data kecil | Optimal untuk data besar |
| Relevansi | Tidak ada | Score-based ranking |
| Bahasa | Case-sensitive | Case-insensitive |

### Command Scout

```bash
# Import data ke search index
php artisan scout:import "App\Models\User"

# Flush search index
php artisan scout:flush "App\Models\User"

# Re-index (flush + import)
php artisan scout:import "App\Models\User" --force
```

### Tips

- Scout search hanya bekerja jika model punya trait `Searchable`
- Data otomatis sync ke search index saat create/update/delete
- Gunakan parameter `search` untuk full-text search
- Bisa dikombinasi dengan parameter filter lain

---

## 🏢 Multi-Tenancy

### Apa Itu Multi-Tenancy?

Multi-tenancy artinya satu backend bisa melayani banyak perusahaan (tenant) dengan database terpisah.

**Contoh:**
- Company A punya database sendiri
- Company B punya database sendiri
- Semua menggunakan backend yang sama

### Cara Kerja

1. **Central Database** - Menyimpan data tenant (company) dan data global
2. **Tenant Database** - Database terpisah per tenant dengan nama `db_{tenant_id}`

### Identifikasi Tenant

Tenant diidentifikasi menggunakan:
- **Header `X-Tenant`** - ID tenant (opsional, untuk domain mapping)
- **Domain** - Mapping domain ke tenant (opsional)

### Command Tenancy

#### List Semua Tenant

```bash
php artisan tenants:list
```

#### Migrate Tenant

```bash
# Migrate semua tenant
php artisan tenants:migrate

# Migrate tenant spesifik
php artisan tenants:migrate --tenants={tenant_id}
```

#### Seed Tenant

```bash
# Seed semua tenant
php artisan tenants:seed

# Seed tenant spesifik
php artisan tenants:seed --tenants={tenant_id}
```

### Struktur File Tenant

#### Migration Tenant

Diletakkan di: `Modules/{module}/database/migrations/tenant/`

```php
DB::statement('CREATE SCHEMA IF NOT EXISTS core');

Schema::create('core.province', function (Blueprint $table) {
    $table->string('province_id', 50)->primary();
    $table->string('province_name', 100);
    $table->timestamps();
});
```

#### Seeder Tenant

Diletakkan di: `Modules/{module}/database/seeders/tenant/`

```php
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

---

## 🏗 Struktur Project

### Modular Architecture

Project dibagi menjadi module yang independen:

```
Modules/
├── Authentication/  # Login, Register, User management
├── Core/           # Data master (Province, City, dll)
└── [Module Lain]/  # Module bisnis lain
```

### Struktur Module

```
Modules/Core/
├── app/
│   ├── Http/
│   │   ├── Controllers/      # API Controllers
│   │   └── Requests/         # Validation Rules
│   └── Models/               # Eloquent Models
├── database/
│   ├── migrations/           # Database migrations
│   │   ├── central/          # Central database migrations
│   │   └── tenant/           # Tenant database migrations
│   └── seeders/              # Database seeders
├── routes/
│   └── api.php               # API Routes
└── docs/
    └── api.json              # Generated API documentation
```

---

## 💡 Konsep Utama

### Master-Draft Pattern

Sistem ini menggunakan konsep **Master-Draft** untuk keamanan:

#### Master (Posted Data)
- Data yang sudah disetujui
- Tidak bisa diedit langsung
- Immutable (tidak berubah)

#### Draft (Workspace)
- Tempat kerja sementara
- Data bisa diedit
- Perlu approval untuk commit ke master

### Alur Kerja Edit

```
1. User request edit data master
   ↓
2. Data disalin ke draft (temporary)
   ↓
3. User edit di draft
   ↓
4. User commit draft
   ↓
5. Stored procedure validasi
   ↓
6. Data masuk ke master
   ↓
7. History dicatat
```

### Kenapa Pattern Ini?

✅ **Audit Trail** - Semua perubahan tercatat  
✅ **Multi-User Safe** - Banyak user bisa edit bersamaan  
✅ **Approval Workflow** - Bisa tambahkan approval  
✅ **Rollback** - Bisa kembali ke versi sebelumnya  

### Stored Procedure

Business logic diletakkan di database (stored procedure) untuk:

- Performa lebih baik
- Konsisten di semua platform
- Bisa diubah tanpa deploy ulang
- Lebih aman (logic di server database)

---

## ❓ FAQ

### Q: Bagaimana cara membuat module baru?

```bash
php artisan erp:make-module {nama_module}
```

### Q: Bagaimana cara membuat model baru?

```bash
php artisan erp:make-model {nama_model} {nama_module}
```

### Q: Bagaimana cara migrate module?

```bash
php artisan module:migrate {nama_module}
```

### Q: Bagaimana cara seed module?

```bash
php artisan module:seed {nama_module}
```

### Q: Bagaimana cara clear cache?

```bash
php artisan optimize:clear
```

### Q: Apa bedanya central dan tenant migration?

- **Central Migration** - Untuk database central (data tenant, auth, dll)
- **Tenant Migration** - Untuk database tenant (data bisnis per tenant)

### Q: Bagaimana cara menggunakan AI?

Install Laravel AI:

```bash
composer require laravel/ai
```

Setup API key di `.env`:

```env
OPENAI_API_KEY=sk-xxxx
```

Gunakan di controller:

```php
use Illuminate\Support\Facades\AI;

$response = AI::prompt('Buat ringkasan transaksi');
return $response->text();
```

---

## 🎯 Use Case Nyata

### Contoh 1: Ubah Rule Bisnis

**Requirement:** "Simpan Village → otomatis buat Region"

**Cara Lama:**
1. Ubah controller
2. Ubah service
3. Deploy ulang

**Cara Baru:**
1. Update stored procedure
2. Selesai

### Contoh 2: Multi-Company SaaS

Satu backend untuk:
- Company A (database terpisah)
- Company B (database terpisah)
- Company C (database terpisah)

Semua menggunakan kode yang sama, data terisolasi.

---

## 📞 Bantuan

Jika ada pertanyaan atau masalah:

1. Cek dokumentasi API di `/api/docs`
2. Cek log di `storage/logs/laravel.log`
3. Clear cache: `php artisan optimize:clear`