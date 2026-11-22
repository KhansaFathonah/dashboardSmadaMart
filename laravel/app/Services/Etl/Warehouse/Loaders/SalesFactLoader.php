<?php

namespace App\Services\Etl\Warehouse\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesFactLoader
{
    /**
     * Load sales fact records into the Data Warehouse.
     *
     * Existing records for the affected sales transactions
     * are replaced within a single database transaction.
     *
     * @param Collection<int, array{
     *     id_waktu: int,
     *     id_produk: int,
     *     id_shift: int,
     *     kd_jual: string,
     *     jumlah_terjual: int,
     *     nilai_penjualan_detail: int,
     *     nilai_hpp: int,
     *     alokasi_diskon_nota: int,
     *     nilai_penjualan_bersih: int,
     *     is_anomali: bool
     * }> $factRows
     */
    public function load(Collection $factRows): int
    {
        if ($factRows->isEmpty()) {
            return 0;
        }

        $salesCodes = $factRows
            ->pluck('kd_jual')
            ->map(static fn (string $code): string => trim($code))
            ->unique()
            ->values();

        if ($salesCodes->contains('')) {
            throw new InvalidArgumentException(
                'Sales transaction code must not be empty.'
            );
        }

        $connection = DB::connection('pgsql_dw');

        return $connection->transaction(
            function () use (
                $connection,
                $factRows,
                $salesCodes
            ): int {
                // Delete existing fact records for affected transactions.
                foreach ($salesCodes->chunk(500) as $salesCodeChunk) {
                    $connection
                        ->table('smada.fact_penjualan')
                        ->whereIn('kd_jual', $salesCodeChunk->all())
                        ->delete();
                }

                $processedRows = 0;

                // Insert transformed fact records in chunks.
                foreach ($factRows->chunk(500) as $factChunk) {
                    $rows = $factChunk->map(
                        static fn (array $fact): array => [
                            'id_waktu' => $fact['id_waktu'],
                            'id_produk' => $fact['id_produk'],
                            'id_shift' => $fact['id_shift'],
                            'kd_jual' => $fact['kd_jual'],
                            'jumlah_terjual' => $fact['jumlah_terjual'],
                            'nilai_penjualan_detail' => $fact['nilai_penjualan_detail'],
                            'nilai_hpp' => $fact['nilai_hpp'],
                            'alokasi_diskon_nota' => $fact['alokasi_diskon_nota'],
                            'nilai_penjualan_bersih' => $fact['nilai_penjualan_bersih'],
                            'is_anomali' => $fact['is_anomali'],
                        ]
                    )->all();

                    $connection
                        ->table('smada.fact_penjualan')
                        ->insert($rows);

                    $processedRows += count($rows);
                }

                return $processedRows;
            }
        );
    }
}
