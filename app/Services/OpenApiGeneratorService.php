<?php

namespace App\Services;

use OpenApi\Generator;

class OpenApiGeneratorService
{
    /**
     * Generate OpenAPI documentation for a specific module
     *
     * @param string $moduleName
     * @return array
     */
    public function generateForModule(string $moduleName): array
    {
        return $this->generateManualForModule($moduleName);
    }

    /**
     * Process tags to add nested structure for a module
     *
     * @param array $data
     * @param string $moduleName
     * @return array
     */
    protected function processTagsForModule(array $data, string $moduleName): array
    {
        $tags = $data['tags'] ?? [];
        $tagGroups = [];

        // Group tags by module prefix
        $moduleTags = [];
        foreach ($tags as $tag) {
            $tagName = $tag['name'];
            // If tag doesn't already have module prefix, add it
            if (strpos($tagName, $moduleName . ' - ') !== 0) {
                $newTagName = "{$moduleName} - {$tagName}";
                $tag['name'] = $newTagName;
                $tag['description'] = ($tag['description'] ?? '') . " in {$moduleName} module";
                $moduleTags[] = $tag;
                
                // Update operation tags in paths
                if (isset($data['paths'])) {
                    foreach ($data['paths'] as $path => $methods) {
                        foreach ($methods as $method => $details) {
                            if (isset($details['tags'])) {
                                $data['paths'][$path][$method]['tags'] = array_map(
                                    fn($t) => $t === $tagName ? $newTagName : $t,
                                    $details['tags']
                                );
                            }
                        }
                    }
                }
            } else {
                $moduleTags[] = $tag;
            }
        }

        // Create tag group
        if (!empty($moduleTags)) {
            $tagGroups[] = [
                'name' => ucfirst($moduleName),
                'tags' => array_column($moduleTags, 'name')
            ];
        }

        $data['tags'] = $moduleTags;
        
        if (!empty($tagGroups)) {
            $data['x-tagGroups'] = $tagGroups;
        }

        return $data;
    }

