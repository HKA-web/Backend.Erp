<?php

namespace App\Services;

use App\Traits\SoftDelete;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BaseStaging
{
    public function executeStaging(string $procedureName, array $payload, array $tags = [])
    {
        $sessionId = request()->header('X-Session-ID') ?? Str::uuid()->toString();
        $payload['user_id'] = Auth::id();

        $result = DB::statement("CALL {$procedureName}(?, ?)", [
            $sessionId,
            json_encode($payload),
        ]);

        if ($result && ! empty($tags)) {
            Cache::tags($tags)->flush();
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
            return $this->executeStaging($procedureName, [
                $primaryKey => $id,
                'is_removed' => true,
            ]);
        } else {
            return $this->executeStaging($procedureName, [
                $primaryKey => $id,
                'is_removed' => true,
                'temporary_option' => 'D',
            ]);
        }
    }
}
