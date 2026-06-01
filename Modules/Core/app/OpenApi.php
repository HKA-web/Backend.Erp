<?php

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Core Module API",
 *     version="V.1",
 *     description="API endpoints for core data management (Province, City, District, Village, Company, Dictionary, Menu). Requires tenant identification via header.",
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
 * @OA\SecurityScheme(
 *     securityScheme="tenant",
 *     type="apiKey",
 *     in="header",
 *     name="X-Tenant",
 *     description="Tenant ID for multi-tenancy (default header name)"
 * )
 *
 * Apply security schemes to all authenticated endpoints
 * @OA\Security(
 *     security={{"sanctum": {}}, {"tenant": {}}}
 * )
 */
