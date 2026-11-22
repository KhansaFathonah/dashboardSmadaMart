<?php

namespace App\Services\Etl\Warehouse\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CategoryLoader
{
    /**
     * Load category records into the Data Warehouse.
     *
     * @param  Collection<int, array{
     *     kode_kategori: string,
     *     nama_kategori: string
     * }>  $categories
     */
    public function load(Collection $categories): int
    {
        if ($categories->isEmpty()) {
            return 0;
        }

        return DB::connection('pgsql_dw')
            ->table('smada.dim_kategori')
            ->upsert(
                $categories->all(),
                ['kode_kategori'],
                ['nama_kategori']
            );
    }
}
