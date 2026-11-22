<?php

namespace App\Services\Etl\Loaders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class SalesDetailLoader
{
    /**
     * Load sales detail data into the PostgreSQL staging database.
     *
     * @param  LazyCollection<int, object>  $details
     */
    public function load(LazyCollection $details, string $batchId): int
    {
        $connection = DB::connection('pgsql_staging');
        $extractedAt = now()->toDateTimeString();

        return $connection->transaction(
            function () use ($connection, $details, $batchId, $extractedAt): int {
                $processedRows = 0;

                foreach ($details->chunk(250) as $detailChunk) {
                    $rows = $detailChunk->map(
                        static fn (object $detail): array => [
                            'kd_jual' => $detail->kd_jual,
                            'kd_brg' => $detail->kd_brg,
                            'stok_jual' => $detail->stok_jual,
                            'jml_jual' => $detail->jml_jual,
                            'hpp_jual' => $detail->hpp_jual,
                            'hrg_jual' => $detail->hrg_jual,
                            'dskn_jual_1' => $detail->dskn_jual_1,
                            'dskn_jual_2' => $detail->dskn_jual_2,
                            'dskn_jual_3' => $detail->dskn_jual_3,
                            'tot_diskon' => $detail->tot_diskon,
                            'ppn_detail' => $detail->ppn_detail,
                            'total_detail' => $detail->total_detail,
                            'batch_id' => $batchId,
                            'extracted_at' => $extractedAt,
                        ]
                    )->all();

                    $connection->table('smada.t_jual_detail')
                        ->insert($rows);

                    $processedRows += count($rows);
                }

                return $processedRows;
            }
        );
    }
}
