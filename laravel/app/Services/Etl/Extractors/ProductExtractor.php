<?php

namespace App\Services\Etl\Extractors;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductExtractor
{
    /**
     * Extract product data from the source MySQL database.
     *
     * @return Collection<int, object>
     */
    public function extract(): Collection
    {
        return DB::connection('mysql_source')
            ->table('t_brg')
            ->select([
                'kd_brg',
                'kd_kat',
                'kd_sup',
                'nm_brg',
                'bar_brg',
                'sat_kecil',
                'jml_kecil',
                'sat_besar',
                'jml_besar',
                'min_brg',
                'max_brg',
                'hrg_beli',
                'hrg_pkok',
                'hrg_jual_a',
                'hrg_jual_b',
                'min_jual_b',
                'hrg_jual_c',
                'min_jual_c',
                'hrg_grsr',
                'dskn_grosir',
                'dskn_brg_1',
                'dskn_brg_2',
                'dskn_brg_3',
                'dskn_brg_rp',
                'bkp',
                'stok_brg',
                'stok_msk',
                'stok_psn',
                'stok_jual',
                'stok_adj',
                'stok_rtr_beli',
                'stok_rtr_jual',
                'sts_brg',
            ])
            ->orderBy('kd_brg')
            ->get();
    }
}