    /**
     * Generate manual OpenAPI documentation for a module
     *
     * @param string $moduleName
     * @return array
     */
    protected function generateManualForModule(string $moduleName): array
    {
        $modulePath = base_path("Modules/{$moduleName}");
        $routesFile = $modulePath . '/routes/api.php';

        if (!file_exists($routesFile)) {
            return [];
        }

        $paths = [];
        $tags = [];
        $tagGroups = [];
        $securitySchemes = [];

        // Parse the routes file to extract route definitions with prefixes
        $routesContent = file_get_contents($routesFile);

        // Parse routes by tracking prefix stack
        $this->parseRoutesByTrackingPrefixes($routesContent, $paths, $moduleName, $tags);

        // Check if route has auth middleware
        $hasAuth = strpos($routesContent, 'auth:sanctum') !== false;

        // Add security to all routes if auth middleware exists
        if ($hasAuth) {
            foreach ($paths as $path => $methods) {
                foreach ($methods as $method => $details) {
                    $paths[$path][$method]['security'] = [['sanctum' => []]];

                    // Add tenant headers only if the specific route has tenant middleware
                    if ($this->isTenantRoute($path, $method, $moduleName)) {
                        if (!isset($paths[$path][$method]['parameters'])) {
                            $paths[$path][$method]['parameters'] = [];
                        }
                        // Add X-Tenant header (default header name)
                        $paths[$path][$method]['parameters'][] = [
                            'name' => 'X-Tenant',
                            'in' => 'header',
                            'description' => 'Tenant ID for multi-tenancy (default header name)',
                            'required' => true,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ];
                    }
                }
            }
        }

        // Create tag groups for nested structure
        if (!empty($tags)) {
            // Group tags by their prefix (module name)
            $moduleTags = [];
            foreach ($tags as $tag) {
                $tagName = $tag['name'];
                if (strpos($tagName, $moduleName . ' - ') === 0) {
                    $moduleTags[] = $tagName;
                }
            }

            if (!empty($moduleTags)) {
                $tagGroups[] = [
                    'name' => ucfirst($moduleName),
                    'tags' => $moduleTags
                ];
            }
        }

        // Add security scheme if any endpoint has security
        $securitySchemes['sanctum'] = [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
            'description' => 'Laravel Sanctum authentication'
        ];

        $result = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => $moduleName . ' API',
                'version' => 'V.1',
                'description' => 'API endpoints for ' . $moduleName . ' module',
            ],
            'tags' => $tags,
            'paths' => $paths,
            'components' => [
                'securitySchemes' => $securitySchemes
            ],
            'x-module' => $moduleName,
        ];

        // Add x-tagGroups if available
        if (!empty($tagGroups)) {
            $result['x-tagGroups'] = $tagGroups;
        }

        return $result;
    }

    /**
     * Check if a route is tenant-specific (requires X-Tenant header)
     *
     * @param string $path
     * @param string $method
     * @param string $moduleName
     * @return bool
     */
    protected function isTenantRoute(string $path, string $method, string $moduleName): bool
    {
        // Get the actual route from Laravel's route collection
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        
        foreach ($routes as $route) {
            $routeMethod = strtolower($route->methods[0]);
            $routePath = '/' . ltrim(str_replace('api/', '', $route->uri), '/');
            
            // Match the route by path and method
            if ($routePath === $path && $routeMethod === $method) {
                // Check if route has tenant middleware
                $middleware = $route->gatherMiddleware();
                foreach ($middleware as $m) {
                    if (str_contains($m, 'InitializeTenancyByRequestData') || 
                        str_contains($m, 'tenancy.header')) {
                        return true;
                    }
                }
                return false;
            }
        }
        
        return false;
    }

    /**
     * Parse routes by tracking prefixes through the file
     *
     * @param string $content
     * @param array &$paths
     * @param string $moduleName
     * @param array &$tags
     * @return void
     */
    protected function parseRoutesByTrackingPrefixes(string $content, array &$paths, string $moduleName, array &$tags): void
    {
        // Use Laravel's route collection to get actual registered routes
        $routes = \Illuminate\Support\Facades\Route::getRoutes();

        // Track unique groups to avoid duplicate tags
        $uniqueGroups = [];

        foreach ($routes as $route) {
            // Only include routes that belong to this module
            $routeAction = $route->getAction();
            $controller = $routeAction['controller'] ?? null;

            if (!$controller) {
                continue;
            }

            // Check if controller belongs to this module
            if (strpos($controller, "Modules\\{$moduleName}\\") === false) {
                continue;
            }

            $method = strtolower($route->methods[0]);
            $path = $route->uri;

            // Remove 'api' prefix since server URL already includes it
            if (strpos($path, 'api/') === 0) {
                $path = substr($path, 4); // Remove 'api/'
            }

            // Add leading slash to path
            $path = '/' . ltrim($path, '/');

            // Extract method name from controller
            $methodName = $route->getActionMethod();

            // Extract group name from path (e.g., /v1/core/province -> Province)
            $groupName = $this->extractGroupNameFromPath($path, $moduleName);
            $tagName = $groupName ? "{$moduleName} - {$groupName}" : $moduleName;

            // Add tag if not already added
            if ($groupName && !isset($uniqueGroups[$tagName])) {
                $tags[] = [
                    'name' => $tagName,
                    'description' => "{$groupName} endpoints in {$moduleName} module"
                ];
                $uniqueGroups[$tagName] = true;
            }

            $operation = [
                'tags' => [$tagName],
                'summary' => $this->generateSummary($method, $path, $methodName),
                'description' => $this->generateDescription($method, $path, $methodName),
                'operationId' => $method . str_replace(['/', '{', '}'], '', $path),
                'responses' => [
                    '200' => [
                        'description' => 'Successful operation',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'success' => ['type' => 'boolean'],
                                        'data' => ['type' => 'object'],
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '401' => [
                        'description' => 'Unauthorized'
                    ],
                    '404' => [
                        'description' => 'Not found'
                    ]
                ]
            ];

            // Extract path parameters (e.g., {id}, {slug})
            preg_match_all('/\{([^}]+)\}/', $path, $pathParams);
            if (!empty($pathParams[1])) {
                if (!isset($operation['parameters'])) {
                    $operation['parameters'] = [];
                }
                foreach ($pathParams[1] as $param) {
                    $operation['parameters'][] = [
                        'name' => $param,
                        'in' => 'path',
                        'description' => ucfirst($param) . ' parameter',
                        'required' => true,
                        'schema' => [
                            'type' => 'string'
                        ]
                    ];
                }
            }

            // Add default query parameters for GET methods
            if ($method === 'get') {
                if (!isset($operation['parameters'])) {
                    $operation['parameters'] = [];
                }
                $operation['parameters'] = array_merge($operation['parameters'], [
                    [
                        'name' => 'take',
                        'in' => 'query',
                        'description' => 'Number of records to return',
                        'required' => false,
                        'schema' => [
                            'type' => 'integer',
                            'default' => 10
                        ]
                    ],
                    [
                        'name' => 'skip',
                        'in' => 'query',
                        'description' => 'Number of records to skip',
                        'required' => false,
                        'schema' => [
                            'type' => 'integer',
                            'default' => 0
                        ]
                    ],
                    [
                        'name' => 'filter',
                        'in' => 'query',
                        'description' => 'Filter criteria (JSON string)',
                        'required' => false,
                        'schema' => [
                            'type' => 'string'
                        ]
                    ],
                    [
                        'name' => 'expand',
                        'in' => 'query',
                        'description' => 'Related resources to expand (comma-separated)',
                        'required' => false,
                        'schema' => [
                            'type' => 'string'
                        ]
                    ],
                    [
                        'name' => 'fields',
                        'in' => 'query',
                        'description' => 'Fields to return (comma-separated)',
                        'required' => false,
                        'schema' => [
                            'type' => 'string'
                        ]
                    ]
                ]);
            }

            // Add X-Session-ID header for commit, destroy, update, store, and revise methods
            if (in_array($methodName, ['commit', 'destroy', 'update', 'store', 'revise'])) {
                if (!isset($operation['parameters'])) {
                    $operation['parameters'] = [];
                }
                $operation['parameters'][] = [
                    'name' => 'X-Session-ID',
                    'in' => 'header',
                    'description' => 'Session ID for temporary workspace isolation',
                    'required' => true,
                    'schema' => [
                        'type' => 'string'
                    ]
                ];
            }

            // Add request body for POST/PUT/PATCH methods (except excluded methods)
            $excludedMethodsForBody = ['commit', 'revise', 'destroy'];
            if (in_array($method, ['post', 'put', 'patch']) && !in_array($methodName, $excludedMethodsForBody)) {
                // Try to extract validation rules from Request class
                $requestSchema = $this->extractRequestSchema($controller, $methodName, $moduleName);

                $operation['requestBody'] = [
                    'required' => true,
                    'content' => [
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => $requestSchema
                            ]
                        ]
                    ]
                ];
            }

            $paths[$path][$method] = $operation;
        }
    }

    /**
     * Extract request schema from Request class validation rules
     *
     * @param string $controller
     * @param string $methodName
     * @param string $moduleName
     * @return array
     */
    protected function extractRequestSchema(string $controller, string $methodName, string $moduleName): array
    {
        $schema = [];

        // Extract controller class name (remove @method if present)
        $controllerClass = strpos($controller, '@') !== false ? explode('@', $controller)[0] : $controller;

        // Try to find the Request class
        // Pattern: Modules/{ModuleName}/app/Http/Requests/{Resource}Request.php
        try {
            $controllerClass = new \ReflectionClass($controllerClass);
            $controllerShortName = $controllerClass->getShortName();
            $resourceName = str_replace('Controller', '', $controllerShortName);

            // Try multiple patterns for Request class name
            $possibleRequestClasses = [
                "Modules\\{$moduleName}\\Http\\Requests\\{$resourceName}Request",
                "Modules\\{$moduleName}\\Http\\Requests\\" . str_replace('Draft', '', $resourceName) . 'Request', // Remove 'Draft' suffix
                "Modules\\{$moduleName}\\Http\\Requests\\" . str_replace('Drafts', '', $resourceName) . 'Request', // Remove 'Drafts' suffix
            ];

            $requestClass = null;
            foreach ($possibleRequestClasses as $class) {
                if (class_exists($class)) {
                    $requestClass = $class;
                    break;
                }
            }

            if (!$requestClass) {
                // Fallback to generic schema
                return [
                    'data' => [
                        'type' => 'object',
                        'description' => 'Request data'
                    ]
                ];
            }

            $requestInstance = new $requestClass();
            $rules = $requestInstance->rules();

            foreach ($rules as $field => $rule) {
                $schema[$field] = $this->convertLaravelRuleToOpenApi($rule);
            }
        } catch (\Exception $e) {
            // Fallback to generic schema if parsing fails
            return [
                'data' => [
                    'type' => 'object',
                    'description' => 'Request data'
                ]
            ];
        }

        return $schema;
    }

    /**
     * Convert Laravel validation rule to OpenAPI schema
     *
     * @param string $rule
     * @return array
     */
    protected function convertLaravelRuleToOpenApi(string $rule): array
    {
        $schema = [
            'type' => 'string',
            'description' => $rule
        ];

        // Parse Laravel validation rules
        if (is_string($rule)) {
            $rules = explode('|', $rule);
        } elseif (is_array($rule)) {
            $rules = $rule;
        } else {
            return $schema;
        }

        foreach ($rules as $r) {
            if (is_string($r)) {
                // Check for type rules
                if (str_starts_with($r, 'required')) {
                    // This will be handled at the required array level
                } elseif (str_starts_with($r, 'string')) {
                    $schema['type'] = 'string';
                } elseif (str_starts_with($r, 'integer') || str_starts_with($r, 'numeric')) {
                    $schema['type'] = 'integer';
                } elseif (str_starts_with($r, 'boolean')) {
                    $schema['type'] = 'boolean';
                } elseif (str_starts_with($r, 'array')) {
                    $schema['type'] = 'array';
                } elseif (str_starts_with($r, 'email')) {
                    $schema['type'] = 'string';
                    $schema['format'] = 'email';
                } elseif (str_starts_with($r, 'date')) {
                    $schema['type'] = 'string';
                    $schema['format'] = 'date';
                } elseif (str_starts_with($r, 'max:')) {
                    $maxValue = substr($r, 4);
                    if ($schema['type'] === 'string') {
                        $schema['maxLength'] = (int)$maxValue;
                    } else {
                        $schema['maximum'] = (int)$maxValue;
                    }
                } elseif (str_starts_with($r, 'min:')) {
                    $minValue = substr($r, 3);
                    if ($schema['type'] === 'string') {
                        $schema['minLength'] = (int)$minValue;
                    } else {
                        $schema['minimum'] = (int)$minValue;
                    }
                }
            }
        }

        return $schema;
    }

    /**
     * Generate summary for endpoint
     *
     * @param string $method
     * @param string $path
     * @param string $methodName
     * @return string
     */
    protected function generateSummary(string $method, string $path, string $methodName): string
    {
        $resource = $this->extractResourceName($path);
        $action = $this->methodNameToAction($methodName, $method);

        return ucfirst($action) . ' ' . $resource;
    }

    /**
     * Generate description for endpoint
     *
     * @param string $method
     * @param string $path
     * @param string $methodName
     * @return string
     */
    protected function generateDescription(string $method, string $path, string $methodName): string
    {
        $resource = $this->extractResourceName($path);
        $action = $this->methodNameToAction($methodName, $method);

        return ucfirst($action) . ' ' . $resource . ' resource';
    }

    /**
     * Extract resource name from path
     *
     * @param string $path
     * @return string
     */
    protected function extractResourceName(string $path): string
    {
        // Remove prefix and get the last segment
        $segments = explode('/', trim($path, '/'));
        return end($segments);
    }

    /**
     * Extract group name from path (e.g., /v1/core/province -> Province)
     *
     * @param string $path
     * @param string $moduleName
     * @return string|null
     */
    protected function extractGroupNameFromPath(string $path, string $moduleName): ?string
    {
        $segments = explode('/', trim($path, '/'));
        
        // Look for the module name in the path segments
        $moduleIndex = array_search(strtolower($moduleName), array_map('strtolower', $segments));
        
        if ($moduleIndex !== false && isset($segments[$moduleIndex + 1])) {
            $groupSegment = $segments[$moduleIndex + 1];
            
            // Remove 'drafts' suffix if present (e.g., province-drafts -> province)
            $groupSegment = preg_replace('/-drafts$/', '', $groupSegment);
            
            // Convert to title case
            return ucfirst($groupSegment);
        }
        
        return null;
    }

    /**
     * Convert controller method name to action name
     *
     * @param string $methodName
     * @param string $httpMethod
     * @return string
     */
    protected function methodNameToAction(string $methodName, string $httpMethod): string
    {
        $actions = [
            'index' => 'get',
            'show' => 'get',
            'store' => 'create',
            'update' => 'update',
            'destroy' => 'delete',
        ];

        if (isset($actions[$methodName])) {
            return $actions[$methodName];
        }

        return $methodName;
    }

    /**
     * Check if annotation has security
     *
     * @param string $annotation
     * @return bool
     */
    protected function hasSecurity(string $annotation): bool
    {
        return strpos($annotation, 'security') !== false;
    }

    /**
     * Extract class name from file content
     *
     * @param string $content
     * @return string|null
     */
    protected function extractClassName(string $content): ?string
    {
        preg_match('/class\s+(\w+)/', $content, $matches);
        return $matches[1] ?? null;
    }

    /**
     * Parse OpenAPI annotation
     *
     * @param string $annotation
     * @return array|null
     */
    protected function parseAnnotation(string $annotation): ?array
    {
        // This is a simplified parser - in production, you'd want a more robust solution
        if (preg_match('/path="([^"]+)"/', $annotation, $pathMatch)) {
            $path = $pathMatch[1];
        } else {
            return null;
        }

        if (preg_match('/@OA\\\\(\w+)/', $annotation, $methodMatch)) {
            $method = strtolower($methodMatch[1]);
        } else {
            return null;
        }

        return [
            'path' => $path,
            'method' => $method,
            'summary' => $this->extractAnnotationValue($annotation, 'summary'),
            'description' => $this->extractAnnotationValue($annotation, 'description'),
        ];
    }

    /**
     * Extract value from annotation
     *
     * @param string $annotation
     * @param string $key
     * @return string|null
     */
    protected function extractAnnotationValue(string $annotation, string $key): ?string
    {
        if (preg_match('/' . $key . '="([^"]+)"/', $annotation, $match)) {
            return $match[1];
        }
        return null;
    }

    /**
     * Generate OpenAPI documentation for all modules
     *
     * @return array
     */
    public function generateForAllModules(): array
    {
        $modulesPath = base_path('Modules');
        $modules = glob($modulesPath . '/*', GLOB_ONLYDIR);

        $paths = [];
        $components = [
            'schemas' => [],
            'securitySchemes' => [],
        ];
        $tags = [];
        $tagGroups = [];

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $moduleDocs = $this->generateForModule($moduleName);

            if (empty($moduleDocs)) {
                continue;
            }

            // Merge paths
            if (isset($moduleDocs['paths'])) {
                foreach ($moduleDocs['paths'] as $path => $methods) {
                    if (!isset($paths[$path])) {
                        $paths[$path] = $methods;
                    } else {
                        foreach ($methods as $method => $details) {
                            $paths[$path][$method] = $details;
                        }
                    }
                }
            }

            // Merge components
            if (isset($moduleDocs['components'])) {
                if (isset($moduleDocs['components']['schemas'])) {
                    $components['schemas'] = array_merge($components['schemas'], $moduleDocs['components']['schemas']);
                }
                if (isset($moduleDocs['components']['securitySchemes'])) {
                    $components['securitySchemes'] = array_merge($components['securitySchemes'], $moduleDocs['components']['securitySchemes']);
                }
            }

            // Merge tags
            if (isset($moduleDocs['tags'])) {
                $tags = array_merge($tags, $moduleDocs['tags']);
            }

            // Merge tag groups
            if (isset($moduleDocs['x-tagGroups'])) {
                $tagGroups = array_merge($tagGroups, $moduleDocs['x-tagGroups']);
            }
        }

        $result = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'ERP Backend API',
                'description' => 'API documentation',
                'version' => 'V.1',
            ],
            'servers' => [
                [
                    'url' => config('app.url') . '/api/',
                    'description' => 'API Server',
                ],
            ],
            'tags' => $tags,
            'paths' => $paths,
            'components' => $components,
        ];

        // Add x-tagGroups if available
        if (!empty($tagGroups)) {
            $result['x-tagGroups'] = $tagGroups;
        }

        return $result;
    }

    /**
     * Save OpenAPI documentation to JSON file for a module
     *
     * @param string $moduleName
     * @return bool
     */
    public function saveForModule(string $moduleName): bool
    {
        $docs = $this->generateForModule($moduleName);

        if (empty($docs)) {
            return false;
        }

        $docsPath = base_path("Modules/{$moduleName}/docs");
        if (!is_dir($docsPath)) {
            mkdir($docsPath, 0755, true);
        }

        $filePath = $docsPath . '/api.json';
        file_put_contents($filePath, json_encode($docs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return true;
    }

    /**
     * Save OpenAPI documentation for all modules
     *
     * @return array
     */
    public function saveForAllModules(): array
    {
        $modulesPath = base_path('Modules');
        $modules = glob($modulesPath . '/*', GLOB_ONLYDIR);

        $results = [];

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $success = $this->saveForModule($moduleName);
            $results[$moduleName] = $success;
        }

        return $results;
    }

    /**
     * Load OpenAPI documentation from a module's JSON file
     *
     * @param string $moduleName
     * @return array|null
     */
    public function loadFromModule(string $moduleName): ?array
    {
        $filePath = base_path("Modules/{$moduleName}/docs/api.json");

        if (!file_exists($filePath)) {
            return null;
        }

        $json = file_get_contents($filePath);
        return json_decode($json, true);
    }

    /**
     * Load and merge OpenAPI documentation from all modules
     *
     * @return array
     */
    public function loadFromAllModules(): array
    {
        $modulesPath = base_path('Modules');
        $modules = glob($modulesPath . '/*', GLOB_ONLYDIR);

        $paths = [];
        $components = [
            'schemas' => [],
            'securitySchemes' => [],
        ];
        $tags = [];
        $tagGroups = [];

        // Add Auth tag first so it appears at the top
        $tags[] = [
            'name' => 'Auth',
            'description' => 'Authentication endpoints (login, register, logout)'
        ];

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $moduleDocs = $this->loadFromModule($moduleName);

            if (empty($moduleDocs)) {
                continue;
            }

            // Merge paths
            if (isset($moduleDocs['paths'])) {
                foreach ($moduleDocs['paths'] as $path => $methods) {
                    if (!isset($paths[$path])) {
                        $paths[$path] = $methods;
                    } else {
                        foreach ($methods as $method => $details) {
                            $paths[$path][$method] = $details;
                        }
                    }
                }
            }

            // Merge components
            if (isset($moduleDocs['components'])) {
                if (isset($moduleDocs['components']['schemas'])) {
                    $components['schemas'] = array_merge($components['schemas'], $moduleDocs['components']['schemas']);
                }
                if (isset($moduleDocs['components']['securitySchemes'])) {
                    $components['securitySchemes'] = array_merge($components['securitySchemes'], $moduleDocs['components']['securitySchemes']);
                }
            }

            // Merge tags
            if (isset($moduleDocs['tags'])) {
                $tags = array_merge($tags, $moduleDocs['tags']);
            }

            // Merge tag groups
            if (isset($moduleDocs['x-tagGroups'])) {
                $tagGroups = array_merge($tagGroups, $moduleDocs['x-tagGroups']);
            }
        }

        // Add main app routes (login, register, logout)
        $mainAppPaths = $this->generateMainAppRoutes();
        $paths = array_merge($paths, $mainAppPaths);

        $result = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'ERP Backend API',
                'description' => 'API documentation',
                'version' => '1.0.0',
            ],
            'servers' => [
                [
                    'url' => config('app.url') . '/api/',
                    'description' => 'API Server',
                ],
            ],
            'tags' => $tags,
            'paths' => $paths,
            'components' => $components,
        ];

        // Add x-tagGroups if available
        if (!empty($tagGroups)) {
            $result['x-tagGroups'] = $tagGroups;
        }

        return $result;
    }

    /**
     * Generate documentation for main app routes (login, register, logout)
     *
     * @return array
     */
    protected function generateMainAppRoutes(): array
    {
        $paths = [];

        // Register endpoint
        $paths['/register']['post'] = [
            'tags' => ['Auth'],
            'summary' => 'Register new user',
            'description' => 'Create a new user account',
            'operationId' => 'postregister',
            'requestBody' => [
                'required' => true,
                'content' => [
                    'multipart/form-data' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'user_name' => [
                                    'type' => 'string',
                                    'maxLength' => 255,
                                    'description' => 'User name'
                                ],
                                'email' => [
                                    'type' => 'string',
                                    'format' => 'email',
                                    'description' => 'User email address'
                                ],
                                'password' => [
                                    'type' => 'string',
                                    'minLength' => 6,
                                    'description' => 'User password (minimum 6 characters)'
                                ]
                            ],
                            'required' => ['user_name', 'email', 'password']
                        ]
                    ]
                ]
            ],
            'responses' => [
                '200' => [
                    'description' => 'User registered successfully',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'success' => ['type' => 'boolean'],
                                    'data' => ['type' => 'object'],
                                ]
                            ]
                        ]
                    ]
                ],
                '422' => [
                    'description' => 'Validation error'
                ]
            ]
        ];

        // Login endpoint
        $paths['/login']['post'] = [
            'tags' => ['Auth'],
            'summary' => 'Login user',
            'description' => 'Authenticate user and return token',
            'operationId' => 'postlogin',
            'requestBody' => [
                'required' => true,
                'content' => [
                    'multipart/form-data' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'email' => [
                                    'type' => 'string',
                                    'format' => 'email',
                                    'description' => 'User email address'
                                ],
                                'password' => [
                                    'type' => 'string',
                                    'description' => 'User password'
                                ]
                            ],
                            'required' => ['email', 'password']
                        ]
                    ]
                ]
            ],
            'responses' => [
                '200' => [
                    'description' => 'Login successful',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'success' => ['type' => 'boolean'],
                                    'data' => ['type' => 'object'],
                                ]
                            ]
                        ]
                    ]
                ],
                '401' => [
                    'description' => 'Invalid credentials'
                ]
            ]
        ];

        // Logout endpoint (requires auth)
        $paths['/logout']['post'] = [
            'tags' => ['Auth'],
            'summary' => 'Logout user',
            'description' => 'Logout user and invalidate token',
            'operationId' => 'postlogout',
            'security' => [['sanctum' => []]],
            'responses' => [
                '200' => [
                    'description' => 'Logout successful',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'success' => ['type' => 'boolean'],
                                    'message' => ['type' => 'string'],
                                ]
                            ]
                        ]
                    ]
                ],
                '401' => [
                    'description' => 'Unauthorized'
                ]
            ]
        ];

        return $paths;
    }
}
