<?php

namespace App\Services\Etl\Warehouse\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseFactLoader
{
    /**
     * Load purchase fact records into the Data Warehouse.
     *
     * @param Collection<int, array{
     *     id_waktu: int,
     *     id_produk: int,
     *     id_supplier: int,
     *     kd_beli: string,
     *     jumlah_pembelian: int,
     *     nilai_pembelian_detail: int,
     *     alokasi_diskon_nota: int,
     *     nilai_pembelian_bersih: int,
     *     is_anomali: bool
     * }> $purchaseFacts
     */
    public function load(Collection $purchaseFacts): int
    {
        if ($purchaseFacts->isEmpty()) {
            return 0;
        }

        $purchaseCodes = $purchaseFacts
            ->pluck('kd_beli')
            ->map(static fn (string $code): string => trim($code))
            ->unique()
            ->values();

        if ($purchaseCodes->contains('')) {
            throw new InvalidArgumentException(
                'Purchase code must not be empty.'
            );
        }

        $connection = DB::connection('pgsql_dw');

        return $connection->transaction(
            function () use (
                $connection,
                $purchaseCodes,
                $purchaseFacts
            ): int {
                /*
                 * Delete existing fact records for affected
                 * purchase transactions to prevent duplicates.
                 */
                foreach ($purchaseCodes->chunk(500) as $codeChunk) {
                    $connection
                        ->table('smada.fact_pembelian')
                        ->whereIn('kd_beli', $codeChunk->all())
                        ->delete();
                }

                /*
                 * Insert transformed purchase fact records
                 * in batches of 500 rows.
                 */
                $processedRows = 0;

                foreach ($purchaseFacts->chunk(500) as $factChunk) {
                    $rows = $factChunk
                        ->map(
                            static fn (array $fact): array => [
                                'id_waktu' => $fact['id_waktu'],
                                'id_produk' => $fact['id_produk'],
                                'id_supplier' => $fact['id_supplier'],
                                'kd_beli' => $fact['kd_beli'],
                                'jumlah_pembelian' => $fact['jumlah_pembelian'],
                                'nilai_pembelian_detail' => $fact['nilai_pembelian_detail'],
                                'alokasi_diskon_nota' => $fact['alokasi_diskon_nota'],
                                'nilai_pembelian_bersih' => $fact['nilai_pembelian_bersih'],
                                'is_anomali' => $fact['is_anomali'],
                            ]
                        )
                        ->all();

                    $connection
                        ->table('smada.fact_pembelian')
                        ->insert($rows);

                    $processedRows += count($rows);
                }

                return $processedRows;
            }
        );
    }
}
