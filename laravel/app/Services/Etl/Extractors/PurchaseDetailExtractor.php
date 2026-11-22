<?php

namespace App\Services\Etl\Extractors;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class PurchaseDetailExtractor
{
    /**
     * Extract purchase detail data from the MySQL source database.
     *
     * @return LazyCollection<int, object>
     */
    public function extract(): LazyCollection
    {
        return DB::connection('mysql_source')
            ->table('t_beli_detail')
            ->select([
                'kd_beli',
                'kd_brg',
                'stok_beli',
                'jml_beli',
                'hpp_beli',
                'hrg_beli',
                'dskn_beli',
                'tot_diskon',
                'ppn_detail',
                'total_detail',
            ])
            ->orderBy('kd_beli')
            ->orderBy('kd_brg')
            ->cursor();
    }
}
