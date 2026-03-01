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
* [Instalasi](#-instalasi)
* [Membuat Modul Baru](#-membuat-modul-baru)
* [Manajemen Permission](#-manajemen-permission)
* [Arsitektur](#-arsitektur)
* [Alur Kerja](#-alur-kerja)
* [Struktur API](#-struktur-api)
* [Alur Database](#-alur-database)
* [Mengapa Arsitektur Ini?](#-mengapa-arsitektur-ini)
* [Dokumentasi API](#-dokumentasi-api)
* [Temporary Hierarchical](#-schema-temporary)

---

# 🧭 Ringkasan

Proyek ini dibangun menggunakan [**Laravel Module**](https://laravelmodules.com/docs/12/advanced/artisan-commands).

Prinsip utama:

* Pemisahan antara **Master (Data Posted)** dan **Draft (Ruang Kerja Sementara)**
* Logika bisnis ditangani di **level database (Trigger & Stored Procedure)**
* Laravel berperan sebagai:

    * Validator
    * Orkestrator
    * Pemberi respons API

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

Run seed default:

```bash
php artisan module:seed Authentication
php artisan module:seed Core
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

Jalankan seed:

```bash
php artisan module:seed {module}
```

---

# 🔐 Manajemen Permission

Proyek ini menggunakan [**Spatie Laravel Permission**.](https://spatie.be/docs/laravel-permission/v7/introduction)

Contoh:

```php
$user->assignRole('admin');
$role->givePermissionTo('edit-user');
```

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
* Tabel Master: `core.dictionary`
* Tabel Sementara: `temporary.core_dictionary`

---

# 🏛 Struktur API

---

## 1️⃣ Resource Master (Data Resmi / Posted)

🔒 Tidak diperbolehkan edit langsung.

| Method | Endpoint                          | Deskripsi              |
| ------ |-----------------------------------| ---------------------- |
| GET    | `/v1/core/dictionary`             | Daftar data POSTED     |
| GET    | `/v1/core/dictionary/{id}`        | Lihat detail resmi     |
| POST   | `/v1/core/dictionary/{id}/revise` | Kunci & salin ke Draft |
| DELETE | `/v1/core/dictionary/{id}`        | Ajukan penghapusan     |

---

## 2️⃣ Resource Draft (Workspace / Sandbox)

| Method | Endpoint                                 | Deskripsi      |
| ------ |------------------------------------------| -------------- |
| GET    | `/v1/core/dictionary-drafts`             | Daftar draft   |
| POST   | `/v1/core/dictionary-drafts`             | Buat draft     |
| GET    | `/v1/core/dictionary-drafts/{id}`        | Detail draft   |
| PUT    | `/v1/core/dictionary-drafts/{id}`        | Perbarui draft |
| DELETE | `/v1/core/dictionary-drafts/{id}`        | Buang draft    |
| POST   | `/v1/core/dictionary-drafts/{id}/commit` | Finalisasi     |

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

# 📄 Dokumentasi API

Import koleksi Postman dari:

```
postman/collections/Laravel.postman_collection.json
```

---

# 🏁 Siklus Status

![Lifecycle Diagram](public/documentation/lifecycle-diagram.png)

---

# 🗂 Schema Temporary

Berikut adalah contoh desain akhir struktur kolom untuk tabel **Master–Detail–SubDetail (3 tingkat)** di dalam schema `temporary`.

Struktur ini mendukung:

* Hirarki bertingkat
* Editing berbasis session
* Tracking Insert / Update / Delete
* Commit aman ke schema Master

---

# 🏗 Struktur Hirarki (3 Tingkat)

Misalkan kita memiliki tabel pada schema `sales`:

| Tingkat | Tabel Master       |
| ------- | ------------------ |
| Level 1 | `sales.orders`     |
| Level 2 | `sales.items`      |
| Level 3 | `sales.item_taxes` |

Schema `temporary` akan mencerminkan struktur ini dengan tambahan kolom kontrol.

---

# 🥇 Level 1 — `temporary.sales_orders`

Level Root / Master.

| Kolom            | Tipe    | Keterangan                                               |
| ---------------- | ------- | -------------------------------------------------------- |
| `temporary_id`        | uuid    | Primary key khusus tabel temporary                       |
| `session_id`     | uuid    | ID sesi user (login / browser session)                   |
| `master_id`      | string  | Referensi ke `order_id` tabel asli (NULL jika data baru) |
| `parent_temporary_id` | uuid    | NULL (karena ini level Root)                             |
| `temporary_option`        | char(1) | Status operasi: `I` (Insert), `U` (Update), `D` (Delete) |
| `order_date`     | date    | Kolom asli dari tabel master                             |
| `customer_id`    | string  | Kolom asli dari tabel master                             |

---

# 🥈 Level 2 — `temporary.sales_order_items`

Level Detail yang terhubung ke `sales_orders`.

| Kolom            | Tipe    | Keterangan                                         |
| ---------------- | ------- | -------------------------------------------------- |
| `temporary_id`        | uuid    | Primary key                                        |
| `session_id`     | uuid    | Sama dengan level 1                                |
| `master_id`      | string  | Referensi ke `item_id` asli (NULL jika baris baru) |
| `parent_temporary_id` | uuid    | Link ke `sales_orders.temporary_id`                     |
| `temporary_option`        | char(1) | Status perubahan baris                             |
| `product_id`     | string  | Kolom asli                                         |
| `qty`            | numeric | Kolom asli                                         |

---

# 🥉 Level 3 — `temporary.sales_order_item_taxes`

Level Sub-Detail yang terhubung ke `sales_order_items`.

| Kolom            | Tipe    | Keterangan                          |
| ---------------- | ------- | ----------------------------------- |
| `temporary_id`        | uuid    | Primary key                         |
| `session_id`     | uuid    | Sama dengan level 1 & 2             |
| `master_id`      | string  | Referensi ke `tax_id` asli          |
| `parent_temporary_id` | uuid    | Link ke `sales_order_items.temporary_id` |
| `temporary_option`        | char(1) | Status perubahan baris              |
| `tax_percent`    | numeric | Kolom asli                          |

---

# 🔗 Alur Relasi Hirarki

```text
temporary.sales_orders (Level 1)
        │
        └── parent_temporary_id
              ↓
temporary.sales_order_items (Level 2)
        │
        └── parent_temporary_id
              ↓
temporary.sales_order_item_taxes (Level 3)
```

---

# 🧠 Prinsip Desain Utama

### 1️⃣ `temporary_id`

Identifier unik di dalam schema temporary.

---

### 2️⃣ `session_id`

Menjamin isolasi data antar user (tidak saling bercampur).

---

### 3️⃣ `master_id`

Menghubungkan ke record asli di tabel Master.

* NULL → Data baru (Insert)
* Tidak NULL → Data lama (Update/Delete)

---

### 4️⃣ `parent_temporary_id`

Menjaga struktur hirarki antar level dalam schema temporary.

---

### 5️⃣ `temporary_option`

Menandai jenis operasi:

| Nilai | Arti   |
| ----- | ------ |
| `I`   | Insert |
| `U`   | Update |
| `D`   | Delete |

---

# 🏢 Dirancang Untuk

* Sistem ERP
* Aplikasi Enterprise
* Lingkungan sensitif audit
* Sistem transaksional multi-user

---
