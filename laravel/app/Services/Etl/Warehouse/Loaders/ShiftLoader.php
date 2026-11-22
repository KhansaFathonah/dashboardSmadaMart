<?php

namespace App\Services\Etl\Warehouse\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShiftLoader
{
    /**
     * Load shift records into the Data Warehouse.
     *
     * @param Collection<int, array{
     *     kd_shift: string,
     *     tgl_buka: string,
     *     jam_buka: string,
     *     tgl_tutup: string,
     *     jam_tutup: string
     * }> $shifts
     */
    public function load(Collection $shifts): int
    {
        if ($shifts->isEmpty()) {
            return 0;
        }

        return DB::connection('pgsql_dw')
            ->table('smada.dim_shift')
            ->upsert(
                $shifts->all(),
                ['kd_shift'],
                ['tgl_buka', 'jam_buka', 'tgl_tutup', 'jam_tutup']
            );
    }
}
