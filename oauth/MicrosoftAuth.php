<?php
class MicrosoftAuth {
    private $clientId;
    private $tenantId;
    private $clientSecret;
    private $appUrl;
    private $scopes = ['openid', 'profile', 'email', 'User.Read'];

    public function __construct() {
        $env = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/.env');
        $this->clientId     = $env['MICROSOFT_CLIENT_ID'];
        $this->tenantId     = $env['MICROSOFT_TENANT_ID'];
        $this->clientSecret = $env['MICROSOFT_CLIENT_SECRET'];
        $this->appUrl       = rtrim($env['APP_URL'], '/');
    }

    private function redirectUri() {
        return $this->appUrl . '/oauth/callback.php';
    }

    private function composerPath() {
        return $_SERVER['DOCUMENT_ROOT'] . (phpversion() < '8' ? '/composer7' : '/composer8');
    }

    private function provider() {
        require_once $this->composerPath() . '/vendor/autoload.php';
        $profile_fields = ['mail', 'userPrincipalName', 'displayName', 'givenName', 'surname', 'companyName', 'streetAddress', 'city', 'state', 'postalCode'];
        $resource_owner_url = 'https://graph.microsoft.com/v1.0/me?$select=' . implode(',', $profile_fields);
        return new \Greew\OAuth2\Client\Provider\Azure(
            [
                'clientId'     => $this->clientId,
                'clientSecret' => $this->clientSecret,
                'redirectUri'  => $this->redirectUri(),
                'tenantId'     => $this->tenantId,
            ],
            [],
            'https://login.microsoftonline.com',
            $resource_owner_url
        );
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

            $id_token = $token->getValues()['id_token'] ?? '';
            if (!$id_token) {
                throw new \Exception('No ID token received from Microsoft.');
            }
            $parts   = explode('.', $id_token);
            $claims  = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            $email   = $claims['email'] ?? $claims['preferred_username'] ?? $claims['upn'] ?? '';

            $profile = [];
            try {
                $profile = $provider->getResourceOwner($token)->toArray();
            } catch (\Exception $e) {
                error_log('[MicrosoftAuth] Graph API profile lookup failed (User.Read consent may be needed): ' . $e->getMessage());
            }

            $_SESSION['microsoft_oauth_result'] = [
                'email'        => strtolower(trim($profile['mail'] ?? $email)),
                'name_first'   => $profile['givenName']     ?? $claims['given_name']  ?? '',
                'name_last'    => $profile['surname']       ?? $claims['family_name'] ?? '',
                'display_name' => $profile['displayName']   ?? $claims['name']        ?? '',
                'company_name' => $profile['companyName']   ?? '',
                'address'      => $profile['streetAddress'] ?? '',
                'city'         => $profile['city']          ?? '',
                'state'        => $profile['state']         ?? '',
                'zip'          => $profile['postalCode']    ?? '',
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
