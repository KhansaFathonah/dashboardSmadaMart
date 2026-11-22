<?php

namespace App\Services\Etl\Extractors;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplierExtractor
{
    /**
     * Extract supplier data from the source MySQL database.
     *
     * @return Collection<int, object>
     */
    public function extract(): Collection
    {
        return DB::connection('mysql_source')
            ->table('t_sup')
            ->select([
                'kd_sup',
                'nm_sup',
                'almt_sup',
                'tlpn_sup',
                'fax_sup',
                'sts_sup',
            ])
            ->orderBy('kd_sup')
            ->get();
    }
}
