<?php

namespace App\Services\Etl\Warehouse\Transformers;

use Illuminate\Support\Collection;
use InvalidArgumentException;

class ProductTransformer
{
    /**
     * Transform staging product records into Data Warehouse records.
     *
     * @param  Collection<int, object>  $products
     * @param  Collection<int, object>  $categories
     * @return Collection<int, array{
     *     kode_produk: string,
     *     id_kategori: int,
     *     nama_produk: string,
     *     satuan: string
     * }>
     */
    public function transform(
        Collection $products,
        Collection $categories
    ): Collection {
        $categoryMap = $categories->keyBy('kode_kategori');

        return $products->map(static function (object $product) use ($categoryMap): array {
            $kodeProduk = trim((string) $product->kd_brg);
            $kodeKategori = trim((string) $product->kd_kat);
            $namaProduk = trim((string) $product->nm_brg);
            $satuan = trim((string) $product->sat_kecil);

            if (
                $kodeProduk === ''
                || $kodeKategori === ''
                || $namaProduk === ''
                || $satuan === ''
            ) {
                throw new InvalidArgumentException(
                    'Product code, category code, name, and unit must not be empty.'
                );
            }

            $category = $categoryMap->get($kodeKategori);

            if ($category === null) {
                throw new InvalidArgumentException(
                    "Category code {$kodeKategori} was not found in the Data Warehouse."
                );
            }

            if (mb_strlen($kodeProduk) > 50) {
                throw new InvalidArgumentException(
                    'Product code exceeds the maximum length of 50 characters.'
                );
            }

            if (mb_strlen($namaProduk) > 255) {
                throw new InvalidArgumentException(
                    'Product name exceeds the maximum length of 255 characters.'
                );
            }

            if (mb_strlen($satuan) > 50) {
                throw new InvalidArgumentException(
                    'Product unit exceeds the maximum length of 50 characters.'
                );
            }

            return [
                'kode_produk' => $kodeProduk,
                'id_kategori' => (int) $category->id_kategori,
                'nama_produk' => $namaProduk,
                'satuan' => $satuan,
            ];
        });
    }
}
