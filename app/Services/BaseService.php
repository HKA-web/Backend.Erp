<?php

namespace App\Services;

use App\Traits\SoftDelete;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class BaseService
{
    public function executeProcedure(string $procedureName, array $payload, string|array|null $models = null)
    {
        $sessionId = request()->header('X-Session-ID') ?? Str::uuid()->toString();
        $payload['user_id'] = Auth::id();

        $result = DB::statement("CALL {$procedureName}(?, ?)", [
            $sessionId,
            json_encode($payload),
        ]);

        if ($result && !empty($models)) {
            $modelsToClear = is_array($models) ? $models : [$models];
            foreach ($modelsToClear as $modelClass) {
                if (class_exists($modelClass)) {
                    CacheService::clearCache(new $modelClass);
                }
            }
        }

        return $result;
    }

    public function requestDelete($modelClass, $id, $procedureName)
    {
        $model = new $modelClass;
        $primaryKey = $model->getKeyName();

        $traits = class_uses_recursive($model);
        $hasStagingTrait = in_array(SoftDelete::class, $traits);

        if ($hasStagingTrait) {
            return $this->executeProcedure($procedureName, [
                $primaryKey => $id,
                'is_removed' => true,
            ], $modelClass);
        } else {
            return $this->executeProcedure($procedureName, [
                $primaryKey => $id,
                'is_removed' => true,
                'temporary_option' => 'D',
            ], $modelClass);
        }
    }
}
