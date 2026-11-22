<?php

namespace App\Services\Etl;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AnomalyLogger
{
    /**
     * Store detected ETL anomalies in the application database.
     *
     * @param  Collection<int, array{
     *     source_table: string,
     *     record_key: string,
     *     anomaly_type: string,
     *     source_header_batch_id: ?string,
     *     source_detail_batch_id: ?string,
     *     header_value: int,
     *     detail_value: int,
     *     difference_value: int,
     *     description: string
     * }>  $anomalies
     */
    public function log(
        Collection $anomalies,
        string $etlBatchId
    ): int {
        $etlBatchId = trim($etlBatchId);

        if ($etlBatchId === '') {
            throw new InvalidArgumentException(
                'ETL batch ID must not be empty.'
            );
        }

        if ($anomalies->isEmpty()) {
            return 0;
        }

        $now = now()->toDateTimeString();

        $rows = $anomalies->map(
            static function (array $anomaly) use (
                $etlBatchId,
                $now
            ): array {
                return [
                    'etl_batch_id' => $etlBatchId,
                    'source_table' => $anomaly['source_table'],
                    'record_key' => $anomaly['record_key'],
                    'anomaly_type' => $anomaly['anomaly_type'],
                    'source_header_batch_id' => $anomaly['source_header_batch_id'],
                    'source_detail_batch_id' => $anomaly['source_detail_batch_id'],
                    'header_value' => $anomaly['header_value'],
                    'detail_value' => $anomaly['detail_value'],
                    'difference_value' => $anomaly['difference_value'],
                    'description' => $anomaly['description'],
                    'detected_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        )->all();

        DB::connection('mysql_app')
            ->table('etl_anomaly_log')
            ->upsert(
                $rows,
                [
                    'etl_batch_id',
                    'source_table',
                    'record_key',
                    'anomaly_type',
                ],
                [
                    'source_header_batch_id',
                    'source_detail_batch_id',
                    'header_value',
                    'detail_value',
                    'difference_value',
                    'description',
                    'detected_at',
                    'updated_at',
                ]
            );

        return count($rows);
    }
}
