<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use App\Services\CacheService;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Requests\OptionRequest;
use Modules\Core\Models\Option;

class OptionDraftController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            $query = DB::table('temporary.core_option');

            return $this->erpResponse($query, cache: false);
        });
    }

    public function store(OptionRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {
            $staging->executeStaging('core.procedure_upsert_option_draft', $request->validated());

            return $this->erpResponse(
                message: 'Draft Option saved successfully.'
            );
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.core_option')
                ->where('temporary_id', $id);

            return $this->erpResponse($draft, cache: false);
        });
    }

    public function update(OptionRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {
            $payload = array_merge($request->validated(), ['temporary_id' => $id]);

            $staging->executeStaging('core.procedure_upsert_option_draft', $payload);

            return $this->erpResponse(message: 'Draft updated.');
        });
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.core_option')
                ->where('temporary_id', $id)
                ->delete();

            return $this->erpResponse(message: 'Draft discarded.');
        });
    }

    public function commit($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {
            $payload = [
                'temporary_id' => $id,
                'option_id' => request()->input('option_id'),
            ];

            $staging->executeStaging('core.procedure_commit_option', $payload);

            // Clear cache for option model
            $option = Option::find(request()->input('option_id'));
            if ($option) {
                CacheService::clearCache($option);
            }

            return $this->erpResponse(
                message: 'Option committed to master successfully.',
                tags: ['option']
            );
        }, 'Failed to commit draft.');
    }
}
