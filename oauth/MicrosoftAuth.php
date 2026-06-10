<?php
class MicrosoftAuth {
    private $clientId;
    private $tenantId;
    private $clientSecret;
    private $scopes = ['openid', 'profile', 'email', 'User.Read'];

    public function __construct() {
        $env = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/.env');
        $this->clientId     = $env['MICROSOFT_CLIENT_ID'];
        $this->tenantId     = $env['MICROSOFT_TENANT_ID'];
        $this->clientSecret = $env['MICROSOFT_CLIENT_SECRET'];
    }

    private function redirectUri() {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'] . '/oauth/callback.php';
    }

    private function composerPath() {
        return $_SERVER['DOCUMENT_ROOT'] . (phpversion() < '8' ? '/composer7' : '/composer8');
    }

    private function provider() {
        require_once $this->composerPath() . '/vendor/autoload.php';
        return new \Greew\OAuth2\Client\Provider\Azure([
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUri'  => $this->redirectUri(),
            'tenantId'     => $this->tenantId,
        ]);
    }

    public function init() {
        $provider = $this->provider();
        $url = $provider->getAuthorizationUrl(['scope' => $this->scopes]);
        $_SESSION['oauth2state'] = $provider->getState();
        header('Location: ' . $url);
        exit;
    }

    public function callback() {
        $state = isset($_GET['state']) ? $_GET['state'] : '';
        if (!$state || !isset($_SESSION['oauth2state']) || $state !== $_SESSION['oauth2state']) {
            unset($_SESSION['oauth2state']);
            $_SESSION['microsoft_oauth_error'] = 'Invalid OAuth state. Please try again.';
            header('Location: /request-access');
            exit;
        }
        unset($_SESSION['oauth2state']);

        if (isset($_GET['error'])) {
            $_SESSION['microsoft_oauth_error'] = htmlspecialchars($_GET['error_description'] ?? $_GET['error']);
            header('Location: /request-access');
            exit;
        }

        try {
            $provider = $this->provider();
            $token    = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);

            $parts   = explode('.', $token->getToken());
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);

            $email = $payload['unique_name'] ?? $payload['upn'] ?? $payload['email'] ?? '';
            $_SESSION['microsoft_oauth_result'] = [
                'email'        => strtolower(trim($email)),
                'name_first'   => $payload['given_name']  ?? '',
                'name_last'    => $payload['family_name'] ?? '',
                'display_name' => $payload['name']        ?? '',
            ];
        } catch (\Exception $e) {
            error_log('[MicrosoftAuth] ' . $e->getMessage());
            $_SESSION['microsoft_oauth_error'] = 'Microsoft authentication failed. Please try again.';
        }

        header('Location: /request-access');
        exit;
    }
}
?>
