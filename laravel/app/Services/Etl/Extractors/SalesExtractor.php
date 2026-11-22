<?php

namespace App\Services\Etl\Extractors;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class SalesExtractor
{
    /**
     * Extract sales header data from the MySQL source database.
     *
     * @return LazyCollection<int, object>
     */
    public function extract(): LazyCollection
    {
        return DB::connection('mysql_source')
            ->table('t_jual')
            ->select([
                'kd_jual',
                'tgl_jual',
                'jam_jual',
                'jns_jual',
                'kd_user',
                'kd_sls',
                'kd_plgn',
                'tot_ktr',
                'tot_dskn',
                'dskn_nota',
                'dskn_nota_rp',
                'dpp_jual',
                'ppn_jual',
                'ttl_jual',
                'byr_jual',
                'jth_tmp',
                'sts_jual',
                'kd_shift',
            ])
            ->orderBy('kd_jual')
            ->cursor();
    }
}
