<?php

/**
 * Unit tests for blacklist_class (classes/blacklist.php).
 *
 * Uses the same stub strategy as NewUserTest.php — stubs/scs_header.php is
 * resolved first via include_path to avoid loading the real dependency chain.
 * The blacklist_class is loaded directly from the real file; its parent
 * database_class is stubbed inline.
 */

use PHPUnit\Framework\TestCase;

set_include_path(__DIR__ . '/stubs' . PATH_SEPARATOR . get_include_path());

// ── Stub: database layer ──────────────────────────────────────────────────

if (!class_exists('database_meta_class')) {
    class database_meta_class {
        public $rows  = 0;
        public $error = '';
    }
}

if (!class_exists('database_class')) {
    class database_class {
        public $meta;
        private $_queryResult = [];
        private $_cursor      = 0;
        private $_lastInsertId = 0;

        public function query($q) {
            $this->meta = new database_meta_class();
            if (is_array($q)) $q = implode(' ', $q);

            if (stripos($q, "SHOW TABLES LIKE 'blacklist'") !== false) {
                if (self::$tableExists) {
                    $this->meta->rows = 1;
                    $this->_queryResult = [['Tables_in_db' => 'blacklist']];
                } else {
                    $this->meta->rows = 0;
                    $this->_queryResult = [];
                }
                $this->_cursor = 0;
                return;
            }

            if (stripos($q, 'CREATE TABLE') !== false) {
                self::$tableExists = true;
                return;
            }

            if (stripos($q, 'select') !== false && stripos($q, 'from blacklist') !== false) {
                $matches = [];
                foreach (self::$rows as $row) {
                    if (stripos($q, 'where domain=') !== false) {
                        preg_match("/where domain='([^']+)'/i", $q, $m);
                        if (isset($m[1]) && strtolower($row['domain']) === strtolower($m[1])) {
                            $matches[] = $row;
                        }
                    } elseif (stripos($q, 'where id=') !== false) {
                        preg_match("/where id='?(\d+)'?/i", $q, $m);
                        if (isset($m[1]) && $row['id'] == $m[1]) {
                            $matches[] = $row;
                        }
                    } else {
                        $matches[] = $row;
                    }
                }
                $this->meta->rows = count($matches);
                $this->_queryResult = $matches;
                $this->_cursor = 0;
                return;
            }

            if (stripos($q, 'insert into blacklist') !== false) {
                self::$lastInsertCounter++;
                $this->_lastInsertId = self::$lastInsertCounter;
                preg_match("/domain='([^']+)'/i", $q, $dm);
                preg_match("/comment='([^']*)'/i", $q, $cm);
                preg_match("/created=(\d+)/i", $q, $cr);
                $row = [
                    'id'      => $this->_lastInsertId,
                    'domain'  => isset($dm[1]) ? $dm[1] : '',
                    'comment' => isset($cm[1]) ? $cm[1] : null,
                    'created' => isset($cr[1]) ? (int)$cr[1] : 0,
                ];
                self::$rows[] = $row;
                return;
            }

            if (stripos($q, 'delete from blacklist') !== false) {
                preg_match("/where id='?(\d+)'?/i", $q, $m);
                if (isset($m[1])) {
                    self::$rows = array_values(array_filter(self::$rows, function($r) use ($m) {
                        return $r['id'] != $m[1];
                    }));
                }
                return;
            }

            if (stripos($q, 'insert into log') !== false) {
                self::$insertCounter++;
                $this->_lastInsertId = self::$insertCounter;
                self::$insertedRows[] = $q;
            }
        }

        public function fetch_array() {
            if ($this->_cursor < count($this->_queryResult)) {
                return $this->_queryResult[$this->_cursor++];
            }
            return null;
        }
        public function free_result() {}
        public function insert_id()   { return $this->_lastInsertId; }

        public static $tableExists      = true;
        public static $rows             = [];
        public static $lastInsertCounter = 0;
        public static $insertedRows     = [];
        public static $insertCounter    = 0;

        public static function resetState() {
            self::$tableExists      = true;
            self::$rows             = [];
            self::$lastInsertCounter = 0;
            self::$insertedRows     = [];
            self::$insertCounter    = 0;
        }
    }
}

if (!function_exists('fn_escape')) {
    function fn_escape($value, $quote = TRUE) {
        if ($quote === FALSE) return (strlen($value)) ? $value : "0";
        if ($quote === "null" && !strlen($value)) return "null";
        return "'" . addslashes($value) . "'";
    }
}

// ── Load blacklist class ──────────────────────────────────────────────────

$database = new stdClass();
require_once dirname(__DIR__, 2) . '/classes/blacklist.php';


// ── Tests ─────────────────────────────────────────────────────────────────

class BlacklistTest extends TestCase
{
    /** @var blacklist_class */
    private $bl;

