<?php

namespace App\Services\Etl\Warehouse\Transformers;

use Illuminate\Support\Collection;
use InvalidArgumentException;

class SupplierTransformer
{
    /**
     * Transform staging supplier records into Data Warehouse records.
     *
     * @param  Collection<int, object>  $suppliers
     * @return Collection<int, array{
     *     kode_supplier: string,
     *     nama_supplier: string,
     *     alamat_supplier: ?string
     * }>
     */
    public function transform(Collection $suppliers): Collection
    {
        return $suppliers->map(static function (object $supplier): array {
            $kodeSupplier = trim((string) $supplier->kd_sup);
            $namaSupplier = trim((string) $supplier->nm_sup);

            if ($kodeSupplier === '' || $namaSupplier === '') {
                throw new InvalidArgumentException(
                    'Supplier code and name must not be empty.'
                );
            }

            if (mb_strlen($kodeSupplier) > 50) {
                throw new InvalidArgumentException(
                    'Supplier code exceeds the maximum length of 50 characters.'
                );
            }

            if (mb_strlen($namaSupplier) > 255) {
                throw new InvalidArgumentException(
                    'Supplier name exceeds the maximum length of 255 characters.'
                );
            }

            return [
                'kode_supplier' => $kodeSupplier,
                'nama_supplier' => $namaSupplier,
                'alamat_supplier' => $supplier->almt_sup !== null
                    ? trim((string) $supplier->almt_sup)
                    : null,
            ];
        });
    }
}
