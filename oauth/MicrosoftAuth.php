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

    private function generatePkceVerifier() {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function generatePkceChallenge($verifier) {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    public function init() {
        $provider = $this->provider();

        $verifier = $this->generatePkceVerifier();
        $_SESSION['oauth2_pkce_verifier'] = $verifier;

        $url = $provider->getAuthorizationUrl([
            'scope' => $this->scopes,
            'code_challenge' => $this->generatePkceChallenge($verifier),
            'code_challenge_method' => 'S256',
        ]);
        $_SESSION['oauth2state'] = $provider->getState();
        header('Location: ' . $url);
        exit;
    }

    public function callback() {
        $state = isset($_GET['state']) ? $_GET['state'] : '';
        if (!$state || !isset($_SESSION['oauth2state']) || $state !== $_SESSION['oauth2state']) {
            unset($_SESSION['oauth2state']);
            unset($_SESSION['oauth2_pkce_verifier']);
            $_SESSION['microsoft_oauth_error'] = 'Invalid OAuth state. Please try again.';
            header('Location: /request-access');
            exit;
        }
        unset($_SESSION['oauth2state']);

        if (isset($_GET['error'])) {
            unset($_SESSION['oauth2_pkce_verifier']);
            $_SESSION['microsoft_oauth_error'] = htmlspecialchars($_GET['error_description'] ?? $_GET['error']);
            header('Location: /request-access');
            exit;
        }

        try {
            $provider = $this->provider();

            if (isset($_SESSION['oauth2_pkce_verifier'])) {
                $provider->setPkceCode($_SESSION['oauth2_pkce_verifier']);
                unset($_SESSION['oauth2_pkce_verifier']);
            }

            $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);

            $profile = [];
            try {
                $profile = $provider->getResourceOwner($token)->toArray();
            } catch (\Exception $e) {
                error_log('[MicrosoftAuth] Graph API profile lookup failed (User.Read consent may be needed): ' . $e->getMessage());
            }

            // ID token claims as fallback — safe in auth code flow since the
            // token was delivered server-to-server over TLS with client_secret.
            $claims = [];
            $id_token = $token->getValues()['id_token'] ?? '';
            if ($id_token && count(explode('.', $id_token)) === 3) {
                $parts  = explode('.', $id_token);
                $claims = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) ?: [];
            }

            $email = strtolower(trim(
                $profile['mail'] ?? $profile['userPrincipalName'] ?? $claims['email'] ?? $claims['preferred_username'] ?? $claims['upn'] ?? ''
            ));
            if (empty($email) || strpos($email, '@') === false) {
                throw new \Exception('No verified email address available from Microsoft account.');
            }

            session_regenerate_id(true);

            $_SESSION['microsoft_oauth_result'] = [
                'email'        => $email,
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
