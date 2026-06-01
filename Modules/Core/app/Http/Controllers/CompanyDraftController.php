<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use App\Services\CacheService;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Requests\CompanyRequest;
use Modules\Core\Models\Company;
use Modules\Core\Services\TenantRegistrationService;

class CompanyDraftController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            // Membaca langsung dari tabel temporary
            $query = DB::table('temporary.core_company');

            return $this->erpResponse($query, cache: false);
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

            return $this->erpResponse($draft, cache: false);
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

    public function commit($id, BaseStaging $staging, TenantRegistrationService $tenantService)
    {
        return $this->erpExecution(function () use ($staging, $id, $tenantService) {
            $payload = [
                'temporary_id' => $id,
                'company_id' => request()->input('company_id'),
            ];

            $staging->executeStaging('core.procedure_commit_company', $payload);

            // Clear cache for company model
            $company = Company::find(request()->input('company_id'));
            if ($company) {
                CacheService::clearCache($company);
            }

            // $company = Company::where('company_id', $payload['company_id'])->first();

            // if ($company && $company->status === 'POSTED') {
            //     $domains = request()->input('domains', []);
            //     $runSeeder = request()->input('run_seeder', false);

            //     $tenantService->createTenant(
            //         companyData: $company->toArray(),
            //         domainData: $domains,
            //         runSeeder: $runSeeder,
            //     );

            //     return $this->erpResponse(
            //         message: 'Company committed to master and tenant created successfully.',
            //         tags: ['company']
            //     );
            // }

            // return $this->erpResponse(
            //     message: 'Company committed to master but tenant creation skipped (status is not POSTED).',
            //     tags: ['company']
            // );
        }, 'Failed to commit draft and create tenant.');
    }
}
