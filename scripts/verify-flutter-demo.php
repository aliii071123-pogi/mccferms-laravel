<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$db = DB::connection();

if ($db->getDriverName() !== 'pgsql') {
    throw new RuntimeException('Use the configured Supabase PostgreSQL connection.');
}

$assert = function (bool $ok, string $message): void {
    if (! $ok) {
        throw new RuntimeException($message);
    }
    echo "PASS: $message\n";
};

$db->beginTransaction();
try {
    $db->statement('SET LOCAL ROLE anon');
    foreach (['users', 'sessions', 'password_reset_tokens', 'students', 'facilities', 'equipment', 'reservations', 'reservation_equipment'] as $table) {
        $privileges = $db->selectOne(
            "SELECT has_table_privilege(current_user, ?, 'SELECT') OR has_table_privilege(current_user, ?, 'INSERT') OR has_table_privilege(current_user, ?, 'UPDATE') OR has_table_privilege(current_user, ?, 'DELETE') AS allowed",
            array_fill(0, 4, "public.$table"),
        );
        $assert(! $privileges->allowed, "Anonymous role cannot access $table");
        $db->statement('SAVEPOINT denied_read');
        $denied = false;
        try {
            $db->select("SELECT * FROM public.\"$table\" LIMIT 1");
        } catch (Throwable $exception) {
            $denied = (string) $exception->getCode() === '42501';
        }
        $db->statement('ROLLBACK TO SAVEPOINT denied_read');
        $db->statement('RELEASE SAVEPOINT denied_read');
        $assert($denied, "Anonymous reads of $table are rejected");
    }
} finally {
    $db->rollBack();
}

echo "FERMS database privacy checks passed.\n";
