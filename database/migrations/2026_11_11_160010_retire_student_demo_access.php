<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE public.students ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS student_demo_read ON public.students;
DROP POLICY IF EXISTS student_demo_insert ON public.students;
DROP POLICY IF EXISTS student_demo_update ON public.students;
DROP POLICY IF EXISTS student_demo_delete ON public.students;
DROP TRIGGER IF EXISTS student_app_validate_before_write ON public.students;
DROP FUNCTION IF EXISTS public.student_app_search(text, integer, integer);
DROP FUNCTION IF EXISTS public.student_app_validate_row();
REVOKE ALL ON TABLE public.students FROM anon, authenticated;
REVOKE ALL ON SEQUENCE public.students_id_seq FROM anon, authenticated;
NOTIFY pgrst, 'reload schema';
SQL);
    }

    public function down(): void
    {
        // The anonymous student demo remains retired if later migrations are rolled back.
    }
};
