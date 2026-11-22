<?php

namespace App\Services\Etl\Extractors;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CategoryExtractor
{
    /**
     * Extract category data from the source MySQL database.
     *
     * @return Collection<int, object>
     */
    public function extract(): Collection
    {
        return DB::connection('mysql_source')
            ->table('t_kat')
            ->select([
                'kd_kat',
                'nm_kat',
                'sts_kat',
            ])
            ->orderBy('kd_kat')
            ->get();
    }
}
