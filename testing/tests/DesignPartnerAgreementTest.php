<?php

/**
 * Unit tests for the Design Partner Agreement admin page (design_partner_agreement.php).
 *
 * design_partner_agreement.php is a procedural script (not a class) that blocks
 * every user account sharing a given company_name from selected product
 * categories, via the company_category_block join table. It mirrors user.php's
 * per-user "Category Access" tab, but scoped to company_name instead of user_id.
 *
 * Its AJAX branch (load/save) calls die(json_encode(...)), so tests exercise the
 * underlying functions directly (design_partner_agreement_company_exists(),
 * design_partner_agreement_save(), design_partner_agreement_output()) after a
 * non-AJAX require — same technique UserManagerTest.php uses for tab_category().
 *
 * Stub strategy mirrors UserManagerTest.php: stubs/scs_header.php is resolved
 * first via include_path so the real dependency chain never loads.
 */

use PHPUnit\Framework\TestCase;

set_include_path(__DIR__ . '/stubs' . PATH_SEPARATOR . dirname(__DIR__, 2) . PATH_SEPARATOR . get_include_path());

// ── Stub: global functions normally provided by scs_header.php ─────────────

if (!function_exists('fn_escape')) {
    function fn_escape($value, $quote = TRUE) {
        if ($quote === FALSE) return (strlen($value)) ? $value : "0";
        if ($quote === "null" && !strlen($value)) return "null";
        return "'" . addslashes($value) . "'";
    }
}

if (!class_exists('query_where')) {
    class query_where {
        public $field, $op, $value;
        function __construct($field, $op, $value) {
            $this->field = $field;
            $this->op    = $op;
            $this->value = $value;
        }
    }
}

// ── Stub: $database->user (access gate + company existence lookup) ─────────

if (!class_exists('DesignPartnerAgreementStubUser')) {
    class DesignPartnerAgreementStubUser {
        public $meta;
        public $rowsForCompany = [];
        private $lastWhere;

        function __construct() { $this->meta = (object) ['rows' => 0]; }
        function access($role, $deny = TRUE) { return TRUE; }
        function query($sql) {
            // The stub doesn't parse SQL; the test sets rowsForCompany to
            // reflect whatever company_name was searched for.
            $this->meta->rows = count($this->rowsForCompany);
        }
    }
}

// ── Stub: $database->where() (used by design_partner_agreement_company_exists) ─

if (!class_exists('DesignPartnerAgreementStubDatabase')) {
    class DesignPartnerAgreementStubDatabase {
        function where($conditions) { return ''; }
    }
}

// ── Stub: category catalog + company_category_block join table ────────────

if (!class_exists('DesignPartnerAgreementStubCategory')) {
    class DesignPartnerAgreementStubCategory {
        public $rows = [];
        public $fetch;
        public $data;
        private $cursor = 0;

        function query($sql) { $this->cursor = 0; }
        function fetch_array() {
            if ($this->cursor < count($this->rows)) return $this->rows[$this->cursor++];
            return false;
        }
        function fetch($f = false) {
            $this->data = (object) ['id' => $this->fetch['id'], 'name' => $this->fetch['name']];
        }
        function free_result() {}
    }
}

if (!class_exists('DesignPartnerAgreementStubTemp')) {
    class DesignPartnerAgreementStubTemp {
        public $fetch;
        public $queryLog    = [];
        public $fixtureRows = [];
        private $cursor     = 0;
        private $selecting  = false;

        function query($sql) {
            $this->queryLog[] = $sql;
            $this->selecting  = (stripos($sql, 'select') !== false);
            $this->cursor     = 0;
        }
        function fetch_array() {
            if (!$this->selecting) return false;
            if ($this->cursor < count($this->fixtureRows)) return $this->fixtureRows[$this->cursor++];
            return false;
        }
        function free_result() {}
    }
}

// ── Stub: $forms / $menu ─────────────────────────────────────────────────

