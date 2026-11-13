<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('feedback');
    }

    public function down(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_anonymous')->default(false);
            $table->unsignedTinyInteger('rating');
            $table->text('message');
            $table->timestamps();
            $table->index(['is_anonymous', 'created_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public."feedback" ENABLE ROW LEVEL SECURITY');
            DB::statement('REVOKE ALL ON TABLE public."feedback" FROM anon, authenticated');
            DB::statement('REVOKE ALL ON SEQUENCE public.feedback_id_seq FROM anon, authenticated');
        }
    }
};
