<?php

namespace App\Services\Etl\Warehouse\Transformers;

use Illuminate\Support\Collection;
use InvalidArgumentException;

class CategoryTransformer
{
    /**
     * Transform staging category records into Data Warehouse records.
     *
     * @param  Collection<int, object>  $categories
     * @return Collection<int, array{
     *     kode_kategori: string,
     *     nama_kategori: string
     * }>
     */
    public function transform(Collection $categories): Collection
    {
        return $categories->map(
            static function (object $category): array {
                $kodeKategori = trim((string) $category->kd_kat);
                $namaKategori = trim((string) $category->nm_kat);

                if ($kodeKategori === '' || $namaKategori === '') {
                    throw new InvalidArgumentException(
                        'Category code and name must not be empty.'
                    );
                }

                if (mb_strlen($kodeKategori) > 50) {
                    throw new InvalidArgumentException(
                        "Category code exceeds 50 characters: {$kodeKategori}"
                    );
                }

                if (mb_strlen($namaKategori) > 100) {
                    throw new InvalidArgumentException(
                        "Category name exceeds 100 characters: {$namaKategori}"
                    );
                }

                return [
                    'kode_kategori' => $kodeKategori,
                    'nama_kategori' => $namaKategori,
                ];
            }
        );
    }
}
