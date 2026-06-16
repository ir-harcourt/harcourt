<?php
/*
 * Usage:
 *   php migrate.php                       Show status of all migrations
 *   php migrate.php migrate               Run all pending migrations
 *   php migrate.php rollback              Rollback the last batch of migrations
 *   php migrate.php make <name>           Create a new migration stub file
 *
 * Migration files live in the migrations/ folder.
 * Applied migrations are tracked in the scs_migration table.
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Access denied. Run from command line: php migrate.php\n");
}

define('MIGRATE_ROOT', __DIR__);
define('MIGRATE_DIR',  __DIR__ . DIRECTORY_SEPARATOR . 'migrations');

// Minimal server vars so included libraries don't crash on CLI
$_SERVER['DOCUMENT_ROOT'] = MIGRATE_ROOT;
$_SERVER['HTTP_HOST']     = 'localhost';
$_SERVER['SERVER_ADMIN']  = '';

date_default_timezone_set("America/Detroit");
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

set_include_path(
    get_include_path()
    . PATH_SEPARATOR . MIGRATE_ROOT . '/includes'
    . PATH_SEPARATOR . MIGRATE_ROOT . '/includes_legacy'
    . PATH_SEPARATOR . MIGRATE_ROOT . '/composer'
    . PATH_SEPARATOR . MIGRATE_ROOT . '/scsps'
);

require_once 'database_mysqli.php';
require_once 'functions.php';

// Mirror scs_header.php connection logic exactly
if (fn_development_server()) {
    $database->connect("localhost", "demo", "suburban", "harcourt");
} elseif (gethostname() === 'stage.harcourt.co') {
    $database->connect("localhost", "harcou6_wp_kggm0", "Va#6rmW@36fQ9LrG", "harcou6_wp_bllqe");
} else {
    $database->connect("localhost", "harcou6_wp_kggm0", "Va#6rmW@36fQ9LrG", "harcou6_wp_bllqe");
}
$database->set_charset("utf8mb4");

// ─── Terminal output ──────────────────────────────────────────────────────────

function out($text, $color = '') {
    $map = [
        'green'  => "\033[32m",
        'red'    => "\033[31m",
        'yellow' => "\033[33m",
        'gray'   => "\033[90m",
        'bold'   => "\033[1m",
    ];
    echo ($map[$color] ?? '') . $text . ($color ? "\033[0m" : '') . "\n";
}

function col($text, $width = 58) {
    return str_pad($text, $width, '.', STR_PAD_RIGHT);
}

// ─── Migration tracking table ─────────────────────────────────────────────────

function ensure_migration_table() {
    global $database;
    $database->temp->query(
        "CREATE TABLE IF NOT EXISTS `scs_migration` (
            `id`        int(11)      NOT NULL AUTO_INCREMENT,
            `migration` varchar(255) NOT NULL,
            `batch`     int(11)      NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `scs_migration_key` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Applied database migrations'"
    );
}

function applied_migrations() {
    global $database;
    $applied = [];
    $database->temp->query("SELECT migration, batch FROM scs_migration ORDER BY batch, id");
    while ($database->temp->fetch = $database->temp->fetch_array()) {
        $applied[$database->temp->fetch['migration']] = (int) $database->temp->fetch['batch'];
    }
    $database->temp->free_result();
    return $applied;
}

function next_batch_number() {
    global $database;
    $database->temp->query("SELECT COALESCE(MAX(batch), 0) + 1 AS n FROM scs_migration");
    $database->temp->fetch = $database->temp->fetch_array();
    $n = (int) $database->temp->fetch['n'];
    $database->temp->free_result();
    return $n;
}

// ─── Load migration files ─────────────────────────────────────────────────────

function load_migrations() {
    $files = glob(MIGRATE_DIR . DIRECTORY_SEPARATOR . '*.php') ?: [];
    sort($files);
    $migrations = [];
    foreach ($files as $file) {
        $name = basename($file, '.php');
        $migrations[$name] = require $file;
    }
    return $migrations;
}

// ─── Commands ─────────────────────────────────────────────────────────────────

function cmd_status() {
    $migrations = load_migrations();
    $applied    = applied_migrations();

    if (!$migrations) {
        out("  No migration files found in migrations/", 'yellow');
        return;
    }

    out('');
    foreach ($migrations as $name => $def) {
        $label = col("  {$name} ");
        if (array_key_exists($name, $applied)) {
            out($label . " \033[32mApplied\033[0m ");
        } else {
            out($label . " \033[33mPending\033[0m ");
        }
    }
    out('');
}

function cmd_migrate() {
    global $database;
    $migrations = load_migrations();
    $applied    = applied_migrations();
    $pending    = array_diff_key($migrations, $applied);

    if (!$pending) {
        out('  Nothing to migrate.', 'green');
        return;
    }

    $batch = next_batch_number();

    foreach ($pending as $name => $def) {
        out("  Migrating: {$name}", 'gray');
        $start = microtime(TRUE);
        $error = '';

        $database->transaction("start");
        foreach ((array) $def['up'] as $sql) {
            $database->temp->query(trim($sql));
            if ($database->temp->meta->errno) {
                $error = $database->temp->meta->error . ' (' . $database->temp->meta->errno . ')';
                break;
            }
        }

        if ($error) {
            $database->transaction("rollback");
            out("  FAILED:    {$name}", 'red');
            out("             {$error}", 'red');
            return;
        }

        $database->temp->query(
            "INSERT INTO scs_migration (migration, batch) VALUES (" .
            fn_escape($name) . ", " . fn_escape($batch, FALSE) . ")"
        );
        $database->transaction("commit");

        $ms = round((microtime(TRUE) - $start) * 1000);
        out("  Migrated:  {$name} ({$ms}ms)", 'green');
    }
}

function cmd_rollback() {
    global $database;
    $migrations = load_migrations();
    $applied    = applied_migrations();

    if (!$applied) {
        out('  Nothing to rollback.', 'yellow');
        return;
    }

    $last_batch  = max($applied);
    $to_rollback = array_keys(array_filter($applied, fn($b) => $b === $last_batch));
    rsort($to_rollback);

    foreach ($to_rollback as $name) {
        if (!isset($migrations[$name])) {
            out("  Skipping:  {$name} (file not found)", 'yellow');
            continue;
        }

        out("  Rolling back: {$name}", 'gray');
        $start = microtime(TRUE);
        $error = '';

        $database->transaction("start");
        foreach ((array) $migrations[$name]['down'] as $sql) {
            $database->temp->query(trim($sql));
            if ($database->temp->meta->errno) {
                $error = $database->temp->meta->error . ' (' . $database->temp->meta->errno . ')';
                break;
            }
        }

        if ($error) {
            $database->transaction("rollback");
            out("  FAILED:    {$name}", 'red');
            out("             {$error}", 'red');
            return;
        }

        $database->temp->query("DELETE FROM scs_migration WHERE migration=" . fn_escape($name));
        $database->transaction("commit");

        $ms = round((microtime(TRUE) - $start) * 1000);
        out("  Rolled back: {$name} ({$ms}ms)", 'green');
    }
}

function cmd_make($name) {
    if (!strlen(trim($name))) {
        out("Usage: php migrate.php make <migration_name>", 'yellow');
        out("Example: php migrate.php make add_phone_to_user", 'gray');
        return;
    }

    if (!is_dir(MIGRATE_DIR)) mkdir(MIGRATE_DIR, 0755, TRUE);

    $slug     = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($name)));
    $filename = date('Y_m_d_His') . '_' . $slug . '.php';
    $path     = MIGRATE_DIR . DIRECTORY_SEPARATOR . $filename;

    $stub = <<<'PHP'
<?php
return [
    'description' => '',
    'up' => [
        // SQL to apply this migration (CREATE TABLE, ALTER TABLE, etc.)
    ],
    'down' => [
        // SQL to reverse this migration (DROP TABLE, ALTER TABLE DROP COLUMN, etc.)
    ],
];
PHP;

    file_put_contents($path, $stub);
    out("  Created: migrations/{$filename}", 'green');
}

function cmd_help() {
    out('');
    out('  Usage: php migrate.php [command]', 'bold');
    out('');
    out('  Commands:');
    out('    (no command)          Show migration status', 'gray');
    out('    migrate               Run all pending migrations', 'gray');
    out('    rollback              Rollback the last batch of migrations', 'gray');
    out('    make <name>           Create a new migration stub file', 'gray');
    out('');
}

// ─── Main ─────────────────────────────────────────────────────────────────────

ensure_migration_table();

$command = $argv[1] ?? '';
switch ($command) {
    case 'migrate':  cmd_migrate();           break;
    case 'rollback': cmd_rollback();          break;
    case 'make':     cmd_make($argv[2] ?? ''); break;
    case 'help':     cmd_help();              break;
    default:         cmd_status();            break;
}
