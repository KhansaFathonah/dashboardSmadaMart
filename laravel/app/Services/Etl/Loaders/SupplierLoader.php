<?php

namespace App\Services\Etl\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplierLoader
{
    /**
     * Load supplier data into the PostgreSQL staging database.
     *
     * @param  Collection<int, object>  $suppliers
     */
    public function load(Collection $suppliers, string $batchId): int
    {
        if ($suppliers->isEmpty()) {
            return 0;
        }

        $extractedAt = now()->toDateTimeString();

        $rows = $suppliers->map(
            static fn (object $supplier): array => [
                'kd_sup' => $supplier->kd_sup,
                'nm_sup' => $supplier->nm_sup,
                'almt_sup' => $supplier->almt_sup,
                'tlpn_sup' => $supplier->tlpn_sup,
                'fax_sup' => $supplier->fax_sup,
                'sts_sup' => $supplier->sts_sup,
                'batch_id' => $batchId,
                'extracted_at' => $extractedAt,
            ]
        )->all();

        return DB::connection('pgsql_staging')
            ->table('smada.t_sup')
            ->upsert(
                $rows,
                ['kd_sup'],
                [
                    'nm_sup',
                    'almt_sup',
                    'tlpn_sup',
                    'fax_sup',
                    'sts_sup',
                    'batch_id',
                    'extracted_at',
                ]
            );
    }
}
