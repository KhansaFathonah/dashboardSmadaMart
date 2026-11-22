<?php

namespace App\Services\Etl\Extractors;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseExtractor
{
    /**
     * Extract purchase header data from the MySQL source database.
     *
     * @return Collection<int, object>
     */
    public function extract(): Collection
    {
        return DB::connection('mysql_source')
            ->table('t_beli')
            ->select([
                'kd_beli',
                'tgl_beli',
                'jam_beli',
                'jns_beli',
                'kd_user',
                'kd_sup',
                'tot_ktr',
                'tot_dskn',
                'dskn_nota',
                'dskn_nota_rp',
                'dpp_beli',
                'ppn_beli',
                'ttl_beli',
                'byr_beli',
                'jth_tmp',
                'sts_beli',
                'kd_shift',
            ])
            ->orderBy('kd_beli')
            ->get();
    }
}
