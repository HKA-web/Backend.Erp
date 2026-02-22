<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BaseStaging
{
    /**
     * Jalankan proses Staging ke Master
     * * @param string $tempTable Nama tabel temporary (e.g., temporary.core_village)
     * @param string $procedureName Nama prosedur (e.g., core.commit_village)
     * @param array $data Data dari request
     */
    public function process(string $tempTable, string $procedureName, array $data)
    {
        return DB::transaction(function () use ($tempTable, $procedureName, $data) {
            // 1. Generate atau ambil Session ID
            $sessionId = request()->header('x-session-id', (string) Str::uuid());

            // 2. Insert ke Tabel Temporary
            // Kita gunakan DB table agar fleksibel tanpa Model khusus
            DB::table($tempTable)->insert(array_merge($data, [
                'session_id' => $sessionId,
            ]));

            // 3. Panggil Stored Procedure
            // PostgreSQL menggunakan CALL untuk procedure
            try {
                DB::statement("CALL {$procedureName}(?)", [$sessionId]);
            } catch (\Exception $e) {
                // Lempar kembali agar transaksi rollback
                throw new \Exception("Database Procedure Error: " . $e->getMessage());
            }

            return [
                'status' => 'success',
                'session_id' => $sessionId
            ];
        });
    }

    public function executeStaging(string $procedureName, array $data)
    {
        $sessionId = request()->header('x-session-id');

        if (!$sessionId) {
            throw new \Exception("x-session-id is required.", 400);
        }

        return DB::statement("CALL {$procedureName}(?, ?)", [
            $sessionId,
            json_encode($data)
        ]);
    }
}
