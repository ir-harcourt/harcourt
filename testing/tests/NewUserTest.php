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
        public $domain        = '';
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
        $this->meta = (object)['error' => 0, 'rows' => 0];
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

        $_SESSION = ['user' => new user_data_class(), 'remote' => 0, 'impersonate' => 0];
        $_POST    = [];
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

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

    // ── Microsoft OAuth ───────────────────────────────────────────────────

    private function oauthData(array $overrides = []): array
    {
        return array_merge([
            'email'        => 'user@example.com',
            'name_first'   => 'Test',
            'name_last'    => 'User',
            'display_name' => 'Test User',
            'company_name' => '',
            'address'      => '',
            'city'         => '',
            'state'        => '',
            'zip'          => '',
        ], $overrides);
    }

    public function test_html_microsoft_button_links_to_oauth_init(): void
    {
        $html = $this->sut->html_microsoft_button();

        $this->assertStringContainsString('Sign in with Microsoft', $html);
        $this->assertStringContainsString("href='/oauth/init.php'", $html);
    }

    public function test_html_register_includes_microsoft_button(): void
    {
        $this->sut->recaptcha = new recaptcha_class("newuser", 1, array("local" => TRUE));

        $html = $this->sut->html_register();

        $this->assertStringContainsString('Sign in with Microsoft', $html);
    }

    public function test_html_remote_includes_microsoft_button(): void
    {
        $html = $this->sut->html_remote(FALSE);

        $this->assertStringContainsString('Sign in with Microsoft', $html);
    }

    public function test_html_dispatches_to_oauth_handler_when_result_set(): void
    {
        $_SESSION['microsoft_oauth_result'] = $this->oauthData(['email' => '']);

        ob_start();
        $this->sut->html();
        $output = ob_get_clean();

        $this->assertStringContainsStringIgnoringCase('did not provide an email', $output);
    }

    public function test_process_microsoft_oauth_shows_error_and_register_form(): void
    {
        $_SESSION['microsoft_oauth_error'] = 'Something went wrong';

        ob_start();
        $this->sut->process_microsoft_oauth();
        $output = ob_get_clean();

        $this->assertStringContainsString('Something went wrong', $output);
        $this->assertStringContainsString('Sign in with Microsoft', $output);
        $this->assertArrayNotHasKey('microsoft_oauth_error', $_SESSION);
    }

    public function test_process_microsoft_oauth_shows_error_when_email_missing(): void
    {
        $_SESSION['microsoft_oauth_result'] = $this->oauthData(['email' => '']);

        ob_start();
        $this->sut->process_microsoft_oauth();
        $output = ob_get_clean();

        $this->assertStringContainsStringIgnoringCase('did not provide an email', $output);
        $this->assertArrayNotHasKey('microsoft_oauth_result', $_SESSION);
    }

    public function test_process_microsoft_oauth_grants_access_when_remote_already_registered(): void
    {
        global $database;
        $database->remote->meta->rows = 1;
        $_SESSION['microsoft_oauth_result'] = $this->oauthData(['email' => 'known@harcourt.co']);

        ob_start();
        $this->sut->process_microsoft_oauth();
        $output = ob_get_clean();

        $this->assertStringContainsString('Congratulations, you now have access!', $output);
        $this->assertSame('known@harcourt.co', $database->remote->data->email);
        $this->assertSame(dechex(strtotime('+1 year')), $database->remote->data->unlock_code);
    }

    public function test_process_microsoft_oauth_grants_access_when_domain_active(): void
    {
        global $database;
        $database->user->meta->rows  = 1;
        $database->user->data->status = 'Active';
        $_SESSION['microsoft_oauth_result'] = $this->oauthData(['email' => 'new.person@harcourt.co']);

        ob_start();
        $this->sut->process_microsoft_oauth();
        $output = ob_get_clean();

        $this->assertStringContainsString('Congratulations, you now have access!', $output);
        $this->assertSame('new.person@harcourt.co', $database->remote->data->email);
    }

    public function test_process_microsoft_oauth_shows_pending_when_domain_pending(): void
    {
        global $database;
        $database->user->meta->rows   = 1;
        $database->user->data->status = 'Pending';
        $_SESSION['microsoft_oauth_result'] = $this->oauthData(['email' => 'pending.person@example.com']);

        ob_start();
        $this->sut->process_microsoft_oauth();
        $output = ob_get_clean();

        $this->assertStringContainsStringIgnoringCase('pending approval', $output);
    }

    public function test_process_microsoft_oauth_registers_new_user_when_no_domain_match(): void
    {
        global $database;
        $_SESSION['microsoft_oauth_result'] = $this->oauthData([
            'email'        => 'new.user@example.com',
            'display_name' => 'New User',
            'company_name' => 'Acme Inc',
            'address'      => '123 Main St',
            'city'         => 'Springfield',
            'state'        => 'IL',
            'zip'          => '62704',
        ]);

        ob_start();
        $this->sut->process_microsoft_oauth();
        $output = ob_get_clean();

        $this->assertStringContainsString('Congratulations, your access request has been submitted!', $output);
        $this->assertSame('example.com', $database->user->data->domain);
        $this->assertSame('Acme Inc', $database->user->data->company_name);
        $this->assertSame($database->user->data, $_SESSION['user']);
    }

    public function test_grant_microsoft_remote_access_sets_year_long_unlock_code(): void
    {
        global $database;

        ob_start();
        $this->sut->grant_microsoft_remote_access('user@harcourt.co', TRUE);
        $output = ob_get_clean();

        $this->assertSame('user@harcourt.co', $database->remote->data->email);
        $this->assertSame(dechex(strtotime('+1 year')), $database->remote->data->unlock_code);
        $this->assertStringContainsString('Congratulations, you now have access!', $output);
        $this->assertStringContainsString('action: "initial"', $output);
        $this->assertStringContainsString('scscpq_url', $output);
    }

    public function test_process_microsoft_register_returns_error_on_update_failure(): void
    {
        global $database;
        $database->user->meta->error = 1;

        $result = $this->sut->process_microsoft_register('fail@example.com', $this->oauthData(['email' => 'fail@example.com']));

        $this->assertStringContainsStringIgnoringCase('Internal error', $result);
    }

    public function test_process_microsoft_register_populates_domain_from_email(): void
    {
        global $database;

        $result = $this->sut->process_microsoft_register('jane@acme.org', $this->oauthData([
            'email'        => 'jane@acme.org',
            'display_name' => 'Jane Doe',
            'company_name' => 'Acme Org',
        ]));

        $this->assertSame('acme.org', $database->user->data->domain);
        $this->assertSame('Acme Org', $database->user->data->company_name);
        $this->assertStringContainsString('access request has been submitted', $result);
    }
}
