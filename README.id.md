# Ubah Bahasa

[🇺🇸 English](README.md)

---

# 🚀 ERP Backend – Arsitektur Modular Enterprise

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

Backend modular kelas enterprise yang dibangun dengan Laravel dan PostgreSQL menggunakan **Pola Master–Draft**, Stored Procedure, dan logika bisnis berbasis database.

---

# 📚 Daftar Isi

* [Ringkasan](#-ringkasan)
* [Arsitektur](#-arsitektur)
* [Alur Kerja](#-alur-kerja)
* [Struktur API](#-struktur-api)
* [Alur Database](#-alur-database)
* [Mengapa Arsitektur Ini?](#-mengapa-arsitektur-ini)
* [Instalasi](#-instalasi)
* [Membuat Modul Baru](#-membuat-modul-baru)
* [Manajemen Permission](#-manajemen-permission)
* [Dokumentasi API](#-dokumentasi-api)

---

# 🧭 Ringkasan

Proyek ini dibangun menggunakan **arsitektur modular penuh**.

Prinsip utama:

* Pemisahan antara **Master (Data Posted)** dan **Draft (Ruang Kerja Sementara)**
* Logika bisnis ditangani di **level database (Trigger & Stored Procedure)**
* Laravel berperan sebagai:

    * Validator
    * Orkestrator
    * Pemberi respons API

---

# 🏗 Arsitektur

## Pola Master–Draft + Dukungan remoteForeign

* ✅ Pemisahan skema Master & Draft
* ✅ Relasi lintas skema menggunakan `remoteForeign`
* ✅ Semua perubahan Master melalui Stored Procedure
* ✅ Ramah audit & siap workflow

---

## 🧩 Diagram Arsitektur Tingkat Tinggi

![Architecture Diagram](public/documentation/arcitecture-diagram.png)

---

# 🔄 Alur Kerja

## Contoh Endpoint API

`POST /store`

### Tanggung Jawab Laravel

* Validasi input
* Simpan data tervalidasi ke tabel sementara
* Jalankan stored procedure:

```sql
CALL core.procedure_commit();
```

---

## Tanggung Jawab Database

Ketika terjadi `INSERT` atau pembaruan status:

Trigger akan otomatis menjalankan:

> "Data baru terdeteksi → hitung saldo → buat jurnal → perbarui stok."

---

## Alur Akhir

1. Database mengembalikan **OK**
2. Laravel mengembalikan respons sukses dalam bentuk JSON

---

# 📦 Contoh Modul

Contoh modul yang dihasilkan:

* Modul: `Core`
* Model: `Dictionary`
* Tabel Master: `core.dictionaries`
* Tabel Sementara: `temporary.core_dictionary`

---

# 🏛 Struktur API

---

## 1️⃣ Resource Master (Data Resmi / Posted)

🔒 Tidak diperbolehkan edit langsung.

| Method | Endpoint                       | Deskripsi              |
| ------ | ------------------------------ | ---------------------- |
| GET    | `/v1/dictionaries`             | Daftar data POSTED     |
| GET    | `/v1/dictionaries/{id}`        | Lihat detail resmi     |
| POST   | `/v1/dictionaries/{id}/revise` | Kunci & salin ke Draft |
| DELETE | `/v1/dictionaries/{id}`        | Ajukan penghapusan     |

---

## 2️⃣ Resource Draft (Workspace / Sandbox)

| Method | Endpoint                            | Deskripsi      |
| ------ | ----------------------------------- | -------------- |
| GET    | `/v1/dictionary-drafts`             | Daftar draft   |
| POST   | `/v1/dictionary-drafts`             | Buat draft     |
| GET    | `/v1/dictionary-drafts/{id}`        | Detail draft   |
| PUT    | `/v1/dictionary-drafts/{id}`        | Perbarui draft |
| DELETE | `/v1/dictionary-drafts/{id}`        | Buang draft    |
| POST   | `/v1/dictionary-drafts/{id}/commit` | Finalisasi     |

---

# 🧠 Alur Database

## ✏️ Alur Edit

![Edit Flow Diagram](public/documentation/edit-flow-diagram.png)

---

## 🗑 Alur Hapus

![Delete Flow Diagram](public/documentation/delete-flow-diagram.png)

---

# 🎯 Mengapa Arsitektur Ini?

* ✅ Tidak ada edit langsung ke Master
* ✅ Sepenuhnya dapat diaudit
* ✅ Mendukung approval workflow
* ✅ Aman untuk multi-user
* ✅ Aturan bisnis berbasis database
* ✅ Tidak perlu redeploy untuk perubahan logika bisnis

---

# 💼 Keunggulan Enterprise Nyata

Jika manajer Anda berkata:

> “Setiap kali kita menyimpan Village, otomatis buat record Region.”

Anda TIDAK perlu:

* Mengubah Controller
* Mengubah Service layer
* Redeploy aplikasi

Anda HANYA:

1. Buka pgAdmin
2. Ubah Trigger atau Procedure
3. Selesai.

---

# 🚀 Instalasi

## Kebutuhan

* PHP 8.2+
* Composer
* Laravel 12.x
* PostgreSQL

---

## Setup

Clone repository:

```bash
git clone https://github.com/HKA-web/Backend.Erp.git {project_name}
```

Masuk ke proyek:

```bash
cd {project_name}
```

Install dependensi:

```bash
composer install --prefer-dist
```

Setup environment:

```bash
cp .env.example .env
```

Generate key:

```bash
php artisan key:generate
```

Jalankan migration:

```bash
php artisan migrate
```

Jalankan server:

```bash
php artisan serve
```

---

# 🛠 Membuat Modul Baru

Buat modul:

```bash
php artisan erp:make-module {module}
```

Buat model:

```bash
php artisan erp:make-model {model} {module}
```

Jalankan migration modul:

```bash
php artisan module:migrate {module}
```

---

## Konfigurasi Routes

```php
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('{module}/{model}', {model}Controller::class)
        ->names('{module}-{model}');
});
```

---

# 🔐 Manajemen Permission

Proyek ini menggunakan **Spatie Laravel Permission**.

Contoh:

```php
$user->assignRole('admin');
$role->givePermissionTo('edit-user');
```

---

# 📄 Dokumentasi API

Import koleksi Postman dari:

```
postman/collections/Laravel.postman_collection.json
```

---

# 🏁 Siklus Status

![Lifecycle Diagram](public/documentation/lifecycle-diagram.png)

---

# 🏢 Dirancang Untuk

* Sistem ERP
* Aplikasi Enterprise
* Sistem Keuangan
* Lingkungan sensitif audit
* Sistem transaksional multi-user

---

Kalau kamu mau, next level kita bisa bikin:

* 🔥 README versi SaaS Product Style
* 📊 Diagram arsitektur versi Clean Architecture
* 🧠 Whitepaper PDF untuk presentasi ke manajemen
* 🏗 Diagram skema database visual

Kamu mau naik ke level mana sekarang? 🚀
