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
            $table->boolean('is_suspended')->default(false);
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->string('staff_in_charge', 160)->nullable();
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->string('staff_in_charge', 160)->nullable();
            $table->string('category', 80)->nullable()->change();
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160)->unique();
            $table->timestamps();
        });

        $organizations = DB::table('users')
            ->whereNotNull('organization')
            ->where('organization', '<>', '')
            ->distinct()
            ->pluck('organization')
            ->map(fn (string $name) => ['name' => $name, 'created_at' => now(), 'updated_at' => now()])
            ->all();

        if ($organizations) {
            DB::table('organizations')->insertOrIgnore($organizations);
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public."organizations" ENABLE ROW LEVEL SECURITY');
            DB::statement('REVOKE ALL ON TABLE public."organizations" FROM anon, authenticated');
            DB::statement('REVOKE ALL ON SEQUENCE public.organizations_id_seq FROM anon, authenticated');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
        DB::table('equipment')->whereNull('category')->update(['category' => '']);

        Schema::table('equipment', function (Blueprint $table) {
            $table->string('category', 80)->change();
            $table->dropColumn('staff_in_charge');
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('staff_in_charge');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_suspended');
        });
    }
};
