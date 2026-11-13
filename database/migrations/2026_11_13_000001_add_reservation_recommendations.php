<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('recommended_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('recommended_at')->nullable()->after('recommended_by');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recommended_by');
            $table->dropColumn('recommended_at');
        });
    }
};
