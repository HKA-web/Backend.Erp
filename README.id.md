<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>


# Ganti Bahasa

[🇺🇸 English](README.md)

---

# Laravel Module

#### Program ini dirancang dengan konsep modular. Untuk detail lebih lanjut, silakan kunjungi [Documentation](https://laravelmodules.com/docs/12/advanced/artisan-commands#modulemigrate).

---

# Alur Kerja

### API Endpoint (POST `/store`)

Dipanggil oleh Frontend.

### Laravel

Laravel hanya bertugas untuk:

* Melakukan validasi input dasar (misalnya: “nama tidak boleh kosong”).
* Menyimpan data yang sudah divalidasi ke tabel temporary.
* Memanggil contoh procedure `CALL core.procedure_commit()`.

---

## Di Dalam Procedure (SQL)

1. Procedure SQL mengambil data dari tabel temporary.
2. SQL mengecek status:

    * “Apakah ini `DRAFT` atau `POSTED`?”
3. Jika status adalah `POSTED`, SQL akan melakukan `INSERT` ke tabel Master.

---

## Di Dalam Tabel Master (Trigger)

Begitu terjadi `INSERT` (atau perubahan status melalui `UPDATE`):

Trigger akan langsung berjalan dan mengeksekusi:

> “Data baru terdeteksi! Jalankan perhitungan saldo, buat jurnal, dan perbarui stok.”

---

## Selesai

1. Database mengembalikan sinyal `"OK"` ke Laravel.
2. Laravel mengirimkan respons JSON sukses ke pengguna.

---

# Keuntungan Utama untuk Anda (Sebagai Maintainer)

Dengan arsitektur ini, jika suatu hari atasan Anda mengatakan:

> “Mulai sekarang, setiap kali menyimpan Village, tolong otomatis buatkan data di tabel `Region` juga.”

Maka:

* Anda TIDAK perlu membuka VS Code.
* Anda TIDAK perlu mengubah Controller atau Service di PHP.
* Anda TIDAK perlu melakukan deploy ulang aplikasi.

Anda HANYA perlu:

* Membuka pgAdmin.
* Menambahkan satu statement `INSERT` di dalam Trigger atau Procedure.

Selesai.

---
# Memulai

## Prasyarat

* PHP 8.2 atau lebih tinggi
* Composer
* Laravel 12.x
* PgSQL atau database lain yang didukung

---

## Instalasi

1. **Clone repository:**

```bash
https://github.com/HKA-web/Backend.Erp.git {project_name}
```

2. **Masuk ke direktori project:**

```bash
cd {project_name}
```

3. **Install dependency:**

```bash
composer install --prefer-dist
```

4. **Atur environment:**

Salin file `.env.example` menjadi `.env` lalu konfigurasi pengaturan database Anda.

```bash
cp .env.example .env
```

5. **Generate application key:**

```bash
php artisan key:generate
```

6. **Jalankan migration:**

```bash
php artisan migrate
```

7. **Seed database (opsional):**

```bash
php artisan db:seed
```

8. **Menjalankan aplikasi:**

```bash
php artisan serve
```

---

# Membuat Fitur Baru

1. **Buat Module:**

```bash
php artisan erp:make-module {module}
```

2. **Buat Model:**

```bash
php artisan erp:make-model {model} {module}
```

3. **Migrasi Module:**

```bash
php artisan module:migrate {module}
```

4. **Migrasi Procedure:**

Eksekusi file query pada `Modules/{module}/database/migrations/sql/xxxx_xx_xx_xxxxxx_{modul}.procedure_action_{model}.sql`.

```bash
php artisan module:migrate {module}
```

5. **Atur Routes:**

Tambahkan route pada file `{module}/routes/api` dan sesuaikan konfigurasi route Anda.

```bash
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
  Route::apiResource('{module}/{model}', {model}Controller::class)->names('{module}-{model}');
});
```

6. **Konfigurasi Seeder:**

Tambahkan seeder pada file `{module}/databases/seeders` lalu sesuaikan konfigurasi seeder Anda.

```bash
{model}::factory()->create();
```

7. **Seed Module (opsional):**

```bash
php artisan module:seed {module}
```

8. **Clear Cache:**

```bash
php artisan config:clear
php artisan permission:cache-reset
php artisan config:cache
php artisan optimize:clear
```

---

# Spatie

#### Untuk pengelolaan hak akses (permission), program ini menggunakan Spatie. Untuk detail lebih lanjut, silakan kunjungi [Documentation](https://spatie.be/docs/laravel-permission/v7/basic-usage/role-permissions).

---

# Contoh Eksekusi Menggunakan Tinker

1. **Buka tinker:**

```bash
php artisan tinker
```

2. **Import:**

```bash
use Modules\Authentication\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
```

3. **Menambahkan User ke Role (Spatie):**

```bash
$user = User::where('email', 'admin@email.com')->first();
$user->assignRole('admin');
```

4. **Menambahkan Permission ke Role (Spatie):**

```bash
$role = Role::findByName('admin', 'api');
$role->givePermissionTo('edit-user');
$role->givePermissionTo(['create-post', 'delete-post']);
```
---

# Dokmentasi API

#### import koleksi file `Laravel.postman_collection.json` di dalam folder `postman/collections/`.

---
