<?php

namespace App\Services\Etl\Warehouse\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WaktuLoader
{
    /**
     * Load time dimension records into the Data Warehouse.
     *
     * @param  Collection<int, array{
     *     tanggal: string,
     *     nama_hari: string,
     *     bulan: int,
     *     tahun: int
     * }>  $records
     */
    public function load(Collection $records): int
    {
        if ($records->isEmpty()) {
            return 0;
        }

        return DB::connection('pgsql_dw')
            ->table('smada.dim_waktu')
            ->upsert(
                $records->all(),
                ['tanggal'],
                [
                    'nama_hari',
                    'bulan',
                    'tahun',
                ]
            );
    }
}
