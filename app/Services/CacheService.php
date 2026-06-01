<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Get cached data with tags
     */
    public static function remember(string $key, array $tags, $callback, $ttl = null)
    {
        $ttl = $ttl ?? config('cache.ttl', 3600);

        return Cache::tags($tags)->remember($key, $ttl, $callback);
    }

    /**
     * Clear cache by tags
     */
    public static function clearTags(array $tags): void
    {
        if (empty($tags)) {
            return;
        }
        
        Cache::tags($tags)->flush();
        Log::debug('Cache cleared for tags:', $tags);
    }

    /**
     * Clear cache by single tag
     */
    public static function clearTag(string $tag): void
    {
        Cache::tags([$tag])->flush();
        Log::debug('Cache cleared for tag:', [$tag]);
    }

    /**
     * Generate cache key for model
     */
    public static function modelKey(string $schema, string $model, string $id, string $suffix = ''): string
    {
        $fullModel = $schema ? "{$schema}.{$model}" : $model;
        return "{$fullModel}:{$id}" . ($suffix ? ":{$suffix}" : '');
    }

    /**
     * Generate cache key for list
     */
    public static function listKey(string $schema, string $model, array $params = []): string
    {
        $fullModel = $schema ? "{$schema}.{$model}" : $model;
        $paramsString = http_build_query($params);
        return "{$fullModel}:list" . ($paramsString ? ":{$paramsString}" : '');
    }

    /**
     * Get tags for model
     */
    public static function modelTags(string $schema, string $model, ?string $id = null): array
    {
        $fullModel = $schema ? "{$schema}.{$model}" : $model;
        $tags = [$fullModel, 'all'];

        if ($id) {
            $tags[] = "{$fullModel}:{$id}";
        }

        return $tags;
    }

    /**
     * Clear cache for model (single record and list)
     * This should be called AFTER data is committed to database
     */
    public static function clearModel(string $schema, string $model, ?string $id = null): void
    {
        $tags = self::modelTags($schema, $model, $id);
        self::clearTags($tags);
    }

    /**
     * Clear cache for related models (cascade)
     * Format: ['schema.model' => [id1, id2], 'schema2.model2' => null]
     */
    public static function clearRelated(array $models): void
    {
        $tags = [];

        foreach ($models as $fullModel => $ids) {
            // Parse schema.model
            $parts = explode('.', $fullModel);
            if (count($parts) === 2) {
                $schema = $parts[0];
                $model = $parts[1];
            } else {
                $schema = null;
                $model = $parts[0];
            }

            if (is_array($ids)) {
                foreach ($ids as $id) {
                    $tags = array_merge($tags, self::modelTags($schema, $model, $id));
                }
            } else {
                $tags = array_merge($tags, self::modelTags($schema, $model));
            }
        }

        self::clearTags($tags);
    }

    /**
     * Clear ALL cache (use sparingly, typically for debugging)
     */
    public static function clearAll(): void
    {
        Cache::tags(['all'])->flush();
        Log::warning('All cache cleared');
    }

    /**
     * Clear draft cache for a model
     */
    public static function clearDraft(string $schema, string $model): void
    {
        $fullModel = $schema ? "{$schema}.{$model}" : $model;
        $tags = ["{$fullModel}:draft", 'all'];
        self::clearTags($tags);
    }

    /**
     * Smart cache clear - auto scan & clear model + relations
     * Multi-tenant aware + Central database safe
     * Call this AFTER database commit (create, update, delete)
     */
    public static function clearCache($model, ?string $tenantId = null): void
    {
        if (!$model || !$id = $model->id ?? null) {
            return;
        }

        $tenantId ??= self::getCurrentTenantId();
        $isCentral = self::isCentralModel($model);
        $schema = method_exists($model, 'getSchema') ? $model->getSchema() : null;
        $modelName = strtolower(class_basename($model));
        $baseTag = $schema ? "{$schema}.{$modelName}" : $modelName;

        // Clear specific record + list cache
        if ($isCentral) {
            self::clearTags([
                $baseTag,
                "{$baseTag}:{$id}",
                "{$baseTag}:list",
                'all'
            ]);
        } else {
            $tenantPrefix = $tenantId ? "tenant.{$tenantId}" : null;
            if ($tenantPrefix) {
                self::clearTags([
                    "{$tenantPrefix}.{$baseTag}",
                    "{$tenantPrefix}.{$baseTag}:{$id}",
                    "{$tenantPrefix}.{$baseTag}:list",
                    'all'
                ]);
            }
        }

        // Clear related models
        $relatedTags = self::scanRelations($model, $tenantId, $isCentral);
        if ($relatedTags) {
            self::clearTags($relatedTags);
        }

        Log::info("Cache cleared for {$modelName}", [
            'tenant_id' => $tenantId,
            'is_central' => $isCentral,
            'id' => $id,
            'relations' => count($relatedTags ?? []),
        ]);
    }

    /**
     * Get current tenant ID safely
     */
    protected static function getCurrentTenantId(): ?string
    {
        try {
            if (function_exists('tenant') && $tenant = tenant()) {
                return $tenant->id;
            }

            if (class_exists('Stancl\Tenancy\Facades\Tenancy')) {
                if ($tenant = \Stancl\Tenancy\Facades\Tenancy::tenant()) {
                    return $tenant->id;
                }
            }
        } catch (\Throwable $e) {
            Log::debug("Failed to get tenant ID", ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Check if model is central (no tenant isolation)
     */
    protected static function isCentralModel($model): bool
    {
        static $centralModels = [
            \App\Models\Tenant::class,
            \Stancl\Tenancy\Database\Models\Domain::class,
        ];

        return in_array(get_class($model), $centralModels, true);
    }

    /**
     * Scan model relations otomatis dengan tenant awareness
     */
    protected static function scanRelations($model, ?string $tenantId, bool $isCentral): array
    {
        $tags = [];

        try {
            $reflection = new \ReflectionClass($model);

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if (!self::isRelationMethod($method, $model)) {
                    continue;
                }

                try {
                    $relation = $model->{$method->getName()}();
                    if (!method_exists($relation, 'getRelated')) {
                        continue;
                    }

                    $relatedModel = $relation->getRelated();
                    $relatedSchema = method_exists($relatedModel, 'getSchema') ? $relatedModel->getSchema() : null;
                    $relatedName = strtolower(class_basename($relatedModel));
                    $relatedIsCentral = self::isCentralModel($relatedModel);

                    $tag = self::buildTag($relatedSchema, $relatedName, $tenantId, $relatedIsCentral);
                    if ($tag && !in_array($tag, $tags, true)) {
                        $tags[] = $tag;
                    }
                } catch (\Throwable $e) {
                    Log::debug("Skip relation", ['method' => $method->getName(), 'error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            Log::debug("Scan relations failed", ['error' => $e->getMessage()]);
        }

        return $tags;
    }

    /**
     * Check apakah method adalah relation
     */
    protected static function isRelationMethod(\ReflectionMethod $method, $model): bool
    {
        if ($method->isPrivate() || $method->isProtected() || str_starts_with($method->getName(), '_')) {
            return false;
        }

        if ($method->getDeclaringClass()->getName() !== get_class($model)) {
            return false;
        }

        try {
            $result = $model->{$method->getName()}();
            return is_object($result) && method_exists($result, 'getRelated');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Build tag dari schema, model name, tenant & central status
     */
    protected static function buildTag(?string $schema, string $model, ?string $tenantId, bool $isCentral): ?string
    {
        $baseTag = $schema ? "{$schema}.{$model}" : $model;

        if ($isCentral) {
            return $baseTag;
        }

        return $tenantId ? "tenant.{$tenantId}.{$baseTag}" : null;
    }
}
