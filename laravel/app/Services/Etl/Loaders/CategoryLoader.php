<?php

namespace App\Services\Etl\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CategoryLoader
{
    /**
     * Load category data into the PostgreSQL staging database.
     *
     * @param  Collection<int, object>  $categories
     */
    public function load(Collection $categories, string $batchId): int
    {
        if ($categories->isEmpty()) {
            return 0;
        }

        $extractedAt = now()->toDateTimeString();

        $rows = $categories->map(
            static fn (object $category): array => [
                'kd_kat' => $category->kd_kat,
                'nm_kat' => $category->nm_kat,
                'sts_kat' => $category->sts_kat,
                'batch_id' => $batchId,
                'extracted_at' => $extractedAt,
            ]
        )->all();

        return DB::connection('pgsql_staging')
            ->table('smada.t_kat')
            ->upsert(
                $rows,
                ['kd_kat'],
                [
                    'nm_kat',
                    'sts_kat',
                    'batch_id',
                    'extracted_at',
                ]
            );
    }
}
