<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Laravel Module

#### This program is designed with a modular concept. For more details, please visit [Documentation](https://laravelmodules.com/docs/12/advanced/artisan-commands#modulemigrate).

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

3. **Use Module:**

    ```bash
    php artisan module:use {module}
    ```

4. **Migrate Module:**

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

