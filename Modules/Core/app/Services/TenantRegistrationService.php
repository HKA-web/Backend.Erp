<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Company;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Events\TenantCreated;

/**
 * TenantRegistrationService - Handle registrasi tenant baru dengan multi-database
 * Service ini menangani proses lengkap:
 * 1. Validasi data Company
 * 2. Membuat record di database central (tabel core.company)
 * 3. Trigger pembuatan database fisik tenant
 * 4. Menjalankan migrasi tenant
 * 5. Mendaftarkan domain akses
 * 6. Seeding data default (optional)
 */
class TenantRegistrationService
{
    /**
     * Buat tenant baru dengan proses lengkap
     */
    public function createTenant(
        array $companyData,
        array $domainData = [],
        bool $runSeeder = false
    ): Company {
        try {
            // Step 1: Validasi data
            $this->validateCompanyData($companyData);
            
            // Step 2: Buat Company record di database central
            $company = Company::create($companyData);
            
            // Step 3: Trigger database creation
            // getTenantKey() akan transform: 'delta-tech.com' → 'delta_tech_com'
            // Database name: 'db_delta_tech_com'
            event(new TenantCreated($company));
            
            // Step 4: Jalankan migrasi tenant
            $this->runTenantMigrations($company);
            
            // Step 5: Jalankan seeder (optional)
            if ($runSeeder) {
                $this->runTenantSeeder($company);
            }
            
            // Step 6: Daftarkan domain
            $this->registerDomains($company, $domainData);
            
            return $company;
            
        } catch (\Exception $e) {
            // Cleanup: hapus database jika ada error
            if (isset($company)) {
                $this->deleteTenantDatabase($company);
            }
            throw new \Exception("Gagal membuat tenant: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Validasi data Company
     */
    private function validateCompanyData(array $data): void
    {
        // Cek required fields
        $required = ['company_id', 'company_name', 'website', 'email', 'phone', 'address'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field '{$field}' harus diisi");
            }
        }

        // Cek format website (alphanumeric, dash, dot only)
        if (!preg_match('/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?)*$/', $data['website'])) {
            throw new \Exception("Format website tidak valid");
        }

        // Cek website unique
        if (Company::where('website', $data['website'])->exists()) {
            throw new \Exception("Website sudah terdaftar: {$data['website']}");
        }

        // Cek email valid
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Format email tidak valid");
        }

        // Cek email unique
        if (Company::where('email', $data['email'])->exists()) {
            throw new \Exception("Email sudah terdaftar: {$data['email']}");
        }
    }

    /**
     * Jalankan migrasi tenant di database tenant yang baru dibuat
     */
    private function runTenantMigrations(Company $company): void
    {
        try {
            $exitCode = Artisan::call('tenants:migrate', [
                '--tenants' => [$company->getTenantKey()],
                '--force' => true,
            ]);
            
            if ($exitCode !== 0) {
                throw new \Exception("Migrasi tenant gagal (exit code: {$exitCode})");
            }
            
        } catch (\Exception $e) {
            throw new \Exception("Error running migrations: {$e->getMessage()}");
        }
    }

    /**
     * Jalankan seeder tenant
     */
    private function runTenantSeeder(Company $company): void
    {
        try {
            $exitCode = Artisan::call('tenants:seed', [
                '--tenants' => [$company->getTenantKey()],
                '--force' => true,
            ]);
            
            if ($exitCode !== 0) {
                Log::warning("Seeder tenant gagal untuk {$company->getTenantKey()}");
            }
            
        } catch (\Exception $e) {
            Log::warning("Tenant seeder error: {$e->getMessage()}");
        }
    }

    /**
     * Daftarkan domain untuk akses tenant
     */
    private function registerDomains(Company $company, array $domains): void
    {
        // Auto-generate subdomain jika tidak ada domain
        if (empty($domains)) {
            $domains = [[
                'domain' => "{$company->website}.saasmu.com",
                'is_primary' => true,
            ]];
        }

        // Looping dan daftarkan setiap domain
        foreach ($domains as $domainData) {
            try {
                Domain::create([
                    'tenant_id' => $company->getTenantKey(),
                    'domain' => $domainData['domain'],
                ]);
            } catch (\Exception $e) {
                throw new \Exception("Gagal register domain: {$e->getMessage()}");
            }
        }
    }

    /**
     * Hapus database tenant (rollback jika ada error)
     */
    private function deleteTenantDatabase(Company $company): void
    {
        try {
            $company->delete();
        } catch (\Exception $e) {
            Log::error("Error deleting tenant database: {$e->getMessage()}");
        }
    }
}
