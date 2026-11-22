<?php

namespace App\Services\Etl\Extractors;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShiftExtractor
{
    /**
     * Extract shift data from the source MySQL database.
     *
     * @return Collection<int, object>
     */
    public function extract(): Collection
    {
        return DB::connection('mysql_source')
            ->table('t_shift')
            ->select([
                'kd_shift',
                'kd_user',
                'tgl_buka',
                'jam_buka',
                'modal_buka',
                'jual_struk',
                'jual_qty',
                'jual_diskon',
                'jual_rp',
                'rtr_jual_struk',
                'sts_jual_qty',
                'rtr_jual_diskon',
                'rtr_jual_rp',
                'beli_struk',
                'beli_qty',
                'beli_diskon',
                'beli_rp',
                'rtr_beli_struk',
                'rtr_beli_qty',
                'rtr_beli_diskon',
                'rtr_beli_rp',
                'hutang',
                'bayar_hutang',
                'piutang',
                'bayar_piutang',
                'masuk',
                'keluar',
                'total_komputer',
                'input_kasir',
                'selisih',
                'tgl_tutup',
                'jam_tutup',
                'sts_shift',
            ])
            ->orderBy('kd_shift')
            ->get();
    }
}
