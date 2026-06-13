<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Requests\DictionaryRequest;
use Modules\Core\Models\Dictionary;

class DictionaryDraftController extends Controller
{
    public function __construct(protected readonly BaseService $baseService) {}

    public function index()
    {
        return $this->erpExecution(function () {
            $query = DB::table('temporary.core_dictionary');

            return $this->erpResponse($query, cache: false);
        });
    }

    public function store(DictionaryRequest $request)
    {
        return $this->erpExecution(function () use ($request) {
            $this->baseService->executeProcedure('core.procedure_upsert_dictionary_draft', $request->validated());

            return $this->erpResponse(
                message: 'Draft Dictionary saved successfully.'
            );
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.core_dictionary')
                ->where('temporary_id', $id);

            return $this->erpResponse($draft, cache: false);
        });
    }

    public function update(DictionaryRequest $request, $id)
    {
        return $this->erpExecution(function () use ($request, $id) {
            $payload = array_merge($request->validated(), ['temporary_id' => $id]);

            $this->baseService->executeProcedure('core.procedure_upsert_dictionary_draft', $payload);

            return $this->erpResponse(message: 'Draft updated.');
        });
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.core_dictionary')
                ->where('temporary_id', $id)
                ->delete();

            return $this->erpResponse(message: 'Draft discarded.');
        });
    }

    public function commit($id)
    {
        return $this->erpExecution(function () use ($id) {
            $payload = [
                'temporary_id' => $id,
                'dictionary_id' => request()->input('dictionary_id'),
            ];

            $this->baseService->executeProcedure('core.procedure_commit_dictionary', $payload, Dictionary::class);

                        

            return $this->erpResponse(
                message: 'Dictionary committed to master successfully.',
                tags: ['dictionary']
            );
        }, 'Failed to commit draft.');
    }
}
