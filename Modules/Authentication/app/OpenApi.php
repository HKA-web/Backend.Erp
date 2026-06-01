<?php

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Authentication Module API",
 *     version="V.1",
 *     description="API endpoints for user authentication and management. Uses central database, no tenant identification required.",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     )
 * )
 *
 * @OA\Server(
 *     description="API Server",
 *     url="http://localhost:8000/api"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter token in format: Bearer {token}"
 * )
 *
 * Apply security scheme to all authenticated endpoints
 * @OA\Security(
 *     security={{"sanctum": {}}}
 * )
 */
