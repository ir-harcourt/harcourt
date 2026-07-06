<?php

/**
 * Unit tests for the migration runner (migrate.php).
 *
 * Strategy: migrate.php is a CLI script that requires 'database_mysqli.php' and
 * 'functions.php' by bare filename (resolved via include_path) and unconditionally
 * connects to a real database and runs a command at the bottom of the file. Stub
 * files in tests/stubs/ are prepended to the include_path so PHP finds lightweight
 * doubles instead of the real production files, and $argv is forced to an empty
 * command so the top-level require only exercises the harmless "status" branch.
 *
 * Once required, migrate.php's top-level functions (load_migrations, cmd_migrate,
 * cmd_rollback, cmd_make, ...) exist in the global scope and are called directly,
 * with MigrateStubDatabase used to script query results and inspect what was run.
 */

use PHPUnit\Framework\TestCase;

// ── 1. Redirect requires to stubs so real DB/config files are never loaded ─────
set_include_path(__DIR__ . '/stubs' . PATH_SEPARATOR . get_include_path());

// ── 2. Force a harmless default command for the top-level require ─────────────
$argv = ['migrate.php'];

// ── 3. Load migrate.php once; buffer the "status" output it prints on require ─
ob_start();
require_once dirname(__DIR__, 2) . '/migrate.php';
ob_end_clean();

// ── 4. Tests ────────────────────────────────────────────────────────────────

class MigrateTest extends TestCase
{
    /** @var string[] filenames created by cmd_make() during a test, cleaned up in tearDown */
    private $createdFiles = [];

    protected function setUp(): void
    {
        // PHPUnit's backupGlobals restores $GLOBALS to its pre-test-file snapshot
        // before each test, which would null out $database again — recreate it here.
        global $database;
        $database = new MigrateStubDatabase();

        MigrateStubDatabase::$appliedRows    = [];
        MigrateStubDatabase::$nextBatch      = 1;
        MigrateStubDatabase::$failNextQuery  = false;
        MigrateStubDatabase::$executedQueries = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        $this->createdFiles = [];
    }

    private function realMigrationNames(): array
    {
        $files = glob(MIGRATE_DIR . DIRECTORY_SEPARATOR . '*.php') ?: [];
        return array_map(fn($f) => basename($f, '.php'), $files);
    }

    // ── load_migrations() ───────────────────────────────────────────────────

    public function test_load_migrations_returns_up_and_down_for_each_file(): void
    {
        $migrations = load_migrations();

        $this->assertNotEmpty($migrations);
        foreach ($migrations as $name => $def) {
            $this->assertArrayHasKey('up', $def, "Migration {$name} missing 'up'");
            $this->assertArrayHasKey('down', $def, "Migration {$name} missing 'down'");
        }
    }

    public function test_load_migrations_includes_known_migration(): void
    {
        $migrations = load_migrations();

        $this->assertArrayHasKey('2026_06_01_000000_create_user_category', $migrations);
    }

    // ── applied_migrations() ────────────────────────────────────────────────

    public function test_applied_migrations_maps_name_to_batch(): void
    {
        MigrateStubDatabase::$appliedRows = [
            ['migration' => 'foo_migration', 'batch' => '2'],
            ['migration' => 'bar_migration', 'batch' => '3'],
        ];

        $applied = applied_migrations();

        $this->assertSame(['foo_migration' => 2, 'bar_migration' => 3], $applied);
    }

    public function test_applied_migrations_empty_when_no_rows(): void
    {
        MigrateStubDatabase::$appliedRows = [];

        $this->assertSame([], applied_migrations());
    }

    // ── next_batch_number() ─────────────────────────────────────────────────

    public function test_next_batch_number_reads_stub_value(): void
    {
        MigrateStubDatabase::$nextBatch = 7;

        $this->assertSame(7, next_batch_number());
    }

    // ── cmd_migrate() ───────────────────────────────────────────────────────

    public function test_cmd_migrate_reports_nothing_pending_when_all_applied(): void
    {
        MigrateStubDatabase::$appliedRows = array_map(
            fn($name) => ['migration' => $name, 'batch' => '1'],
            $this->realMigrationNames()
        );

        ob_start();
        cmd_migrate();
        $output = ob_get_clean();

        $this->assertStringContainsString('Nothing to migrate.', $output);
    }

