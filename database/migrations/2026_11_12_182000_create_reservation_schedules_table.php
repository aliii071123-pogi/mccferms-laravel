<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();
            $table->index(['starts_at', 'ends_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public."reservation_schedules" ENABLE ROW LEVEL SECURITY');
            DB::statement('REVOKE ALL ON TABLE public."reservation_schedules" FROM anon, authenticated');
            DB::statement('REVOKE ALL ON SEQUENCE public.reservation_schedules_id_seq FROM anon, authenticated');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_schedules');
    }
};
