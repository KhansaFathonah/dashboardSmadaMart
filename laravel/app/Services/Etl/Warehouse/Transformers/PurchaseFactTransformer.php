<?php

namespace App\Services\Etl\Warehouse\Transformers;

use Illuminate\Support\Collection;
use InvalidArgumentException;

class PurchaseFactTransformer
{
    /**
     * Transform staging purchase data into Data Warehouse fact records.
     *
     * @param  Collection<int, object>  $headers
     * @param  Collection<int, object>  $details
     * @param  Collection<int, object>  $times
     * @param  Collection<int, object>  $products
     * @param  Collection<int, object>  $suppliers
     * @return Collection<int, array{
     *     id_waktu: int,
     *     id_produk: int,
     *     id_supplier: int,
     *     kd_beli: string,
     *     jumlah_pembelian: int,
     *     nilai_pembelian_detail: int,
     *     alokasi_diskon_nota: int,
     *     nilai_pembelian_bersih: int,
     *     is_anomali: bool
     * }>
     */
    public function transform(
        Collection $headers,
        Collection $details,
        Collection $times,
        Collection $products,
        Collection $suppliers
    ): Collection {
        $result = $this->transformWithAnomalies(
            $headers,
            $details,
            $times,
            $products,
            $suppliers
        );

        return $result['fact_rows'];
    }