if (!class_exists('DesignPartnerAgreementStubForms')) {
    class DesignPartnerAgreementStubForms {
        public $message = [];
        function title($t) {}
        function message() { return implode('', $this->message); }
        function open($a = null, $b = null)  { return '<form>'; }
        function close()                     { return '</form>'; }
        function text($name, $val = '', $w = null, $m = null, $t = null, $o = []) { return ''; }
        function button($label, $o = [])     { return ''; }
        function checkbox($name, $val, $checked, $o = []) {
            return $checked ? "<input type=checkbox name='{$name}' checked>" : "<input type=checkbox name='{$name}'>";
        }
    }
}

if (!class_exists('DesignPartnerAgreementStubMenu')) {
    class DesignPartnerAgreementStubMenu {
        function head() {}
        function copyright() {}
    }
}

// ── Tests ─────────────────────────────────────────────────────────────────

class DesignPartnerAgreementTest extends TestCase
{
    private function categoryFixture(): array {
        return [
            ['id' => 1, 'name' => 'Books'],
            ['id' => 2, 'name' => 'Software'],
            ['id' => 3, 'name' => 'Hardware'],
        ];
    }

    /**
     * design_partner_agreement_save() reads $_POST["company_category_{id}"]
     * directly for every active category, so every key must be present (as
     * user.php's tab_category() save block requires for user_category_{id}).
     */
    private function defaultCheckboxPost(array $checked = []): array {
        $post = ['company_category_1' => 0, 'company_category_2' => 0, 'company_category_3' => 0];
        foreach ($checked as $id) {
            $post["company_category_{$id}"] = 1;
        }
        return $post;
    }

    /**
     * Requires design_partner_agreement.php in non-AJAX mode (so its top-level
     * code renders the page shell rather than hitting the die() branch) and
     * returns the captured HTML plus the globals the test needs to inspect.
     * Must be called from a @runInSeparateProcess test.
     */
    private function requirePage(DesignPartnerAgreementStubUser $user, DesignPartnerAgreementStubCategory $category, DesignPartnerAgreementStubTemp $temp): string {
        global $database, $forms, $menu;

        $database           = new DesignPartnerAgreementStubDatabase();
        $database->user     = $user;
        $database->category = $category;
        $database->temp     = $temp;
        $database->log      = new class { function update($a, $b = []) {} };

        $forms = new DesignPartnerAgreementStubForms();
        $menu  = new DesignPartnerAgreementStubMenu();

        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = [];

        ob_start();
        require dirname(__DIR__, 2) . '/design_partner_agreement.php';
        return ob_get_clean();
    }

    // ── design_partner_agreement_company_exists() ───────────────────────

    /** @runInSeparateProcess @preserveGlobalState disabled */
    public function test_company_exists_true_when_user_rows_found(): void {
        $user = new DesignPartnerAgreementStubUser();
        $user->rowsForCompany = [['id' => 1]];
        $category = new DesignPartnerAgreementStubCategory();
        $temp = new DesignPartnerAgreementStubTemp();

        $this->requirePage($user, $category, $temp);

        $this->assertTrue(design_partner_agreement_company_exists('Acme Corp'));
    }

    /** @runInSeparateProcess @preserveGlobalState disabled */
    public function test_company_exists_false_when_no_user_rows_found(): void {
        $user = new DesignPartnerAgreementStubUser();
        $user->rowsForCompany = [];
        $category = new DesignPartnerAgreementStubCategory();
        $temp = new DesignPartnerAgreementStubTemp();

        $this->requirePage($user, $category, $temp);

        $this->assertFalse(design_partner_agreement_company_exists('Nonexistent Inc'));
    }

    // ── design_partner_agreement_save(): delete-then-reinsert ───────────

    /** @runInSeparateProcess @preserveGlobalState disabled */
    public function test_save_deletes_previous_blocks_before_reapplying(): void {
        $user = new DesignPartnerAgreementStubUser();
        $category = new DesignPartnerAgreementStubCategory();
        $category->rows = $this->categoryFixture();
        $temp = new DesignPartnerAgreementStubTemp();

        $this->requirePage($user, $category, $temp);
        $_POST = $this->defaultCheckboxPost();
        design_partner_agreement_save('Acme Corp');

        $this->assertStringContainsString('DELETE FROM company_category_block', $temp->queryLog[0]);
        $this->assertStringContainsString("company_name='Acme Corp'", $temp->queryLog[0]);
    }

