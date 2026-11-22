<?php

namespace App\Services\Etl\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseLoader
{
    /**
     * Load purchase header data into the PostgreSQL staging database.
     *
     * @param  Collection<int, object>  $purchases
     */
    public function load(Collection $purchases, string $batchId): int
    {
        $loaded = 0;

        $purchases->chunk(250)->each(function (Collection $chunk) use ($batchId, &$loaded): void {
            $records = $chunk->map(function (object $purchase) use ($batchId): array {
                return [
                    'kd_beli' => $purchase->kd_beli,
                    'tgl_beli' => $purchase->tgl_beli,
                    'jam_beli' => $purchase->jam_beli,
                    'jns_beli' => $purchase->jns_beli,
                    'kd_user' => $purchase->kd_user,
                    'kd_sup' => $purchase->kd_sup,
                    'tot_ktr' => $purchase->tot_ktr,
                    'tot_dskn' => $purchase->tot_dskn,
                    'dskn_nota' => $purchase->dskn_nota,
                    'dskn_nota_rp' => $purchase->dskn_nota_rp,
                    'dpp_beli' => $purchase->dpp_beli,
                    'ppn_beli' => $purchase->ppn_beli,
                    'ttl_beli' => $purchase->ttl_beli,
                    'byr_beli' => $purchase->byr_beli,
                    'jth_tmp' => $purchase->jth_tmp,
                    'sts_beli' => $purchase->sts_beli,
                    'kd_shift' => $purchase->kd_shift,
                    'batch_id' => $batchId,
                    'extracted_at' => now(),
                ];
            })->all();

            DB::connection('pgsql_staging')
                ->table('smada.t_beli')
                ->upsert(
                    $records,
                    ['kd_beli'],
                    [
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
                        'batch_id',
                        'extracted_at',
                    ]
                );

            $loaded += count($records);
        });

        return $loaded;
    }
}
