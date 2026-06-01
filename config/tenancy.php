<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Domain;

return [
    /**
     * Tenant Model menggunakan custom Tenant yang mengimplementasikan TenantWithDatabase
     * Company dan Tenant dipisahkan untuk separation of concerns
     * - Company = business data (di tabel core.company)
     * - Tenant = infrastructure (di tabel tenants)
     */
    'tenant_model' => \App\Models\Tenant::class,
    
    /**
     * ID Generator menggunakan Custom String Generator (website diubah menjadi db_xxx_xxx)
     * Akan di-override di model melalui getTenantKey()
     */
    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,

    'domain_model' => Domain::class,

    /**
     * The list of domains hosting your central app.
     *
     * Only relevant if you're using the domain or subdomain identification middleware.
     */
    'central_domains' => [
        '127.0.0.1',
        'localhost',
    ],

    /**
     * Header name untuk tenant identification via InitializeTenancyByRequestData
     * Default: X-Tenant
     */
    'identification_header' => 'X-Tenant',

    /**
     * Tenancy bootstrappers are executed when tenancy is initialized.
     * Their responsibility is making Laravel features tenant-aware.
     *
     * To configure their behavior, see the config keys below.
     */
    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        // Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class, // Note: phpredis is needed
    ],

    /**
     * Database tenancy config. Used by DatabaseTenancyBootstrapper.
     * 
     * Konfigurasi ini menggunakan Multi-Database approach:
     * - Setiap tenant mendapatkan database fisik terpisah di PostgreSQL
     * - Nama database otomatis generated dari kolom 'website' dengan prefix 'db_'
     * - Contoh: website 'delta-tech.com' → database 'db_delta_tech_com'
     */
    'database' => [
        // Koneksi database central yang menampung tabel 'core.company', 'domains', dll
        'central_connection' => 'central',

        /**
         * Connection template untuk dynamically create tenant database connection.
         * Biarkan null agar package menggunakan setting default dari config/database.php
         */
        'template_tenant_connection' => null,

        /**
         * Tables that should not be tenant-aware
         * These tables will always use the central connection
         */
        'central_tables' => [
            'personal_access_tokens',
            'users',
        ],

        /**
         * Tenant database names are created like this:
         * prefix + tenant_id + suffix.
         * 
         * Contoh: 
         * - prefix: 'db_'
         * - tenant_id: 'delta_tech_com' (hasil transformasi dari 'delta-tech.com')
         * - suffix: ''
         * - Hasil: 'db_delta_tech_com'
         */
        'prefix' => 'db_',
        'suffix' => '',

        /**
         * TenantDatabaseManagers menangani pembuatan & penghapusan tenant database.
         * Kami menggunakan PostgreSQLDatabaseManager untuk Multi-Database (separate database per tenant)
         */
        'managers' => [
            'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'mariadb' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            // PostgreSQL dengan Multi-Database approach (bukan schema)
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,

        /**
         * Optional: Untuk Multi-Schema approach (semua tenant dalam 1 database, berbeda schema)
         * Uncomment baris di bawah jika ingin menggunakan schema separation instead
         */
            // 'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLSchemaManager::class,
        ],
    ],

    /**
     * Cache tenancy config. Used by CacheTenancyBootstrapper.
     *
     * This works for all Cache facade calls, cache() helper
     * calls and direct calls to injected cache stores.
     *
     * Each key in cache will have a tag applied on it. This tag is used to
     * scope the cache both when writing to it and when reading from it.
     *
     * You can clear cache selectively by specifying the tag.
     */
    'cache' => [
        'tag_base' => 'tenant', // This tag_base, followed by the tenant_id, will form a tag that will be applied on each cache call.
    ],

    /**
     * Filesystem tenancy config. Used by FilesystemTenancyBootstrapper.
     * https://tenancyforlaravel.com/docs/v3/tenancy-bootstrappers/#filesystem-tenancy-boostrapper.
     */
    'filesystem' => [
        /**
         * Each disk listed in the 'disks' array will be suffixed by the suffix_base, followed by the tenant_id.
         */
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
            // 's3',
        ],

        /**
         * Use this for local disks.
         *
         * See https://tenancyforlaravel.com/docs/v3/tenancy-bootstrappers/#filesystem-tenancy-boostrapper
         */
        'root_override' => [
            // Disks whose roots should be overridden after storage_path() is suffixed.
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],

        /**
         * Should storage_path() be suffixed.
         *
         * Note: Disabling this will likely break local disk tenancy. Only disable this if you're using an external file storage service like S3.
         *
         * For the vast majority of applications, this feature should be enabled. But in some
         * edge cases, it can cause issues (like using Passport with Vapor - see #196), so
         * you may want to disable this if you are experiencing these edge case issues.
         */
        'suffix_storage_path' => true,

        /**
         * By default, asset() calls are made multi-tenant too. You can use global_asset() and mix()
         * for global, non-tenant-specific assets. However, you might have some issues when using
         * packages that use asset() calls inside the tenant app. To avoid such issues, you can
         * disable asset() helper tenancy and explicitly use tenant_asset() calls in places
         * where you want to use tenant-specific assets (product images, avatars, etc).
         */
        'asset_helper_tenancy' => true,
    ],

    /**
     * Redis tenancy config. Used by RedisTenancyBootstrapper.
     *
     * Note: You need phpredis to use Redis tenancy.
     *
     * Note: You don't need to use this if you're using Redis only for cache.
     * Redis tenancy is only relevant if you're making direct Redis calls,
     * either using the Redis facade or by injecting it as a dependency.
     */
    'redis' => [
        'prefix_base' => 'tenant', // Each key in Redis will be prepended by this prefix_base, followed by the tenant id.
        'prefixed_connections' => [ // Redis connections whose keys are prefixed, to separate one tenant's keys from another.
            // 'default',
        ],
    ],

    /**
     * Features are classes that provide additional functionality
     * not needed for tenancy to be bootstrapped. They are run
     * regardless of whether tenancy has been initialized.
     *
     * See the documentation page for each class to
     * understand which ones you want to enable.
     */
    'features' => [
        // Stancl\Tenancy\Features\UserImpersonation::class,
        // Stancl\Tenancy\Features\TelescopeTags::class,
        // Stancl\Tenancy\Features\UniversalRoutes::class,
        // Stancl\Tenancy\Features\TenantConfig::class, // https://tenancyforlaravel.com/docs/v3/features/tenant-config
        // Stancl\Tenancy\Features\CrossDomainRedirect::class, // https://tenancyforlaravel.com/docs/v3/features/cross-domain-redirect
        // Stancl\Tenancy\Features\ViteBundler::class,
    ],

    /**
     * Should tenancy routes be registered.
     *
     * Tenancy routes include tenant asset routes. By default, this route is
     * enabled. But it may be useful to disable them if you use external
     * storage (e.g. S3 / Dropbox) or have a custom asset controller.
     */
    'routes' => true,

    /**
     * Parameters used by the tenants:migrate command.
     * 
     * Migration files tenant harus diletakkan di folder database/migrations/tenant
     * di root Laravel atau di dalam module yang sesuai
     * 
     * NOTE: Path migration di-set secara dinamis oleh TenantMigrationServiceProvider
     * untuk auto-discover semua module yang memiliki folder database/migrations/tenant
     */
    'migration_parameters' => [
        '--force' => true, // Perlu true untuk production migration
        '--path' => [], // Diisi otomatis oleh TenantMigrationServiceProvider
        '--realpath' => true,
    ],

    /**
     * Parameters used by the tenants:seed command.
     * 
     * NOTE: Seeder class di-set secara dinamis oleh TenantMigrationServiceProvider
     * untuk auto-discover semua module yang memiliki TenantDatabaseSeeder
     */
    'seeder_parameters' => [
        '--class' => 'Modules\\Core\\Database\\Seeders\\TenantDatabaseSeeder', // Default, bisa di-override oleh service provider
        '--force' => true,
    ],
];
