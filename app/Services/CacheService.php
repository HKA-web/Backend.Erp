<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final readonly class CacheService
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
        $tags = [$fullModel];

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
        $tags = ["{$fullModel}:draft"];
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
                "{$baseTag}:list"
            ]);
        } else {
            $tenantPrefix = $tenantId ? "tenant.{$tenantId}" : null;
            if ($tenantPrefix) {
                self::clearTags([
                    "{$tenantPrefix}.{$baseTag}",
                    "{$tenantPrefix}.{$baseTag}:{$id}",
                    "{$tenantPrefix}.{$baseTag}:list"
                ]);
            }
        }

        // Clear related models
        $relatedTags = self::getRelatedTags($model, $tenantId, $isCentral);
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
        if (method_exists($model, 'getConnectionName')) {
            return $model->getConnectionName() !== 'tenant';
        }

        static $centralModels = [
            \App\Models\Tenant::class,
            \Stancl\Tenancy\Database\Models\Domain::class,
        ];

        return in_array(get_class($model), $centralModels, true);
    }

    /**
     * Get tags for related models based on $clearsCache property on the model.
     * Extremely fast compared to reflection.
     */
    protected static function getRelatedTags($model, ?string $tenantId, bool $isCentral): array
    {
        $tags = [];

        // Modern approach: Model mendefinisikan relasi apa saja yang ingin di-clear cache-nya
        // Contoh di Model: public array $clearsCache = ['roles', 'permissions'];
        $relationsToClear = $model->clearsCache ?? [];

        if (empty($relationsToClear) || !is_array($relationsToClear)) {
            return $tags;
        }

        foreach ($relationsToClear as $method) {
            try {
                if (!method_exists($model, $method)) {
                    continue;
                }

                $relation = $model->{$method}();
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
                Log::debug("Skip relation cache clear", ['method' => $method, 'error' => $e->getMessage()]);
            }
        }

        return $tags;
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
