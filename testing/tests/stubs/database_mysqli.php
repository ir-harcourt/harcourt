<?php
// Stub — replaces includes/database_mysqli.php for MigrateTest so migrate.php's
// top-level require never opens a real MySQL connection. Provides a scriptable
// $database double: tests configure MigrateStubDatabase's static properties to
// control query results and inspect which queries ran.

class MigrateStubTemp
{
    public $meta;
    private $rows = [];
    private $cursor = 0;

    public function __construct()
    {
        $this->meta = (object) ['errno' => 0, 'error' => ''];
    }

    public function query($sql)
    {
        $sql = trim($sql);
        MigrateStubDatabase::$executedQueries[] = $sql;

        $isBookkeepingSelect = stripos($sql, 'SELECT migration, batch FROM scs_migration') === 0
            || stripos($sql, 'SELECT COALESCE(MAX(batch)') === 0;

        if (!$isBookkeepingSelect && MigrateStubDatabase::$failNextQuery) {
            $this->meta->errno = 1064;
            $this->meta->error = 'Stub forced failure';
            MigrateStubDatabase::$failNextQuery = false;
        } else {
            $this->meta->errno = 0;
            $this->meta->error = '';
        }

        if (stripos($sql, 'SELECT migration, batch FROM scs_migration') === 0) {
            $this->rows = MigrateStubDatabase::$appliedRows;
        } elseif (stripos($sql, 'SELECT COALESCE(MAX(batch)') === 0) {
            $this->rows = [['n' => MigrateStubDatabase::$nextBatch]];
        } else {
            $this->rows = [];
        }
        $this->cursor = 0;
    }

    public function fetch_array()
    {
        if (!isset($this->rows[$this->cursor])) {
            return false;
        }
        return $this->rows[$this->cursor++];
    }

    public function free_result()
    {
    }
}

class MigrateStubDatabase
{
    public static $appliedRows = [];
    public static $nextBatch = 1;
    public static $failNextQuery = false;
    public static $executedQueries = [];

    public $temp;

    public function __construct()
    {
        $this->temp = new MigrateStubTemp();
    }

    public function connect(...$args)
    {
    }

    public function set_charset($charset)
    {
    }

    public function transaction($action)
    {
        MigrateStubDatabase::$executedQueries[] = "TRANSACTION:{$action}";
    }
}

global $database;
$database = new MigrateStubDatabase();
