<?php

namespace App\Services\Etl\Loaders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class PurchaseDetailLoader
{
    /**
     * Load purchase detail data into the PostgreSQL staging database.
     *
     * @param  LazyCollection<int, object>  $details
     */
    public function load(LazyCollection $details, string $batchId): int
    {
        $loaded = 0;

        DB::connection('pgsql_staging')->transaction(function () use ($details, $batchId, &$loaded): void {
            $details->chunk(250)->each(function (LazyCollection $chunk) use ($batchId, &$loaded): void {
                $records = $chunk->map(function (object $detail) use ($batchId): array {
                    return [
                        'kd_beli' => $detail->kd_beli,
                        'kd_brg' => $detail->kd_brg,
                        'stok_beli' => $detail->stok_beli,
                        'jml_beli' => $detail->jml_beli,
                        'hpp_beli' => $detail->hpp_beli,
                        'hrg_beli' => $detail->hrg_beli,
                        'dskn_beli' => $detail->dskn_beli,
                        'tot_diskon' => $detail->tot_diskon,
                        'ppn_detail' => $detail->ppn_detail,
                        'total_detail' => $detail->total_detail,
                        'batch_id' => $batchId,
                        'extracted_at' => now(),
                    ];
                })->all();

                DB::connection('pgsql_staging')
                    ->table('smada.t_beli_detail')
                    ->insert($records);

                $loaded += count($records);
            });
        });

        return $loaded;
    }
}
