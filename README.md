# Laravel API Boilerplate

![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red)
![PHP Version](https://img.shields.io/badge/PHP-8.2-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Tests](https://img.shields.io/badge/tests-Pest-green)
![Code Quality](https://img.shields.io/badge/analysis-PHPStan-blue)

This repository provides a production-ready boilerplate for creating RESTful APIs using Laravel 12, with a focus on clean, maintainable code. It leverages Actions, Data Transfer Objects (DTOs), API Resources, and modern best practices to ensure a scalable and well-documented architecture.

## Table of Contents

-   [Features](#features)
-   [Getting Started](#getting-started)
    -   [Prerequisites](#prerequisites)
    -   [Installation](#installation)
    -   [Running the API](#running-the-api)
    -   [API Documentation](#api-documentation)
-   [API Endpoints](#api-endpoints)
-   [Usage](#usage)
    -   [Architecture Overview](#architecture-overview)
    -   [Creating New Features](#creating-new-features)
    -   [Authentication](#authentication)
    -   [Response Format](#response-format)
-   [Testing](#testing)
-   [Code Quality](#code-quality)
-   [Contributing](#contributing)
-   [License](#license)
-   [Security](#security)

## Features

-   **RESTful API**: Preconfigured routes and controllers for building APIs
-   **Authentication System**: Complete auth implementation with JWT tokens using Laravel Sanctum
-   **Email Verification**: Secure email verification workflow
-   **Password Reset**: Robust password reset functionality
-   **Actions**: Separate business logic into single-responsibility classes
-   **DTOs**: Manage data flow between layers of the application
-   **API Resources**: Transform models into consistent JSON responses
-   **API Documentation**: Auto-generated API docs with Scramble
-   **Clean Code**: Emphasis on readability, reusability, and performance
-   **Testing Suite**: Comprehensive test coverage using Pest
-   **Code Quality**: Static analysis with PHPStan and code formatting with Pint
-   **Laravel 12**: Leverage the latest features and enhancements in Laravel

## Getting Started

### Prerequisites

-   PHP 8.2 or higher
-   Composer
-   Laravel 12.x
-   MySQL or any other supported database

### Installation

1. **Clone the repository:**

    ```bash
    git clone https://github.com/holiq/api-boilerplate.git
    ```

2. **Navigate to the project directory:**

    ```bash
    cd api-boilerplate
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

### Running the API

Start the development server:

```bash
php artisan serve
```

The API will be accessible at `http://localhost:8000/api`.

### API Documentation

This boilerplate includes auto-generated API documentation using Scramble. Once your server is running, you can access the interactive API documentation at:

```
http://localhost:8000/docs/api
```

The OpenAPI specification is available at:

```
http://localhost:8000/docs/api.json
```

## API Endpoints

### Authentication

| Method | Endpoint                             | Description               | Authentication |
| ------ | ------------------------------------ | ------------------------- | -------------- |
| POST   | `/api/auth/register`                 | Register a new user       | No             |
| POST   | `/api/auth/login`                    | Login user                | No             |
| POST   | `/api/auth/logout`                   | Logout user               | Required       |
| POST   | `/api/auth/forgot-password`          | Send password reset link  | No             |
| POST   | `/api/auth/reset-password`           | Reset password with token | No             |
| GET    | `/api/auth/verify-email/{id}/{hash}` | Verify email address      | Required       |
| POST   | `/api/auth/resend-email`             | Resend verification email | Required       |

### API Information

| Method | Endpoint | Description          | Authentication |
| ------ | -------- | -------------------- | -------------- |
| GET    | `/api/`  | Get API version info | No             |

## Usage

### Architecture Overview

This boilerplate follows a clean architecture pattern with the following components:

-   **Routes**: Define your API routes in `routes/api.php` and `routes/auth.php`
-   **Controllers**: Implement your API logic using controllers located in `app/Http/Controllers`
-   **Actions**: Organize business logic in `app/Actions` using the custom `make:action {name}` command
-   **DTOs**: Use Data Transfer Objects in `app/DataTransferObjects` with `make:dto {name}` command
-   **Resources**: Transform model data using API Resources in `app/Http/Resources`
-   **Requests**: Validate incoming data using Form Requests in `app/Http/Requests`

### Creating New Features

1. **Generate an Action:**

    ```bash
    php artisan make:action Auth/CustomAction
    ```

2. **Generate a DTO:**

    ```bash
    php artisan make:dto Auth/CustomData
    ```

3. **Generate a Resource:**

    ```bash
    php artisan make:resource Api/CustomResource
    ```

4. **Generate a Request:**
    ```bash
    php artisan make:request Api/CustomRequest
    ```

### Authentication

The API uses Laravel Sanctum for authentication. After successful login, you'll receive a token that should be included in subsequent requests:

```bash
Authorization: Bearer {your-token-here}
```

### Response Format

All API responses follow a consistent format:

**Success Response:**

```json
{
  "status": "success",
  "message": "Operation completed successfully",
  "data": {...}
}
```

**Error Response:**

```json
{
  "status": "error",
  "message": "Something went wrong",
  "errors": {...}
}
```

## Testing

Run the test suite using Pest:

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Auth/LoginTest.php

# Run tests with coverage
php artisan test --coverage
```

## Code Quality

### Static Analysis

Run PHPStan for static analysis:

```bash
vendor/bin/phpstan analyse
```

### Code Formatting

Format your code using Laravel Pint:

```bash
vendor/bin/pint
```


## Contributing

Contributions are welcome! Please follow these steps:

1. Fork this repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Run tests and ensure code quality (`php artisan test && vendor/bin/pint && vendor/bin/phpstan analyse`)
5. Push to the branch (`git push origin feature/amazing-feature`)
6. Open a Pull Request

Please ensure your code follows the existing style and includes appropriate tests.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Security

If you discover any security-related issues, please email the maintainer instead of using the issue tracker.