    protected function setUp(): void
    {
        global $database;
        database_class::resetState();
        $database->blacklist = new blacklist_class();
        $this->bl = $database->blacklist;
    }

    // ── check() ───────────────────────────────────────────────────────────

    public function test_check_returns_false_for_empty_email(): void
    {
        $this->assertFalse($this->bl->check(''));
    }

    public function test_check_returns_false_for_email_without_at_sign(): void
    {
        $this->assertFalse($this->bl->check('nodomain'));
    }

    public function test_check_returns_false_for_email_with_empty_domain(): void
    {
        $this->assertFalse($this->bl->check('user@'));
    }

    public function test_check_returns_false_when_domain_not_blacklisted(): void
    {
        $this->assertFalse($this->bl->check('user@safe.com'));
    }

    public function test_check_returns_true_when_domain_is_blacklisted(): void
    {
        database_class::$rows = [
            ['id' => 1, 'domain' => 'evil.com', 'comment' => 'spam', 'created' => time()],
        ];

        $this->assertTrue($this->bl->check('user@evil.com'));
    }

    public function test_check_is_case_insensitive(): void
    {
        database_class::$rows = [
            ['id' => 1, 'domain' => 'evil.com', 'comment' => '', 'created' => time()],
        ];

        $this->assertTrue($this->bl->check('User@EVIL.COM'));
    }

    public function test_check_returns_false_when_table_does_not_exist(): void
    {
        database_class::$tableExists = false;

        $this->assertFalse($this->bl->check('user@evil.com'));
    }

    // ── CRUD ──────────────────────────────────────────────────────────────

    public function test_insert_creates_new_record(): void
    {
        $this->bl->data = new blacklist_data_class();
        $this->bl->data->domain  = 'spam.org';
        $this->bl->data->comment = 'Known spammer';
        $this->bl->update(FALSE);

        $this->assertSame(1, count(database_class::$rows));
        $this->assertSame('spam.org', database_class::$rows[0]['domain']);
    }

    public function test_insert_assigns_auto_increment_id(): void
    {
        $this->bl->data = new blacklist_data_class();
        $this->bl->data->domain = 'test.com';
        $this->bl->update(FALSE);

        $this->assertGreaterThan(0, $this->bl->data->id);
    }

    public function test_read_by_domain(): void
    {
        database_class::$rows = [
            ['id' => 5, 'domain' => 'blocked.net', 'comment' => 'test', 'created' => 1000],
        ];

        $this->bl->read('blocked.net', 'domain');

        $this->assertSame(5, $this->bl->data->id);
        $this->assertSame('blocked.net', $this->bl->data->domain);
        $this->assertSame('test', $this->bl->data->comment);
    }

    public function test_read_by_id(): void
    {
        database_class::$rows = [
            ['id' => 3, 'domain' => 'example.com', 'comment' => '', 'created' => 2000],
        ];

        $this->bl->read(3);

        $this->assertSame('example.com', $this->bl->data->domain);
    }

    public function test_read_resets_data_when_not_found(): void
    {
        $this->bl->read(999);

        $this->assertSame(0, $this->bl->data->id);
        $this->assertNull($this->bl->data->domain);
    }

    public function test_delete_removes_record(): void
    {
        database_class::$rows = [
            ['id' => 1, 'domain' => 'a.com', 'comment' => '', 'created' => 0],
            ['id' => 2, 'domain' => 'b.com', 'comment' => '', 'created' => 0],
        ];

        $this->bl->delete(1);

        $this->assertSame(1, count(database_class::$rows));
        $this->assertSame('b.com', database_class::$rows[0]['domain']);
    }

    // ── table_exists / install ─────────────────────────────────────────────

    public function test_table_exists_returns_true_when_present(): void
    {
        database_class::$tableExists = true;
        $this->assertTrue($this->bl->table_exists());
    }

    public function test_table_exists_returns_false_when_missing(): void
    {
        database_class::$tableExists = false;
        $this->assertFalse($this->bl->table_exists());
    }

    public function test_install_creates_table_when_missing(): void
    {
        database_class::$tableExists = false;

        $this->bl->install();

        $this->assertTrue(database_class::$tableExists);
    }

    public function test_install_is_idempotent(): void
    {
        database_class::$tableExists = true;

        $this->bl->install();

        $this->assertTrue(database_class::$tableExists);
    }

    // ── data class ────────────────────────────────────────────────────────

    public function test_data_class_sets_created_timestamp(): void
    {
        $before = strtotime("now");
        $data   = new blacklist_data_class();
        $after  = strtotime("now");

        $this->assertGreaterThanOrEqual($before, $data->created);
        $this->assertLessThanOrEqual($after, $data->created);
    }

    public function test_data_class_defaults_id_to_zero(): void
    {
        $data = new blacklist_data_class();
        $this->assertSame(0, $data->id);
    }
}
