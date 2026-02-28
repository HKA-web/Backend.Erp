
# Change Language

[🇮🇩 Indonesia](README.id.md)

---

# 🚀 ERP Backend – Enterprise Modular Architecture

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

---

# 📚 Table of Contents

* [Overview](#-overview)
* [Architecture](#-architecture)
* [Workflow](#-workflow)
* [API Structure](#-api-structure)
* [Database Flow](#-database-flow)
* [Why This Architecture?](#-why-this-architecture)
* [Installation](#-installation)
* [Creating New Modules](#-creating-new-modules)
* [Permission Management](#-permission-management)
* [API Documentation](#-api-documentation)

---

# 🧭 Overview

This project is built using a **fully modular architecture**.

Core principles:

* Separation between **Master (Posted Data)** and **Draft (Temporary Workspace)**
* Business logic handled at **database level (Triggers & Stored Procedures)**
* Laravel acts as:

    * Validator
    * Orchestrator
    * API responder

---

# 🏗 Architecture

## Master–Draft Pattern + remoteForeign Support

* ✅ Master & Draft schema separation
* ✅ Cross-schema relationship using `remoteForeign`
* ✅ All Master modifications via Stored Procedures
* ✅ Audit-friendly & workflow-ready

---

## 🧩 High-Level Architecture Diagram

![Architecture Diagram](public/documentation/arcitecture-diagram.png)

---

# 🔄 Workflow

## API Endpoint Example

`POST /store`

### Laravel Responsibilities

* Validate input
* Store validated data into temporary table
* Execute stored procedure:

```sql
CALL core.procedure_commit();
```

---

## Database Responsibilities

When an `INSERT` or status update occurs:

Trigger automatically executes:

> "New data detected → calculate balance → create journal → update stock."

---

## Final Flow

1. Database returns **OK**
2. Laravel returns JSON success response

---

# 📦 Example Module

Example generated module:

* Module: `Core`
* Model: `Dictionary`
* Master Table: `core.dictionaries`
* Temporary Table: `temporary.core_dictionary`

---

# 🏛 API Structure

---

## 1️⃣ Master Resource (Official / Posted Data)

🔒 No direct edits allowed.

| Method | Endpoint                       | Description          |
| ------ | ------------------------------ | -------------------- |
| GET    | `/v1/dictionaries`             | List POSTED data     |
| GET    | `/v1/dictionaries/{id}`        | View official detail |
| POST   | `/v1/dictionaries/{id}/revise` | Lock & copy to Draft |
| DELETE | `/v1/dictionaries/{id}`        | Request deletion     |

---

## 2️⃣ Draft Resource (Workspace / Sandbox)

| Method | Endpoint                            | Description   |
| ------ | ----------------------------------- | ------------- |
| GET    | `/v1/dictionary-drafts`             | List drafts   |
| POST   | `/v1/dictionary-drafts`             | Create draft  |
| GET    | `/v1/dictionary-drafts/{id}`        | Draft detail  |
| PUT    | `/v1/dictionary-drafts/{id}`        | Update draft  |
| DELETE | `/v1/dictionary-drafts/{id}`        | Discard draft |
| POST   | `/v1/dictionary-drafts/{id}/commit` | Finalize      |

---

# 🧠 Database Flow

## ✏️ Edit Flow

![Edit Flow Diagram](public/documentation/edit-flow-diagram.png)

---

## 🗑 Delete Flow

![Delete Flow Diagram](public/documentation/delete-flow-diagram.png)

---

# 🎯 Why This Architecture?

* ✅ Zero direct edits to Master
* ✅ Fully auditable
* ✅ Supports approval workflow
* ✅ Multi-user safe
* ✅ Database-driven business rules
* ✅ No redeploy needed for business logic changes

---

# 💼 Real Enterprise Advantage

If your manager says:

> “Every time we save a Village, automatically create a Region record.”

You DO NOT need to:

* Modify Controller
* Change Service layer
* Redeploy the app

You ONLY:

1. Open pgAdmin
2. Modify Trigger or Procedure
3. Done.

---

# 🚀 Installation

## Requirements

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

Enter project:

```bash
cd {project_name}
```

Install dependencies:

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

Run migration:

```bash
php artisan migrate
```

Run server:

```bash
php artisan serve
```

---

# 🛠 Creating New Modules

Create module:

```bash
php artisan erp:make-module {module}
```

Create model:

```bash
php artisan erp:make-model {model} {module}
```

Run module migration:

```bash
php artisan module:migrate {module}
```

---

## Configure Routes

```php
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('{module}/{model}', {model}Controller::class)
        ->names('{module}-{model}');
});
```

---

# 🔐 Permission Management

This project uses **Spatie Laravel Permission**.

Example:

```php
$user->assignRole('admin');
$role->givePermissionTo('edit-user');
```

---

# 📄 API Documentation

Import Postman collection from:

```
postman/collections/Laravel.postman_collection.json
```

---

# 🏁 Status Lifecycle

![Lifecycle Diagram](public/documentation/lifecycle-diagram.png)

---

# 🏢 Designed For

* ERP Systems
* Enterprise Applications
* Financial Systems
* Audit-sensitive environments
* Multi-user transactional systems

---

Kalau kamu mau, next level kita bisa bikin:

* 🔥 README versi SaaS Product Style
* 📊 Architecture diagram versi Clean Architecture
* 🧠 Whitepaper PDF untuk presentasi ke manajemen
* 🏗 Visual database schema diagram

Kamu mau naik ke level mana sekarang? 🚀