    /** @runInSeparateProcess @preserveGlobalState disabled */
    public function test_save_inserts_rows_only_for_checked_categories(): void {
        $user = new DesignPartnerAgreementStubUser();
        $category = new DesignPartnerAgreementStubCategory();
        $category->rows = $this->categoryFixture();
        $temp = new DesignPartnerAgreementStubTemp();

        $this->requirePage($user, $category, $temp);
        $_POST = $this->defaultCheckboxPost([2, 3]);
        design_partner_agreement_save('Acme Corp');

        $inserts = implode("\n", $temp->queryLog);
        $this->assertStringContainsString("VALUES ('Acme Corp',2)", $inserts);
        $this->assertStringContainsString("VALUES ('Acme Corp',3)", $inserts);
        $this->assertStringNotContainsString("VALUES ('Acme Corp',1)", $inserts);
    }

    /** @runInSeparateProcess @preserveGlobalState disabled */
    public function test_save_removes_all_blocks_when_no_categories_checked(): void {
        $user = new DesignPartnerAgreementStubUser();
        $category = new DesignPartnerAgreementStubCategory();
        $category->rows = $this->categoryFixture();
        $temp = new DesignPartnerAgreementStubTemp();

        $this->requirePage($user, $category, $temp);
        $_POST = $this->defaultCheckboxPost();
        design_partner_agreement_save('Acme Corp');

        $this->assertCount(1, $temp->queryLog);
        $this->assertStringContainsString('DELETE FROM company_category_block', $temp->queryLog[0]);
    }

    // ── design_partner_agreement_output(): rendering ────────────────────

    /** @runInSeparateProcess @preserveGlobalState disabled */
    public function test_output_prechecks_boxes_for_currently_blocked_categories(): void {
        $user = new DesignPartnerAgreementStubUser();
        $category = new DesignPartnerAgreementStubCategory();
        $category->rows = $this->categoryFixture();
        $temp = new DesignPartnerAgreementStubTemp();
        $temp->fixtureRows = [['category_id' => 2]];

        $this->requirePage($user, $category, $temp);
        $html = design_partner_agreement_output('Acme Corp');

        $this->assertStringContainsString("name='company_category_2' checked", $html);
    }

    /** @runInSeparateProcess @preserveGlobalState disabled */
    public function test_output_leaves_unblocked_categories_unchecked(): void {
        $user = new DesignPartnerAgreementStubUser();
        $category = new DesignPartnerAgreementStubCategory();
        $category->rows = $this->categoryFixture();
        $temp = new DesignPartnerAgreementStubTemp();
        $temp->fixtureRows = [['category_id' => 2]];

        $this->requirePage($user, $category, $temp);
        $html = design_partner_agreement_output('Acme Corp');

        $this->assertStringContainsString("name='company_category_1'>", $html);
        $this->assertStringContainsString("name='company_category_3'>", $html);
        $this->assertStringNotContainsString("name='company_category_1' checked", $html);
        $this->assertStringNotContainsString("name='company_category_3' checked", $html);
    }

    /** @runInSeparateProcess @preserveGlobalState disabled */
    public function test_output_lists_every_active_category_by_name(): void {
        $user = new DesignPartnerAgreementStubUser();
        $category = new DesignPartnerAgreementStubCategory();
        $category->rows = $this->categoryFixture();
        $temp = new DesignPartnerAgreementStubTemp();

        $this->requirePage($user, $category, $temp);
        $html = design_partner_agreement_output('Acme Corp');

        $this->assertStringContainsString('Books', $html);
        $this->assertStringContainsString('Software', $html);
        $this->assertStringContainsString('Hardware', $html);
    }

    /** @runInSeparateProcess @preserveGlobalState disabled */
    public function test_output_scopes_blocks_to_the_given_company_only(): void {
        $user = new DesignPartnerAgreementStubUser();
        $category = new DesignPartnerAgreementStubCategory();
        $category->rows = $this->categoryFixture();
        $temp = new DesignPartnerAgreementStubTemp();
        $temp->fixtureRows = [['category_id' => 1]];

        $this->requirePage($user, $category, $temp);
        design_partner_agreement_output('Acme Corp');

        $this->assertStringContainsString("company_name='Acme Corp'", $temp->queryLog[0]);
    }
}
