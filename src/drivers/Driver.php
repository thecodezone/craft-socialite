<?php


namespace CodeZone\socialite\drivers;

use craft\helpers\StringHelper;
use craft\test\Craft;
use craft\web\Request;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use CodeZone\socialite\Exception\OauthException;
use CodeZone\socialite\Socialite;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

abstract class Driver implements DriverContract
{
    // Constants
    // =========================================================================
    const SESSION_OAUTH_STATE = 'socialite:oauth2state';

    // Protected Properties
    // =========================================================================

    /**
     * The provider classname.
     * @var
     */
    protected $_provider;


    public function __construct($provider = null)
    {
        $this->_provider = $provider ? $provider : $this->provider();
    }

    // Public Static methods
    // =========================================================================

    /**
     * @return string
     */
    protected static function name(): string
    {
        $path = explode('\\', static::class);

        return StringHelper::replace(array_pop($path), 'Driver', '');
    }

    /**
     * @return string
     */
    public static function slug(): string
    {
        return StringHelper::toKebabCase(
            StringHelper::replace(static::name(), '/driver', '')
        );
    }

    /**
     * @return string
     */
    public static function label(): string
    {
        return StringHelper::toSpaces(
            StringHelper::replace(static::name(), '/driver', '')
        );
    }

    public static function isConfigured(): bool
    {
        $providers = Socialite::getInstance()->getSettings()->providers;
        return isset($providers[static::slug()]);
    }

    /**
     * Get the auth URL.
     * @return string
     */
    public function getUrl(): string
    {
        return \Craft::$app->getUrlManager()->createUrl(Socialite::$plugin->getHandle() . '/' . static::slug() . '/auth');
    }


    // Public methods
    // =========================================================================

    /**
     * Instantiate and return the provider.
     *
     * @return AbstractProvider
     */
    abstract protected function provider(): AbstractProvider;

    /**
     * Get the providers config.
     *
     * @return array|null
     */
    public function getConfig() {
        if (static::isConfigured()) {
            return array_merge(Socialite::getInstance()->getSettings()->providers[static::slug()], $this->getConfigDefaults());
        }
        return null;
    }

    /**
     * Get the Oauth Provider
     *
     * @return AbstractProvider
     */
    public function getProvider(): AbstractProvider
    {
        return $this->_provider;
    }

    /**
     * Get the token
     */
    public function getToken()
    {
        return \Craft::$app->getSession()->get($this->getSessionTokenKey());
    }

    /**
     * Perform the auth connection
     */
    public function handleRequest(Request $request)
    {
        $state = $request->get('state');

        // If we don't have an authorization code then get one
        if (!$request->get('code')) {
            return $this->handleConnect($request);
        // Check given state against previously stored one to mitigate CSRF attack
        } elseif (!$state || ($state !== \Craft::$app->getSession()->get(self::SESSION_OAUTH_STATE))) {
            \Craft::$app->getSession()->remove(self::SESSION_OAUTH_STATE);
            throw new OauthException('Invalid State');
        } else {
            return $this->handleCallback($request);
        }
    }

    /**
     * @throws OauthException
     */
    public function handleConnect(Request $request)
    {
        // Fetch the authorization URL from the provider; this returns the
        // urlAuthorize option and generates and applies any necessary parameters
        // (e.g. state).
        $authorizationUrl = $this->getProvider()->getAuthorizationUrl();

        // Get the state generated for you and store it to the session.
        \Craft::$app->getSession()->set(static::SESSION_OAUTH_STATE, $this->getProvider()->getState());

        // Redirect the user to the authorization URL.
        return \Craft::$app->getResponse()->redirect($authorizationUrl);
    }

    /**
     * Handle the Oauth callback
     */
    public function handleCallback(Request $request)
    {

        try {
            // Try to get an access token using the authorization code grant.
            $accessToken = $this->getProvider()->getAccessToken('authorization_code', [
                'code' => $request->get('code')
            ]);

            $this->storeToken($accessToken);

        } catch (IdentityProviderException $e) {
            throw new OauthException('Failed to get the access token.', $e);
        }

        return $accessToken;
    }

    // Protected Methods
    // =========================================================================

    protected function storeToken(AccessToken $accessToken)
    {
        \Craft::$app->getSession()->set($this->getSessionTokenKey(), $accessToken->getToken());
    }

    protected function getSessionTokenKey(): string
    {
        return 'socialite:' . static::slug() . ':token';
    }

    protected function getConfigDefaults(): array
    {
        return [
             'redirectUri' => $this->getUrl()
        ];
    }
}