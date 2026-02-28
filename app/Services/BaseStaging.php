<?php

namespace App\Services;

use App\Traits\SoftDelete;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BaseStaging
{
    /**
     * Menjalankan Stored Procedure dengan otomatisasi session dan user_id.
     */
    public function executeStaging(string $procedureName, array $payload)
    {
        // 1. Ambil Session ID (Misal dari Session Laravel atau Header)
        $sessionId = request()->header('X-Session-ID') ?? Str::uuid()->toString();

        // 2. Suntikkan User ID ke dalam payload untuk audit history di SP
        $payload['user_id'] = Auth::id();

        // 3. Eksekusi Procedure
        return DB::statement("CALL {$procedureName}(?, ?)", [
            $sessionId,
            json_encode($payload)
        ]);
    }

    /**
     * Logika cerdas untuk menghapus data berdasarkan kebijakan model (Trait).
     */
    public function requestDelete($modelClass, $id, $procedureName)
    {
        $model = new $modelClass;
        $primaryKey = $model->getKeyName() ?? "{$this->model_lower}_id";

        // CEK: Apakah Model menggunakan Trait SoftDeletesStaging
        $traits = class_uses_recursive($model);
        $hasStagingTrait = in_array(SoftDelete::class, $traits);

        if ($hasStagingTrait) {
            // JIKA PAKAI TRAIT: Masukkan ke Staging (is_removed = true)
            return $this->executeStaging($procedureName, [
                $primaryKey  => $id,
                'is_removed' => true
            ]);
        }

        // JIKA TIDAK PAKAI TRAIT: Hard Delete Langsung di Master
        return $model->where($primaryKey, $id)->delete();
    }
}
