<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Requests\CompanyRequest;

class CompanyDraftController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            // Membaca langsung dari tabel temporary
            $query = DB::table('temporary.core_company');

            return $this->erpResponse($query);
        });
    }

    public function store(CompanyRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {
            $staging->executeStaging('core.procedure_upsert_company_draft', $request->validated());

            return $this->erpResponse(
                message: 'Draft Company saved successfully.'
            );
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.core_company')
                ->where('temporary_id', $id);

            return $this->erpResponse($draft);
        });
    }

    public function update(CompanyRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {
            $payload = array_merge($request->validated(), ['temporary_id' => $id]);

            $staging->executeStaging('core.procedure_upsert_company_draft', $payload);

            return $this->erpResponse(message: 'Draft updated.');
        });
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.core_company')
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
                'company_id' => request()->input('company_id'),
            ];

            $staging->executeStaging('core.procedure_commit_company', $payload);

            return $this->erpResponse(
                message: 'Company committed to master successfully.',
                tags: ['company']
            );
        }, 'Failed to commit draft.');
    }
}
