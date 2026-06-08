<?php

/**
 * Unit tests for remote_access_class (newuser.php).
 *
 * Strategy: stub files in tests/stubs/ are prepended to the include_path so PHP finds
 * them before the real scs_header.php / recaptcha.php / scsmail_rev1.php, preventing
 * the heavy production dependency chain from loading.  All production classes/functions
 * those files would normally provide are defined inline below.
 *
 * The class is loaded once (with output buffered) so the auto-instantiation at the
 * bottom of newuser.php does not break PHPUnit.  Individual tests create a fresh
 * instance via ReflectionClass::newInstanceWithoutConstructor() to avoid side-effects.
 */

use PHPUnit\Framework\TestCase;

// ── 1. Redirect requires to stubs so real includes are never executed ──────
set_include_path(__DIR__ . '/stubs' . PATH_SEPARATOR . get_include_path());

// ── 2. Stub: global functions ──────────────────────────────────────────────
if (!function_exists('harcourt_remote_addr')) {
    function harcourt_remote_addr(): string { return '127.0.0.1'; }
}
if (!function_exists('fn_development_server')) {
    function fn_development_server(): bool { return false; }
}

// ── 3. Stub: production classes used by remote_access_class ───────────────

if (!class_exists('user_data_class')) {
    class user_data_class {
        public $id            = 0;
        public $ip            = '';
        public $company_name  = '';
        public $address       = '';
        public $city          = '';
        public $state         = '';
        public $zip           = '';
        public $country_code  = '';
        public $last_login    = 0;
        public $recaptcha_score = 0.0;
        public $status        = '';
        public $catalog       = 0;
        public $bot_id        = null;
        public $language_code = 'EN';
    }
}

if (!class_exists('address_class')) {
    class address_class {
        public $data;
        public $input    = [];
        public $registry;
        public function __construct($type, $data, $opts = []) {
            $this->data     = (object)['email' => ''];
            $this->registry = new stdClass();
        }
        public function verify()          {}
        public function output($format)   { return []; }
    }
}

if (!class_exists('recaptcha_class')) {
    class recaptcha_class {
        public $score = 0.9;
        public function __construct($page, $version, $opts = []) {}
        public function js()                        { return ''; }
        public function button($label, $opts = [])  { return "<button id='recaptcha_button'>{$label}</button>"; }
        public function score($token)               {}
    }
}

if (!class_exists('scsmail_class')) {
    class scsmail_class {
        public function __construct($type, $data)   {}
        public function recipient($email, $name)    {}
        public function html($html)                 {}
        public function send()                      {}
    }
}

// ── 4. Stub: $database ────────────────────────────────────────────────────

class StubRemoteData {
    public $access       = '';
    public $unlock_code  = '';
    public $token        = '';
    public $email        = '';
}

class StubRemote {
    public $data;
    public $meta;
    public function __construct() {
        $this->data = new StubRemoteData();
        $this->meta = (object)['rows' => 0];
    }
    public function access($a = null, $b = null) { return ''; }
    public function read($email)    {}
    public function expiry()        {}
    public function token()         {}
    public function unlock_code()   {}
    public function update($flag)   {}
}

class StubProfile {
    public $data;
    public function __construct() {
        $this->data = (object)[
            'email'        => '',
            'company_name' => '',
            'address'      => '',
            'city'         => '',
            'state'        => '',
            'zip'          => '',
            'country_code' => '',
        ];
    }
    public function load()              {}
    public function enter($opts = [])   { return ''; }
    public function cookie()            {}
}

class StubUser {
    public $data;
    public $meta;
    public function __construct() {
        $this->data = new user_data_class();
        $this->meta = (object)['error' => 0];
    }
    public function update($flag) {}
}

class StubLog {
    public function update($type, $opts = []) {}
}

class StubDatabase {
    public $remote;
    public $user;
    public $profile;
    public $log;
    public $registry;
    public function __construct() {
        $this->remote   = new StubRemote();
        $this->user     = new StubUser();
        $this->profile  = new StubProfile();
        $this->log      = new StubLog();
        $this->registry = (object)[
            'email' => (object)['webmaster' => 'noreply@example.com'],
        ];
    }
}

// ── 5. Stub: $forms ───────────────────────────────────────────────────────

class StubForms {
    public $error   = [];
    public $message = [0 => ''];
    public function open($attrs, $opts)                     { return '<form>'; }
    public function close()                                 { return '</form>'; }
    public function text($name, $val, $a, $b, $c, $o = []) { return "<input name='{$name}'>"; }
    public function button($label, $opts = [])              { return "<button>{$label}</button>"; }
}

// ── 6. Stub: $menu ────────────────────────────────────────────────────────

class StubMenu {
    public function cookie($a, $b = '')  {}
    public function login_update($v)     {}
    public function head()               {}
    public function copyright()          {}
}

// ── 7. Prime globals & server state, then load newuser.php once ───────────

global $database, $forms, $menu;
$database = new StubDatabase();
$forms    = new StubForms();
$menu     = new StubMenu();

$_COOKIE['harcourt'] = '';
$_COOKIE['remote']   = '';
$_SESSION            = ['user' => new user_data_class()];
$_SERVER['REQUEST_METHOD'] = 'GET';
unset($_SERVER['HTTP_X_REQUESTED_WITH']);

// Buffer all output produced by the auto-instantiation at the bottom of newuser.php.
ob_start();
require_once dirname(__DIR__, 2) . '/newuser.php';
ob_end_clean();


// ── 8. Tests ──────────────────────────────────────────────────────────────

class NewUserTest extends TestCase
{
    /** @var remote_access_class */
    private $sut;

