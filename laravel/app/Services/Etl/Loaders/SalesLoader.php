<?php

namespace App\Services\Etl\Loaders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class SalesLoader
{
    /**
     * Load sales header data into the PostgreSQL staging database.
     *
     * @param  LazyCollection<int, object>  $sales
     */
    public function load(LazyCollection $sales, string $batchId): int
    {
        $extractedAt = now()->toDateTimeString();
        $processedRows = 0;

        foreach ($sales->chunk(250) as $salesChunk) {
            $rows = $salesChunk->map(
                static fn (object $sale): array => [
                    'kd_jual' => $sale->kd_jual,
                    'tgl_jual' => $sale->tgl_jual,
                    'jam_jual' => $sale->jam_jual,
                    'jns_jual' => $sale->jns_jual,
                    'kd_user' => $sale->kd_user,
                    'kd_sls' => $sale->kd_sls,
                    'kd_plgn' => $sale->kd_plgn,
                    'tot_ktr' => $sale->tot_ktr,
                    'tot_dskn' => $sale->tot_dskn,
                    'dskn_nota' => $sale->dskn_nota,
                    'dskn_nota_rp' => $sale->dskn_nota_rp,
                    'dpp_jual' => $sale->dpp_jual,
                    'ppn_jual' => $sale->ppn_jual,
                    'ttl_jual' => $sale->ttl_jual,
                    'byr_jual' => $sale->byr_jual,
                    'jth_tmp' => $sale->jth_tmp,
                    'sts_jual' => $sale->sts_jual,
                    'kd_shift' => $sale->kd_shift,
                    'batch_id' => $batchId,
                    'extracted_at' => $extractedAt,
                ]
            )->all();

            $processedRows += DB::connection('pgsql_staging')
                ->table('smada.t_jual')
                ->upsert(
                    $rows,
                    ['kd_jual'],
                    [
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
                        'batch_id',
                        'extracted_at',
                    ]
                );
        }

        return $processedRows;
    }
}
