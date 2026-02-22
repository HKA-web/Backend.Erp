<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Change Language

[🇮🇩 Bahasa Indonesia](README.id.md)

---
## Laravel Module

#### This program is designed with a modular concept. For more details, please visit [Documentation](https://laravelmodules.com/docs/12/advanced/artisan-commands#modulemigrate).

---
# Workflow

### API Endpoint (POST `/store`)

Called by the Frontend.

### Laravel

Laravel is only responsible for:

* Performing basic input validation (e.g., “name cannot be empty”).
* Inserting the validated data into a temporary table.
* Calling example procedure `CALL core.procedure_commit()`.

---

## Inside the Procedure (SQL)

1. The SQL procedure retrieves the data from the temporary table.
2. SQL checks the status:

    * “Is this `DRAFT` or `POSTED`?”
3. If the status is `POSTED`, SQL performs an `INSERT` into the Master table.

---

## Inside the Master Table (Trigger)

As soon as an `INSERT` (or status `UPDATE`) occurs:

The trigger immediately fires and says:

> “New data detected! Execute balance calculation, create journal entries, and update stock.”

---

## Finish

1. The database returns an `"OK"` signal to Laravel.
2. Laravel sends a success JSON response back to the user.

---

# Main Advantage for You (As a Maintainer)

With this architecture, if one day your boss says:

> “From now on, whenever we save a Village, please automatically create data in the `Region` table as well.”

Then:

* You DO NOT need to open VS Code.
* You DO NOT need to modify the PHP Controller or Service.
* You DO NOT need to redeploy the application.

You ONLY need to:

* Open pgAdmin.
* Add one `INSERT` statement inside the Trigger or Procedure.

Done.

---
## Getting Started

### Prerequisites

-   PHP 8.2 or higher
-   Composer
-   Laravel 12.x
-   PgSQL or any other supported database
  
### Installation

1. **Clone the repository:**

    ```bash
    https://github.com/HKA-web/Backend.Erp.git {project_name}
    ```

2. **Navigate to the project directory:**

    ```bash
    cd {project_name}
    ```

3. **Install dependencies:**

    ```bash
    composer install --prefer-dist
    ```

4. **Set up environment variables:**

   Copy the `.env.example` file to `.env` and configure your database settings.

    ```bash
    cp .env.example .env
    ```

5. **Generate application key:**

    ```bash
    php artisan key:generate
    ```

6. **Run migrations:**

    ```bash
    php artisan migrate
    ```

7. **Seed the database (optional):**

    ```bash
    php artisan db:seed
    ``` 

8. **Running:**

    ```bash
    php artisan serve
    ``` 

### Crete New Features

1. **Make Module:**

    ```bash
    php artisan erp:make-module {module}
    ```

2. **Make Model:**

    ```bash
    php artisan erp:make-model {model} {module}
    ```

3. **Migrate Module:**

    ```bash
    php artisan module:migrate {module}
    ```

4. **Migrate Procedure:**

   Excecution query file to `Modules/{module}/database/migrations/sql/xxxx_xx_xx_xxxxxx_{modul}.procedure_action_{model}.sql`.

    ```bash
    php artisan module:migrate {module}
    ```

5. **Set up routes:**

   Add route in file `{module}/routes/api` and configure your route settings.

    ```bash
    Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
      Route::apiResource('{module}/{model}', {model}Controller::class)->names('{module}-{model}');
    });
    ```
      
5. **Config Seed:**

    Add seeder in file `{module}/databases/seeders` and configure your seeder.

    ```bash
   {model}::factory()->create();
    ```

6. **Seed the database (optional):**

    ```bash
    php artisan module:seed {module}
    ```
   
6. **Clear Cache:**

    ```bash
    php artisan config:clear
    php artisan permission:cache-reset
    php artisan config:cache
    php artisan optimize:clear
    ```
   
---
## Spatie

#### For permit processing, this program is built with spatie. For more details, please visit [Documentation](https://spatie.be/docs/laravel-permission/v7/basic-usage/role-permissions).

## Example Execution With Tinker

1. **Open tinker:**

    ```bash
    php artisan tinker
    ```

2. **Import:**

    ```bash
    use Modules\Authentication\Models\User;
    use Spatie\Permission\Models\Role;
    use Spatie\Permission\Models\Permission;
    ```

3. **Spatie Add User To Role:**

    ```bash
    $user = User::where('email', 'admin@email.com')->first();
    $user->assignRole('admin');
    ```

4. **Spatie Add Permission To Role:**

    ```bash
    $role = Role::findByName('admin', 'api');
    $role->givePermissionTo('edit-user');
    $role->givePermissionTo(['create-post', 'delete-post']);
    ```

---
# Documentation API

#### import collection file `Laravel.postman_collection.json` inside folder `postman/collections/`.

---
