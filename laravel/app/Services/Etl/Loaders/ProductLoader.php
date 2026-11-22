<?php

namespace App\Services\Etl\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductLoader
{
    /**
     * Load product data into the PostgreSQL staging database.
     *
     * @param  Collection<int, object>  $products
     */
    public function load(Collection $products, string $batchId): int
    {
        if ($products->isEmpty()) {
            return 0;
        }

        $extractedAt = now()->toDateTimeString();
        $processedRows = 0;

        foreach ($products->chunk(250) as $productChunk) {
            $rows = $productChunk->map(
                static fn (object $product): array => [
                    'kd_brg' => $product->kd_brg,
                    'kd_kat' => $product->kd_kat,
                    'kd_sup' => $product->kd_sup,
                    'nm_brg' => $product->nm_brg,
                    'bar_brg' => $product->bar_brg,
                    'sat_kecil' => $product->sat_kecil,
                    'jml_kecil' => $product->jml_kecil,
                    'sat_besar' => $product->sat_besar,
                    'jml_besar' => $product->jml_besar,
                    'min_brg' => $product->min_brg,
                    'max_brg' => $product->max_brg,
                    'hrg_beli' => $product->hrg_beli,
                    'hrg_pkok' => $product->hrg_pkok,
                    'hrg_jual_a' => $product->hrg_jual_a,
                    'hrg_jual_b' => $product->hrg_jual_b,
                    'min_jual_b' => $product->min_jual_b,
                    'hrg_jual_c' => $product->hrg_jual_c,
                    'min_jual_c' => $product->min_jual_c,
                    'hrg_grsr' => $product->hrg_grsr,
                    'dskn_grosir' => $product->dskn_grosir,
                    'dskn_brg_1' => $product->dskn_brg_1,
                    'dskn_brg_2' => $product->dskn_brg_2,
                    'dskn_brg_3' => $product->dskn_brg_3,
                    'dskn_brg_rp' => $product->dskn_brg_rp,
                    'bkp' => $product->bkp,
                    'stok_brg' => $product->stok_brg,
                    'stok_msk' => $product->stok_msk,
                    'stok_psn' => $product->stok_psn,
                    'stok_jual' => $product->stok_jual,
                    'stok_adj' => $product->stok_adj,
                    'stok_rtr_beli' => $product->stok_rtr_beli,
                    'stok_rtr_jual' => $product->stok_rtr_jual,
                    'sts_brg' => $product->sts_brg,
                    'batch_id' => $batchId,
                    'extracted_at' => $extractedAt,
                ]
            )->all();

            $processedRows += DB::connection('pgsql_staging')
                ->table('smada.t_brg')
                ->upsert(
                    $rows,
                    ['kd_brg'],
                    [
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
                        'batch_id',
                        'extracted_at',
                    ]
                );
        }

        return $processedRows;
    }
}
