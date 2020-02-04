<?php


namespace CodeZone\socialite\drivers;

use Adbar\Dot;
use CodeZone\socialite\records\SSOAccountsRecord;
use craft\helpers\StringHelper;
use craft\web\Request;
use craft\elements\User;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
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
    protected $provider;

    public function __construct($provider = null)
    {
        $this->provider = $provider ? $provider : $this->provider($this->getConfig());
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


    // Public methods
    // =========================================================================


    /**
     * Get the auth URL.
     * @return string
     */
    public function getUrl(): string
    {
        return trim(\Craft::getAlias('@web')) . '/' . Socialite::$plugin->getHandle() . '/' . static::slug() . '/auth';
    }

    /**
     * Get the owner
     * @param AccessToken $accessToken
     * @return mixed
     */
    public function getOwner(AccessToken $accessToken): ResourceOwnerInterface
    {
        return $this->getProvider()->getResourceOwner($accessToken);
    }

    /**
     * Return an array that maps user field keys to provider field keys.
     * @return array
     */
    public function getUserFieldMap(): array
    {
        $configMap = isset($this->getConfig()['userFieldMap']) ? $this->getConfig()['userFieldMap'] : [];
        return array_merge($this->userFieldMap(), $configMap);
    }

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
        return $this->provider;
    }

    /**
     * @param Request $request
     * @return \craft\web\Response|\yii\console\Response
     * @throws \craft\errors\MissingComponentException
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
            $accessToken = $this->generateAccessToken($request->get('code'));
        } catch (IdentityProviderException $e) {
            throw new OauthException('Failed to get the access token.', $e->getCode(), $e);
        }

        return $accessToken;
    }

    /**
     * Get the owner ID for an access token from the Oauth provider.
     * @param AccessToken $accessToken
     * @return mixed
     */
    public function getOwnerId(AccessToken $accessToken)
    {
        return $this->getOwner($accessToken)->getId();
    }

    /**
     * Check to see if we have a session access token. If not, refresh the stored token.
     *
     * @param null $user
     * @return bool|\League\OAuth2\Client\Token\AccessTokenInterface|mixed
     * @throws \craft\errors\MissingComponentException
     */
    public function getAccessToken($user = null)
    {
        if (!$user) {
            $user = \Craft::$app->getUser();
        }

        if (!$user) {
            return false;
        }

        if (!\Craft::$app->getRequest()->isConsoleRequest) {
            $accessToken = \Craft::$app->getSession()->get($this->getSessionTokenKey());
        } else {
            $accessToken = null;
        }

        if ($accessToken) {
            return $accessToken;
        }

        $account = SSOAccountsRecord::find()->where([
            'userId' => $user->getId(),
            'provider' => static::slug()
        ])->one();

        if (!$account) {
            return false;
        }

        $accessToken = $this->refreshAccessToken($account->refreshToken);
        Socialite::$plugin->users->syncSsoAccount($account, $accessToken);

        return $accessToken;
    }

    /**
     * Do any additional steps the driver needs to do after a user is logged in.
     * @param $accessToken
     * @param $user
     * @param $ssoAccount
     */
    public function cleanup(User $user, AccessToken $token)
    {
        //To be extended
    }



    // Protected methods
    // =========================================================================


    protected function generateAccessToken($code)
    {
        $accessToken = $this->getProvider()->getAccessToken('authorization_code', array_merge(
                $this->getAccessTokenDefaults(), [
                'code' => $code
            ])
        );

        if (!\Craft::$app->getRequest()->isConsoleRequest) {
            \Craft::$app->getSession()->set($this->getSessionTokenKey(), $accessToken);
        }

        return $accessToken;
    }

    protected function refreshAccessToken($refreshToken)
    {
        $accessToken = $this->getProvider()->getAccessToken('refresh_token', array_merge(
                $this->getAccessTokenDefaults(), [
                'refresh_token' => $refreshToken
            ])
        );

        if (!\Craft::$app->getRequest()->isConsoleRequest) {
            \Craft::$app->getSession()->set($this->getSessionTokenKey(), $accessToken);
        }

        return $accessToken;
    }

    /**
     * Instantiate and return the provider.
     *
     * @return AbstractProvider
     */
    abstract protected function provider(array $config): AbstractProvider;

    protected function getSessionTokenKey(): string
    {
        return 'socialite:' . static::slug() . ':token';
    }

    protected function getConfigDefaults(): array
    {
        return [
             'redirectUri' => $this ->getUrl()
        ];
    }

    protected function getAccessTokenDefaults(): array
    {
        return [
            'resource' => "https://graph.microsoft.com/"
        ];
    }

    /**
     * Return an array that maps user field keys to provider field keys.
     * @return array
     */
    protected function userFieldMap(): array
    {
        return [
            'email' => 'email'
        ];
    }
}