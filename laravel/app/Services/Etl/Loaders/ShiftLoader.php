<?php

namespace App\Services\Etl\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShiftLoader
{
    /**
     * Load shift data into the PostgreSQL staging database.
     *
     * @param  Collection<int, object>  $shifts
     */
    public function load(Collection $shifts, string $batchId): int
    {
        if ($shifts->isEmpty()) {
            return 0;
        }

        $extractedAt = now()->toDateTimeString();

        $rows = $shifts->map(
            static fn (object $shift): array => [
                'kd_shift' => $shift->kd_shift,
                'kd_user' => $shift->kd_user,
                'tgl_buka' => $shift->tgl_buka,
                'jam_buka' => $shift->jam_buka,
                'modal_buka' => $shift->modal_buka,
                'jual_struk' => $shift->jual_struk,
                'jual_qty' => $shift->jual_qty,
                'jual_diskon' => $shift->jual_diskon,
                'jual_rp' => $shift->jual_rp,
                'rtr_jual_struk' => $shift->rtr_jual_struk,
                'sts_jual_qty' => $shift->sts_jual_qty,
                'rtr_jual_diskon' => $shift->rtr_jual_diskon,
                'rtr_jual_rp' => $shift->rtr_jual_rp,
                'beli_struk' => $shift->beli_struk,
                'beli_qty' => $shift->beli_qty,
                'beli_diskon' => $shift->beli_diskon,
                'beli_rp' => $shift->beli_rp,
                'rtr_beli_struk' => $shift->rtr_beli_struk,
                'rtr_beli_qty' => $shift->rtr_beli_qty,
                'rtr_beli_diskon' => $shift->rtr_beli_diskon,
                'rtr_beli_rp' => $shift->rtr_beli_rp,
                'hutang' => $shift->hutang,
                'bayar_hutang' => $shift->bayar_hutang,
                'piutang' => $shift->piutang,
                'bayar_piutang' => $shift->bayar_piutang,
                'masuk' => $shift->masuk,
                'keluar' => $shift->keluar,
                'total_komputer' => $shift->total_komputer,
                'input_kasir' => $shift->input_kasir,
                'selisih' => $shift->selisih,
                'tgl_tutup' => $shift->tgl_tutup,
                'jam_tutup' => $shift->jam_tutup,
                'sts_shift' => $shift->sts_shift,
                'batch_id' => $batchId,
                'extracted_at' => $extractedAt,
            ]
        )->all();

        return DB::connection('pgsql_staging')
            ->table('smada.t_shift')
            ->upsert(
                $rows,
                ['kd_shift'],
                [
                    'kd_user',
                    'tgl_buka',
                    'jam_buka',
                    'modal_buka',
                    'jual_struk',
                    'jual_qty',
                    'jual_diskon',
                    'jual_rp',
                    'rtr_jual_struk',
                    'sts_jual_qty',
                    'rtr_jual_diskon',
                    'rtr_jual_rp',
                    'beli_struk',
                    'beli_qty',
                    'beli_diskon',
                    'beli_rp',
                    'rtr_beli_struk',
                    'rtr_beli_qty',
                    'rtr_beli_diskon',
                    'rtr_beli_rp',
                    'hutang',
                    'bayar_hutang',
                    'piutang',
                    'bayar_piutang',
                    'masuk',
                    'keluar',
                    'total_komputer',
                    'input_kasir',
                    'selisih',
                    'tgl_tutup',
                    'jam_tutup',
                    'sts_shift',
                    'batch_id',
                    'extracted_at',
                ]
            );
    }
}
