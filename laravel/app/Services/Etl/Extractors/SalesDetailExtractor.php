<?php

namespace App\Services\Etl\Extractors;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class SalesDetailExtractor
{
    /**
     * Extract sales detail data from the MySQL source database.
     *
     * @return LazyCollection<int, object>
     */
    public function extract(): LazyCollection
    {
        return DB::connection('mysql_source')
            ->table('t_jual_detail')
            ->select([
                'kd_jual',
                'kd_brg',
                'stok_jual',
                'jml_jual',
                'hpp_jual',
                'hrg_jual',
                'dskn_jual_1',
                'dskn_jual_2',
                'dskn_jual_3',
                'tot_diskon',
                'ppn_detail',
                'total_detail',
            ])
            ->orderBy('kd_jual')
            ->orderBy('kd_brg')
            ->cursor();
    }
}
