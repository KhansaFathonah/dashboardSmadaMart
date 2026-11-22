<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('etl_anomaly_log', function (Blueprint $table) {
            $table->id();

            $table->string('etl_batch_id', 50);
            $table->string('source_table', 100);
            $table->string('record_key', 100);
            $table->string('anomaly_type', 100);

            $table->string('source_header_batch_id', 50)
                ->nullable();

            $table->string('source_detail_batch_id', 50)
                ->nullable();

            $table->decimal('header_value', 18, 2)
                ->nullable();

            $table->decimal('detail_value', 18, 2)
                ->nullable();

            $table->decimal('difference_value', 18, 2)
                ->nullable();

            $table->text('description');

            $table->timestamp('detected_at');

            $table->timestamps();

            $table->unique(
                [
                    'etl_batch_id',
                    'source_table',
                    'record_key',
                    'anomaly_type',
                ],
                'etl_anomaly_log_unique'
            );

            $table->index('anomaly_type');
            $table->index('detected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etl_anomaly_log');
    }
};