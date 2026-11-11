<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_auth_codes', function (Blueprint $table) {
            $table->string('code_hash', 64)->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            foreach (['personal_access_tokens', 'mobile_auth_codes'] as $table) {
                DB::statement('ALTER TABLE public."'.$table.'" ENABLE ROW LEVEL SECURITY');
                DB::statement('REVOKE ALL ON TABLE public."'.$table.'" FROM anon, authenticated');
            }
            DB::statement('REVOKE ALL ON SEQUENCE public.personal_access_tokens_id_seq FROM anon, authenticated');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_auth_codes');
    }
};
