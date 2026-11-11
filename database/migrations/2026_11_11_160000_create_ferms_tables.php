<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('student')->index();
            $table->string('organization')->nullable();
            $table->string('position')->nullable();
        });

        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('location', 160);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('capacity');
            $table->boolean('is_available')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('category', 80);
            $table->text('description')->nullable();
            $table->unsignedInteger('quantity_available');
            $table->boolean('is_available')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('pax')->nullable();
            $table->string('purpose', 160);
            $table->text('description');
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('return_due_at')->nullable();
            $table->timestamps();
            $table->index(['facility_id', 'starts_at', 'ends_at', 'status']);
        });

        Schema::create('reservation_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unique(['reservation_id', 'equipment_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            foreach (['facilities', 'equipment', 'reservations', 'reservation_equipment'] as $table) {
                DB::statement('ALTER TABLE public."'.$table.'" ENABLE ROW LEVEL SECURITY');
                DB::statement('REVOKE ALL ON TABLE public."'.$table.'" FROM anon, authenticated');
                DB::statement('REVOKE ALL ON SEQUENCE public."'.$table.'_id_seq" FROM anon, authenticated');
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_equipment');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('facilities');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'organization', 'position']);
        });
    }
};
