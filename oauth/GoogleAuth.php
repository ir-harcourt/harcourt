<?php
require_once __DIR__ . '/PkceTrait.php';

class GoogleAuth {
    use PkceTrait;
    private $clientId;
    private $clientSecret;
    private $scopes = ['openid', 'profile', 'email'];

    private $appUrl;

    public function __construct() {
        $env = @parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/.env');
        if ($env === false) {
            throw new \RuntimeException('OAuth configuration is missing. Contact the administrator.');
        }
        $this->clientId     = $env['GOOGLE_CLIENT_ID'];
        $this->clientSecret = $env['GOOGLE_CLIENT_SECRET'];
        $this->appUrl       = rtrim($env['APP_URL'], '/');
    }

    private function redirectUri() {
        return $this->appUrl . '/oauth/google_callback.php';
    }

    private function composerPath() {
        return $_SERVER['DOCUMENT_ROOT'] . (version_compare(phpversion(), '8', '<') ? '/composer7' : '/composer8');
    }

    private function provider() {
        require_once $this->composerPath() . '/vendor/autoload.php';
        return new \League\OAuth2\Client\Provider\Google([
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUri'  => $this->redirectUri(),
        ]);
    }

    public function init() {
        $provider = $this->provider();

        $verifier = $this->generatePkceVerifier();
        $_SESSION['google_oauth2_pkce_verifier'] = $verifier;

        $url = $provider->getAuthorizationUrl([
            'scope' => $this->scopes,
            'code_challenge' => $this->generatePkceChallenge($verifier),
            'code_challenge_method' => 'S256',
        ]);
        $_SESSION['google_oauth2state'] = $provider->getState();
        $_SESSION['google_oauth2_state_time'] = time();
        header('Location: ' . $url);
        exit;
    }

    public function callback() {
        $state = isset($_GET['state']) ? $_GET['state'] : '';
        if (!$state || !isset($_SESSION['google_oauth2state']) || $state !== $_SESSION['google_oauth2state']) {
            unset($_SESSION['google_oauth2state'], $_SESSION['google_oauth2_pkce_verifier'], $_SESSION['google_oauth2_state_time']);
            $_SESSION['google_oauth_error'] = 'Invalid OAuth state. Please try again.';
            header('Location: /request-access');
            exit;
        }

        $state_age = time() - ($_SESSION['google_oauth2_state_time'] ?? 0);
        if ($state_age > 300) {
            unset($_SESSION['google_oauth2state'], $_SESSION['google_oauth2_pkce_verifier'], $_SESSION['google_oauth2_state_time']);
            $_SESSION['google_oauth_error'] = 'Authentication request expired. Please try again.';
            header('Location: /request-access');
            exit;
        }
        unset($_SESSION['google_oauth2state'], $_SESSION['google_oauth2_state_time']);

        if (isset($_GET['error'])) {
            unset($_SESSION['google_oauth2_pkce_verifier']);
            $_SESSION['google_oauth_error'] = $_GET['error_description'] ?? $_GET['error'];
            header('Location: /request-access');
            exit;
        }

        try {
            $provider = $this->provider();

            if (isset($_SESSION['google_oauth2_pkce_verifier'])) {
                $provider->setPkceCode($_SESSION['google_oauth2_pkce_verifier']);
                unset($_SESSION['google_oauth2_pkce_verifier']);
            }

            $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);

            $owner = $provider->getResourceOwner($token);
            $user  = $owner->toArray();

            if (empty($user['email_verified'])) {
                $_SESSION['google_oauth_error'] = 'Your Google email address is not verified. Please verify it and try again.';
                header('Location: /request-access');
                exit;
            }

            session_regenerate_id(true);

            $_SESSION['google_oauth_result'] = [
                'email'        => strtolower(trim($user['email'] ?? '')),
                'name_first'   => $user['given_name']  ?? '',
                'name_last'    => $user['family_name'] ?? '',
                'display_name' => $user['name']        ?? '',
                'company_name' => '',
                'address'      => '',
                'city'         => '',
                'state'        => '',
                'zip'          => '',
            ];
        } catch (\Exception $e) {
            error_log('[GoogleAuth] ' . $e->getMessage());
            $_SESSION['google_oauth_error'] = 'Google authentication failed. Please try again.';
        }

        header('Location: /request-access');
        exit;
    }
}
?>
