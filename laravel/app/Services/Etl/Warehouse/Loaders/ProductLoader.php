<?php

namespace App\Services\Etl\Warehouse\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductLoader
{
    /**
     * Load product records into the Data Warehouse.
     *
     * @param Collection<int, array{
     *     kode_produk: string,
     *     id_kategori: int,
     *     nama_produk: string,
     *     satuan: string
     * }> $products
     */
    public function load(Collection $products): int
    {
        if ($products->isEmpty()) {
            return 0;
        }

        return DB::connection('pgsql_dw')
            ->table('smada.dim_produk')
            ->upsert(
                $products->all(),
                ['kode_produk'],
                ['id_kategori', 'nama_produk', 'satuan']
            );
    }
}
