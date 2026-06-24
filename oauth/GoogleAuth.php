<?php
class GoogleAuth {
    private $clientId;
    private $clientSecret;
    private $scopes = ['openid', 'profile', 'email'];

    private $appUrl;

    public function __construct() {
        $env = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/.env');
        $this->clientId     = $env['GOOGLE_CLIENT_ID'];
        $this->clientSecret = $env['GOOGLE_CLIENT_SECRET'];
        $this->appUrl       = rtrim($env['APP_URL'], '/');
    }

    private function redirectUri() {
        return $this->appUrl . '/oauth/google_callback.php';
    }

    private function composerPath() {
        return $_SERVER['DOCUMENT_ROOT'] . (phpversion() < '8' ? '/composer7' : '/composer8');
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
        $url = $provider->getAuthorizationUrl(['scope' => $this->scopes]);
        $_SESSION['google_oauth2state'] = $provider->getState();
        header('Location: ' . $url);
        exit;
    }

    public function callback() {
        $state = isset($_GET['state']) ? $_GET['state'] : '';
        if (!$state || !isset($_SESSION['google_oauth2state']) || $state !== $_SESSION['google_oauth2state']) {
            unset($_SESSION['google_oauth2state']);
            $_SESSION['google_oauth_error'] = 'Invalid OAuth state. Please try again.';
            header('Location: /request-access');
            exit;
        }
        unset($_SESSION['google_oauth2state']);

        if (isset($_GET['error'])) {
            $_SESSION['google_oauth_error'] = htmlspecialchars($_GET['error_description'] ?? $_GET['error']);
            header('Location: /request-access');
            exit;
        }

        try {
            $provider = $this->provider();
            $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);

            $owner = $provider->getResourceOwner($token);
            $user  = $owner->toArray();

            if (empty($user['email_verified'])) {
                $_SESSION['google_oauth_error'] = 'Your Google email address is not verified. Please verify it and try again.';
                header('Location: /request-access');
                exit;
            }

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