    /**
     * Transform purchase data and collect detected anomalies.
     *
     * @param  Collection<int, object>  $headers
     * @param  Collection<int, object>  $details
     * @param  Collection<int, object>  $times
     * @param  Collection<int, object>  $products
     * @param  Collection<int, object>  $suppliers
     * @return array{
     *     fact_rows: Collection<int, array{
     *         id_waktu: int,
     *         id_produk: int,
     *         id_supplier: int,
     *         kd_beli: string,
     *         jumlah_pembelian: int,
     *         nilai_pembelian_detail: int,
     *         alokasi_diskon_nota: int,
     *         nilai_pembelian_bersih: int,
     *         is_anomali: bool
     *     }>,
     *     anomalies: Collection<int, array{
     *         source_table: string,
     *         record_key: string,
     *         anomaly_type: string,
     *         source_header_batch_id: ?string,
     *         source_detail_batch_id: ?string,
     *         header_value: int,
     *         detail_value: int,
     *         difference_value: int,
     *         description: string
     *     }>
     * }
     */
    public function transformWithAnomalies(
        Collection $headers,
        Collection $details,
        Collection $times,
        Collection $products,
        Collection $suppliers
    ): array {
        $headerMap = $this->buildUniqueMap(
            $headers,
            'kd_beli',
            'purchase header'
        );

        $timeMap = $this->buildUniqueMap(
            $times,
            'tanggal',
            'time dimension'
        );

        $productMap = $this->buildUniqueMap(
            $products,
            'kode_produk',
            'product dimension'
        );

        $supplierMap = $this->buildUniqueMap(
            $suppliers,
            'kode_supplier',
            'supplier dimension'
        );

        $detailsByPurchase = $details->groupBy(
            static fn (object $detail): string => trim(
                (string) $detail->kd_beli
            )
        );

        foreach ($detailsByPurchase->keys() as $purchaseCode) {
            if (
                $purchaseCode === ''
                || ! $headerMap->has($purchaseCode)
            ) {
                throw new InvalidArgumentException(
                    "Purchase header {$purchaseCode} was not found."
                );
            }
        }

        $factRows = collect();
        $anomalies = collect();

        foreach ($headerMap as $purchaseCode => $header) {
            $purchaseDetails = $detailsByPurchase->get($purchaseCode);

            if ($purchaseDetails === null || $purchaseDetails->isEmpty()) {
                throw new InvalidArgumentException(
                    "Purchase {$purchaseCode} has no detail records."
                );
            }

            $transactionDate = $this->normalizeDate(
                $header->tgl_beli
            );

            $supplierCode = trim((string) $header->kd_sup);

            $time = $timeMap->get($transactionDate);
            $supplier = $supplierMap->get($supplierCode);

            if ($time === null) {
                throw new InvalidArgumentException(
                    "Date {$transactionDate} was not found in dim_waktu."
                );
            }

            if ($supplier === null) {
                throw new InvalidArgumentException(
                    "Supplier {$supplierCode} was not found in dim_supplier."
                );
            }

            $transactionDetails = $purchaseDetails->values();

            $detailTotal = 0;

            foreach ($transactionDetails as $detail) {
                $detailTotal += $this->toInteger(
                    $detail->total_detail,
                    'total_detail'
                );
            }

            $grossTotal = $this->toInteger(
                $header->tot_ktr,
                'tot_ktr'
            );

            $invoiceDiscount = $this->toInteger(
                $header->dskn_nota_rp,
                'dskn_nota_rp'
            );

            $netTotal = $this->toInteger(
                $header->ttl_beli,
                'ttl_beli'
            );

            $discountToAllocate = 0;
            $isAnomaly = false;

            /*
             * Transaction classification:
             *
             * 1. Detail total equals header net total:
             *    Discount is already reflected in detail rows.
             *
             * 2. Detail total equals header gross total:
             *    Invoice discount must be allocated to detail rows.
             *
             * 3. Other reconciliation results:
             *    Preserve source values and mark as anomaly.
             */
            if ($detailTotal === $netTotal) {
                $discountToAllocate = 0;
            } elseif (
                $detailTotal === $grossTotal
                && $grossTotal - $netTotal === $invoiceDiscount
                && $invoiceDiscount > 0
                && $invoiceDiscount <= $detailTotal
            ) {
                $discountToAllocate = $invoiceDiscount;
            } else {
                $isAnomaly = true;

                $anomalies->push([
                    'source_table' => 't_beli',
                    'record_key' => $purchaseCode,
                    'anomaly_type' => 'PURCHASE_HEADER_DETAIL_MISMATCH',
                    'source_header_batch_id' => $this->getBatchId(
                        $header
                    ),
                    'source_detail_batch_id' => $this->getDetailBatchId(
                        $transactionDetails
                    ),
                    'header_value' => $netTotal,
                    'detail_value' => $detailTotal,
                    'difference_value' => $detailTotal - $netTotal,
                    'description' => sprintf(
                        'Purchase transaction %s has a mismatch between '
                        .'header net value (%s) and detail total (%s). '
                        .'Difference (detail - header): %s.',
                        $purchaseCode,
                        $netTotal,
                        $detailTotal,
                        $detailTotal - $netTotal
                    ),
                ]);
            }

            $allocations = $discountToAllocate > 0
                ? $this->allocateInvoiceDiscount(
                    $transactionDetails,
                    $discountToAllocate,
                    $detailTotal
                )
                : [];

            foreach ($transactionDetails as $detail) {
                $productCode = trim((string) $detail->kd_brg);

                $product = $productMap->get($productCode);

                if ($product === null) {
                    throw new InvalidArgumentException(
                        "Product {$productCode} was not found in dim_produk."
                    );
                }

                $quantity = $this->toInteger(
                    $detail->jml_beli,
                    'jml_beli'
                );

                if ($quantity <= 0) {
                    throw new InvalidArgumentException(
                        "Invalid purchase quantity for {$purchaseCode}."
                    );
                }

                $detailValue = $this->toInteger(
                    $detail->total_detail,
                    'total_detail'
                );

                $stagingId = (int) $detail->id_stg;

                $allocatedDiscount = $allocations[$stagingId] ?? 0;

                $netDetailValue = $detailValue - $allocatedDiscount;

                if (
                    $detailValue < 0
                    || $allocatedDiscount < 0
                    || $netDetailValue < 0
                ) {
                    throw new InvalidArgumentException(
                        "Invalid monetary value for purchase {$purchaseCode}."
                    );
                }

                $factRows->push([
                    'id_waktu' => (int) $time->id_waktu,
                    'id_produk' => (int) $product->id_produk,
                    'id_supplier' => (int) $supplier->id_supplier,
                    'kd_beli' => $purchaseCode,
                    'jumlah_pembelian' => $quantity,
                    'nilai_pembelian_detail' => $detailValue,
                    'alokasi_diskon_nota' => $allocatedDiscount,
                    'nilai_pembelian_bersih' => $netDetailValue,
                    'is_anomali' => $isAnomaly,
                ]);
            }
        }

        return [
            'fact_rows' => $factRows,
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Build a lookup map and reject duplicate keys.
     *
     * @param  Collection<int, object>  $records
     */
    private function buildUniqueMap(
        Collection $records,
        string $key,
        string $description
    ): Collection {
        $map = collect();

        foreach ($records as $record) {
            $value = trim((string) $record->{$key});

            if ($value === '') {
                throw new InvalidArgumentException(
                    "Empty {$key} found in {$description}."
                );
            }

            if ($map->has($value)) {
                throw new InvalidArgumentException(
                    "Duplicate {$key} {$value} found in {$description}."
                );
            }

            $map->put($value, $record);
        }

        return $map;
    }

    /**
     * Get the source header batch ID.
     */
    private function getBatchId(object $record): ?string
    {
        $batchId = trim((string) ($record->batch_id ?? ''));

        return $batchId !== '' ? $batchId : null;
    }

    /**
     * Get the unique source detail batch ID for a transaction.
     *
     * @param  Collection<int, object>  $details
     */
    private function getDetailBatchId(Collection $details): ?string
    {
        $batchIds = $details
            ->map(
                static fn (object $detail): string => trim(
                    (string) ($detail->batch_id ?? '')
                )
            )
            ->filter()
            ->unique()
            ->values();

        if ($batchIds->count() > 1) {
            throw new InvalidArgumentException(
                'A purchase transaction contains details from multiple batches.'
            );
        }

        return $batchIds->first();
    }

    /**
     * Allocate invoice discount using the Largest Remainder Method.
     *
     * The allocation is performed in integer rupiah.
     *
     * @param  Collection<int, object>  $details
     * @return array<int, int>
     */
    private function allocateInvoiceDiscount(
        Collection $details,
        int $discount,
        int $detailTotal
    ): array {
        if (
            $detailTotal <= 0
            || $discount < 0
            || $discount > $detailTotal
        ) {
            throw new InvalidArgumentException(
                'Invalid invoice discount allocation values.'
            );
        }

        $allocations = [];
        $remainders = [];
        $allocatedTotal = 0;

        foreach ($details as $detail) {
            $stagingId = (int) $detail->id_stg;

            if ($stagingId <= 0 || isset($allocations[$stagingId])) {
                throw new InvalidArgumentException(
                    'Missing or duplicate staging detail ID.'
                );
            }

            $lineValue = $this->toInteger(
                $detail->total_detail,
                'total_detail'
            );

            if ($lineValue < 0) {
                throw new InvalidArgumentException(
                    'Negative detail value cannot be allocated.'
                );
            }

            if (
                $discount > 0
                && $lineValue > intdiv(PHP_INT_MAX, $discount)
            ) {
                throw new InvalidArgumentException(
                    'Invoice discount allocation multiplication overflow.'
                );
            }

            $numerator = $lineValue * $discount;

            $baseAllocation = intdiv(
                $numerator,
                $detailTotal
            );

            $remainder = $numerator % $detailTotal;

            $allocations[$stagingId] = $baseAllocation;

            $allocatedTotal += $baseAllocation;

            $remainders[] = [
                'id_stg' => $stagingId,
                'remainder' => $remainder,
            ];
        }

        $remainingAmount = $discount - $allocatedTotal;

        usort(
            $remainders,
            static function (array $first, array $second): int {
                $remainderComparison =
                    $second['remainder'] <=> $first['remainder'];

                if ($remainderComparison !== 0) {
                    return $remainderComparison;
                }

                return $first['id_stg'] <=> $second['id_stg'];
            }
        );

        for ($index = 0; $index < $remainingAmount; $index++) {
            if (! isset($remainders[$index])) {
                throw new InvalidArgumentException(
                    'Unable to complete invoice discount allocation.'
                );
            }

            $stagingId = $remainders[$index]['id_stg'];

            $allocations[$stagingId]++;
        }

        if (array_sum($allocations) !== $discount) {
            throw new InvalidArgumentException(
                'Invoice discount allocation does not reconcile.'
            );
        }

        return $allocations;
    }

    /**
     * Normalize and validate a date into YYYY-MM-DD format.
     */
    private function normalizeDate(mixed $date): string
    {
        $value = trim((string) $date);

        if ($value === '') {
            throw new InvalidArgumentException(
                'Purchase transaction date must not be empty.'
            );
        }

        $normalizedDate = substr($value, 0, 10);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalizedDate)) {
            throw new InvalidArgumentException(
                "Invalid purchase transaction date: {$value}."
            );
        }

        [$year, $month, $day] = array_map(
            'intval',
            explode('-', $normalizedDate)
        );

        if (! checkdate($month, $day, $year)) {
            throw new InvalidArgumentException(
                "Invalid purchase transaction date: {$value}."
            );
        }

        return $normalizedDate;
    }

    /**
     * Convert an integer database value into an integer rupiah value.
     *
     * Floating-point values are rejected to avoid precision loss.
     */
    private function toInteger(
        mixed $value,
        string $field
    ): int {
        if (is_int($value)) {
            if ($value < 0) {
                throw new InvalidArgumentException(
                    "Field {$field} must contain a non-negative integer."
                );
            }

            return $value;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException(
                "Field {$field} must be an integer or numeric string."
            );
        }

        $number = trim($value);

        if (! preg_match('/^\+?\d+(?:\.0+)?$/', $number)) {
            throw new InvalidArgumentException(
                "Field {$field} must contain a non-negative integer."
            );
        }

        $number = ltrim($number, '+');

        if (str_contains($number, '.')) {
            $number = explode('.', $number)[0];
        }

        $number = ltrim($number, '0');

        if ($number === '') {
            return 0;
        }

        $maximum = (string) PHP_INT_MAX;

        if (
            strlen($number) > strlen($maximum)
            || (
                strlen($number) === strlen($maximum)
                && strcmp($number, $maximum) > 0
            )
        ) {
            throw new InvalidArgumentException(
                "Field {$field} exceeds the supported integer range."
            );
        }

        return (int) $number;
    }
}
