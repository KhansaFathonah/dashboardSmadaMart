<?php

namespace App\Services\Etl\Warehouse\Transformers;

use DateTimeImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class WaktuTransformer
{
    /**
     * Transform a date range into time dimension records.
     *
     * @return Collection<int, array{
     *     tanggal: string,
     *     nama_hari: string,
     *     bulan: int,
     *     tahun: int
     * }>
     */
    public function transform(
        string $startDate,
        string $endDate
    ): Collection {
        $start = $this->parseDate($startDate);
        $end = $this->parseDate($endDate);

        if ($start > $end) {
            throw new InvalidArgumentException(
                'Start date must not be later than end date.'
            );
        }

        $namaHari = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $records = [];

        for (
            $date = $start;
            $date <= $end;
            $date = $date->modify('+1 day')
        ) {
            $records[] = [
                'tanggal' => $date->format('Y-m-d'),
                'nama_hari' => $namaHari[(int) $date->format('N')],
                'bulan' => (int) $date->format('n'),
                'tahun' => (int) $date->format('Y'),
            ];
        }

        return new Collection($records);
    }

    /**
     * Parse and validate a date using Y-m-d format.
     */
    private function parseDate(string $date): DateTimeImmutable
    {
        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = DateTimeImmutable::getLastErrors();

        if (
            $parsedDate === false
            || ($errors !== false
                && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $parsedDate->format('Y-m-d') !== $date
        ) {
            throw new InvalidArgumentException(
                "Invalid date format: {$date}. Expected Y-m-d."
            );
        }

        return $parsedDate;
    }
}
