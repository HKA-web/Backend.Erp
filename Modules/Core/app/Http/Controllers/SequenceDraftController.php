<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Modules\Core\Http\Requests\SequenceRequest;
use Illuminate\Support\Facades\DB;

class SequenceDraftController extends Controller
{
    public function __construct(protected readonly BaseService $baseService) {}

    public function index()
    {
        return $this->erpExecution(function () {
            // Membaca langsung dari tabel temporary
            $query = DB::table('temporary.core_sequence');
            return $this->erpResponse($query);
        });
    }

    public function store(SequenceRequest $request)
    {
        return $this->erpExecution(function () use ($request) {
            $this->baseService->executeProcedure('core.procedure_upsert_sequence_draft', $request->validated());

            return $this->erpResponse(
                message: "Draft Sequence saved successfully."
            );
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.core_sequence')
                ->where('temporary_id', $id);

            return $this->erpResponse($draft);
        });
    }

    public function update(SequenceRequest $request, $id)
    {
        return $this->erpExecution(function () use ($request, $id) {
            $payload = array_merge($request->validated(), ['temporary_id' => $id]);

            $this->baseService->executeProcedure('core.procedure_upsert_sequence_draft', $payload);

            return $this->erpResponse(message: "Draft updated.");
        });
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.core_sequence')
                ->where('temporary_id', $id)
                ->delete();

            return $this->erpResponse(message: "Draft discarded.");
        });
    }

    public function commit($id)
    {
        return $this->erpExecution(function () use ($id) {
            $payload = [
                'temporary_id' => $id,
                'sequence_id'  => request()->input('sequence_id')
            ];

            $this->baseService->executeProcedure('core.procedure_commit_sequence', $payload, Sequence::class);

            return $this->erpResponse(
                message: "Sequence committed to master successfully.",
                tags: ['sequence']
            );
        }, "Failed to commit draft.");
    }
}
