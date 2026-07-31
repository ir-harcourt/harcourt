<?php

/**
 * Unit tests for blacklist_class (classes/blacklist.php).
 *
 * Uses the same stub strategy as NewUserTest.php — stubs/scs_header.php is
 * resolved first via include_path to avoid loading the real dependency chain.
 * The blacklist_class is loaded directly from the real file; its parent
 * database_class comes from the shared stubs/database_stub.php mock, which
 * every simple CRUD *_class model's test file uses so they don't collide
 * when PHPUnit loads all test files into one process.
 */

use PHPUnit\Framework\TestCase;

set_include_path(__DIR__ . '/stubs' . PATH_SEPARATOR . get_include_path());

require_once __DIR__ . '/stubs/database_stub.php';

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
