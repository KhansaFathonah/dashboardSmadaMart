<?php

namespace App\Services\Etl\Warehouse\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplierLoader
{
    /**
     * Load supplier records into the Data Warehouse.
     *
     * @param Collection<int, array{
     *     kode_supplier: string,
     *     nama_supplier: string,
     *     alamat_supplier: ?string
     * }> $suppliers
     */
    public function load(Collection $suppliers): int
    {
        if ($suppliers->isEmpty()) {
            return 0;
        }

        return DB::connection('pgsql_dw')
            ->table('smada.dim_supplier')
            ->upsert(
                $suppliers->all(),
                ['kode_supplier'],
                ['nama_supplier', 'alamat_supplier']
            );
    }
}