    public function test_cmd_migrate_runs_pending_migration_and_records_it(): void
    {
        $names   = $this->realMigrationNames();
        $pending = array_shift($names); // leave the first migration pending
        MigrateStubDatabase::$appliedRows = array_map(
            fn($name) => ['migration' => $name, 'batch' => '1'],
            $names
        );
        MigrateStubDatabase::$nextBatch = 2;

        ob_start();
        cmd_migrate();
        $output = ob_get_clean();

        $this->assertStringContainsString("Migrated:  {$pending}", $output);
        $inserted = array_filter(
            MigrateStubDatabase::$executedQueries,
            fn($q) => stripos($q, 'INSERT INTO scs_migration') === 0 && strpos($q, $pending) !== false
        );
        $this->assertNotEmpty($inserted, 'Expected an INSERT recording the migrated file');
    }

    public function test_cmd_migrate_rolls_back_transaction_on_sql_failure(): void
    {
        $names   = $this->realMigrationNames();
        $pending = array_shift($names);
        MigrateStubDatabase::$appliedRows = array_map(
            fn($name) => ['migration' => $name, 'batch' => '1'],
            $names
        );
        MigrateStubDatabase::$failNextQuery = true;

        ob_start();
        cmd_migrate();
        $output = ob_get_clean();

        $this->assertStringContainsString("FAILED:    {$pending}", $output);
        $this->assertContains('TRANSACTION:rollback', MigrateStubDatabase::$executedQueries);
        $this->assertNotContains('TRANSACTION:commit', MigrateStubDatabase::$executedQueries);
    }

    // ── cmd_rollback() ──────────────────────────────────────────────────────

    public function test_cmd_rollback_reports_nothing_when_none_applied(): void
    {
        MigrateStubDatabase::$appliedRows = [];

        ob_start();
        cmd_rollback();
        $output = ob_get_clean();

        $this->assertStringContainsString('Nothing to rollback.', $output);
    }

    public function test_cmd_rollback_reverts_last_batch_and_deletes_record(): void
    {
        $names  = $this->realMigrationNames();
        $target = $names[0];
        MigrateStubDatabase::$appliedRows = [
            ['migration' => $target, 'batch' => '1'],
        ];

        ob_start();
        cmd_rollback();
        $output = ob_get_clean();

        $this->assertStringContainsString("Rolled back: {$target}", $output);
        $deleted = array_filter(
            MigrateStubDatabase::$executedQueries,
            fn($q) => stripos($q, 'DELETE FROM scs_migration') === 0 && strpos($q, $target) !== false
        );
        $this->assertNotEmpty($deleted, 'Expected a DELETE removing the rolled-back record');
    }

    public function test_cmd_rollback_skips_record_with_missing_file(): void
    {
        MigrateStubDatabase::$appliedRows = [
            ['migration' => '__nonexistent_migration_file__', 'batch' => '1'],
        ];

        ob_start();
        cmd_rollback();
        $output = ob_get_clean();

        $this->assertStringContainsString('Skipping:  __nonexistent_migration_file__', $output);
        $this->assertStringContainsString('(file not found)', $output);
    }

    public function test_cmd_rollback_only_reverts_last_batch(): void
    {
        $names = $this->realMigrationNames();
        $this->assertGreaterThanOrEqual(2, count($names), 'Test requires at least two migration files');

        MigrateStubDatabase::$appliedRows = [
            ['migration' => $names[0], 'batch' => '1'],
            ['migration' => $names[1], 'batch' => '2'],
        ];

        ob_start();
        cmd_rollback();
        $output = ob_get_clean();

        $this->assertStringContainsString("Rolled back: {$names[1]}", $output);
        $this->assertStringNotContainsString("Rolled back: {$names[0]}", $output);
    }

    // ── cmd_make() ──────────────────────────────────────────────────────────

    public function test_cmd_make_requires_a_name(): void
    {
        ob_start();
        cmd_make('');
        $output = ob_get_clean();

        $this->assertStringContainsString('Usage: php migrate.php make <migration_name>', $output);
    }

    public function test_cmd_make_creates_stub_file_with_expected_shape(): void
    {
        ob_start();
        cmd_make('phpunit test Migration');
        $output = ob_get_clean();

        $this->assertRegExp(
            '/Created: migrations\/\d{4}_\d{2}_\d{2}_\d{6}_phpunit_test_migration\.php/',
            $output
        );

        preg_match('/migrations\/(\S+\.php)/', $output, $m);
        $path = MIGRATE_DIR . DIRECTORY_SEPARATOR . $m[1];
        $this->createdFiles[] = $path;

        $this->assertFileExists($path);
        $def = require $path;
        $this->assertArrayHasKey('up', $def);
        $this->assertArrayHasKey('down', $def);
    }
}