    protected function setUp(): void
    {
        global $database, $forms, $menu;

        $database = new StubDatabase();
        $forms    = new StubForms();
        $menu     = new StubMenu();

        $_SESSION = ['user' => new user_data_class(), 'remote' => 0];
        $_POST    = [];

        // Instantiate without running the constructor so each test starts clean.
        $ref       = new ReflectionClass(remote_access_class::class);
        $this->sut = $ref->newInstanceWithoutConstructor();

        // Initialise the response object that json() normally creates.
        $this->sut->response              = new stdClass();
        $this->sut->response->error       = new stdClass();
        $this->sut->response->html        = new stdClass();
        $this->sut->response->html->unlock_code_message = '';
        $this->sut->response->html->unlock_code_error   = '';
        $this->sut->response->menu        = new stdClass();
    }

    // ── error() ───────────────────────────────────────────────────────────

    public function test_error_stores_result_text(): void
    {
        $this->sut->results = [];
        $this->sut->error   = [];

        $this->sut->error('email', 'Email is required');

        $this->assertSame('Email is required', $this->sut->results['email']);
    }

    public function test_error_adds_to_error_array_when_text_given(): void
    {
        $this->sut->results = [];
        $this->sut->error   = [];

        $this->sut->error('email', 'Email is required');

        $this->assertArrayHasKey('email', $this->sut->error);
    }

    public function test_error_stores_empty_string_in_results(): void
    {
        $this->sut->results = [];
        $this->sut->error   = [];

        $this->sut->error('email', '');

        $this->assertSame('', $this->sut->results['email']);
    }

    public function test_error_does_not_add_to_error_array_when_text_empty(): void
    {
        $this->sut->results = [];
        $this->sut->error   = [];

        $this->sut->error('email', '');

        $this->assertArrayNotHasKey('email', $this->sut->error);
    }

    // ── html_pending ──────────────────────────────────────────────────────

    public function test_html_pending_returns_string(): void
    {
        $this->assertIsString($this->sut->html_pending());
    }

    public function test_html_pending_mentions_pending_status(): void
    {
        $this->assertStringContainsStringIgnoringCase('pending', $this->sut->html_pending());
    }

    // ── css / js ──────────────────────────────────────────────────────────

    public function test_css_wraps_output_in_style_tags(): void
    {
        $css = $this->sut->css();
        $this->assertStringContainsString('<style>', $css);
        $this->assertStringContainsString('</style>', $css);
    }

    public function test_js_wraps_output_in_script_tags(): void
    {
        $js = $this->sut->js();
        $this->assertStringContainsString('<script>', $js);
        $this->assertStringContainsString('</script>', $js);
    }

    public function test_js_defines_fn_newuser_function(): void
    {
        $this->assertStringContainsString('function fn_newuser', $this->sut->js());
    }

    public function test_js_posts_to_newuser_endpoint(): void
    {
        $this->assertStringContainsString('url: "/newuser.php"', $this->sut->js());
    }

    // ── json_verify ───────────────────────────────────────────────────────

    public function test_verify_rejects_blank_unlock_code(): void
    {
        global $database;
        $_POST['unlock_code']                       = '';
        $database->remote->data->unlock_code        = dechex(strtotime('+1 hour'));
        $_SESSION['remote']                         = 0;

        $this->sut->json_verify();

        $this->assertSame('Cannot be blank', $this->sut->response->html->unlock_code_error);
    }

    public function test_verify_rejects_expired_token(): void
    {
        global $database;
        $_POST['unlock_code']                = 'anycode';
        $database->remote->data->unlock_code = dechex(strtotime('-1 hour'));
        $_SESSION['remote']                  = 0;

        $this->sut->json_verify();

        $this->assertStringContainsStringIgnoringCase('expired', $this->sut->response->html->unlock_code_error);
    }

    public function test_verify_rejects_after_excess_failures(): void
    {
        global $database;
        $_POST['unlock_code']                = 'anycode';
        $database->remote->data->unlock_code = dechex(strtotime('+1 hour'));
        $_SESSION['remote']                  = 2;   // 2 previous failures → > 1

        $this->sut->json_verify();

        $this->assertStringContainsStringIgnoringCase('Excess failures', $this->sut->response->html->unlock_code_error);
    }

    public function test_verify_rejects_wrong_code_and_increments_session(): void
    {
        global $database;
        $validHex = dechex(strtotime('+1 hour'));
        $_POST['unlock_code']                = 'wrongcode';
        $database->remote->data->unlock_code = $validHex;
        $_SESSION['remote']                  = 0;

        $this->sut->json_verify();

        $this->assertStringContainsString('Invalid access token', $this->sut->response->html->unlock_code_error);
        $this->assertSame(1, $_SESSION['remote']);
    }

    public function test_verify_clears_error_on_correct_code(): void
    {
        global $database;
        $validHex = dechex(strtotime('+1 hour'));
        $_POST['unlock_code']                = $validHex;
        $database->remote->data->unlock_code = $validHex;
        $_SESSION['remote']                  = 0;
        $_SESSION['user']                    = new user_data_class();

        $this->sut->json_verify();

        $this->assertSame('', $this->sut->response->html->unlock_code_error);
    }

    public function test_verify_sets_scscpq_content_on_success(): void
    {
        global $database;
        $validHex = dechex(strtotime('+1 hour'));
        $_POST['unlock_code']                = $validHex;
        $database->remote->data->unlock_code = $validHex;
        $_SESSION['remote']                  = 0;
        $_SESSION['user']                    = new user_data_class();

        $this->sut->json_verify();

        $this->assertObjectHasAttribute('scscpq_content', $this->sut->response->html);
        $this->assertStringContainsStringIgnoringCase('Congratulations', $this->sut->response->html->scscpq_content);
    }
}
