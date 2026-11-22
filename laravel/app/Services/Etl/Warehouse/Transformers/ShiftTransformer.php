<?php

namespace App\Services\Etl\Warehouse\Transformers;

use DateTimeImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ShiftTransformer
{
    /**
     * Transform staging shift records into Data Warehouse records.
     *
     * @param  Collection<int, object>  $shifts
     * @return Collection<int, array{
     *     kd_shift: string,
     *     tgl_buka: string,
     *     jam_buka: string,
     *     tgl_tutup: string,
     *     jam_tutup: string
     * }>
     */
    public function transform(Collection $shifts): Collection
    {
        return $shifts->map(static function (object $shift): array {
            $kodeShift = trim((string) $shift->kd_shift);
            $tanggalBuka = substr((string) $shift->tgl_buka, 0, 10);
            $jamBuka = substr(trim((string) $shift->jam_buka), 0, 8);
            $tanggalTutup = substr((string) $shift->tgl_tutup, 0, 10);
            $jamTutup = substr(trim((string) $shift->jam_tutup), 0, 8);

            if (
                $kodeShift === ''
                || ! self::isValidDate($tanggalBuka)
                || ! self::isValidTime($jamBuka)
                || ! self::isValidDate($tanggalTutup)
                || ! self::isValidTime($jamTutup)
            ) {
                throw new InvalidArgumentException(
                    'Shift code, opening time, or closing time is invalid.'
                );
            }

            if (mb_strlen($kodeShift) > 50) {
                throw new InvalidArgumentException(
                    'Shift code exceeds the maximum length of 50 characters.'
                );
            }

            return [
                'kd_shift' => $kodeShift,
                'tgl_buka' => $tanggalBuka,
                'jam_buka' => $jamBuka,
                'tgl_tutup' => $tanggalTutup,
                'jam_tutup' => $jamTutup,
            ];
        });
    }

    private static function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private static function isValidTime(string $time): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!H:i:s', $time);

        return $parsed !== false && $parsed->format('H:i:s') === $time;
    }
}
