<?php
/**
 * socialite plugin for Craft CMS 3.x
 *
 * Login to Craft with third-party services like Azure and Google.
 *
 * @link      https://codezone.io
 * @copyright Copyright (c) 2020 CodeZone
 */

namespace CodeZone\socialite\controllers;

use Adbar\Dot;
use CodeZone\socialite\drivers\DriverContract;
use CodeZone\socialite\Exception\OAuthException;
use CodeZone\socialite\records\SSOAccountsRecord;
use CodeZone\socialite\Socialite;

use Craft;
use craft\elements\User;
use craft\events\LoginFailureEvent;
use craft\helpers\User as UserHelper;
use craft\web\Controller;
use craft\web\ServiceUnavailableHttpException;
use League\OAuth2\Client\Token\AccessToken;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\Response;

/**
 * @author    CodeZone
 * @package   Socialite
 * @since     0.0.0
 */
class AuthController extends Controller
{
    // Constants
    // =========================================================================

    /**
     * @event LoginFailureEvent The event that is triggered when a failed login attempt was made
     */
    const EVENT_LOGIN_FAILURE = 'loginFailure';

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['handle'];

    /**
     * Make sure redirect requests don't get rejected
     * @var bool
     */
    public $enableCsrfValidation = false;

    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionHandshake($slug)
    {
        if (!Craft::$app->getUser()->getIsGuest()) {
            // Too easy.
            return $this->_handleSuccessfulLogin(false);
        }

        $request = Craft::$app->getRequest();
        $driver = Socialite::$plugin->drivers->find($slug);
        $state = $request->get('state');

        if (!$driver) {
            throw new HttpException(404);
        }

        try {
            // If we don't have an authorization code then get one
            if (!$request->get('code')) {
                return $driver->handleConnect($request);
                // Check given state against previously stored one to mitigate CSRF attack
            } elseif (!$state || ($state !== \Craft::$app->getSession()->get($driver::SESSION_OAUTH_STATE))) {
                \Craft::$app->getSession()->remove($driver::SESSION_OAUTH_STATE);
                throw new OauthException('Invalid State');
            } else {
                $accessToken = $this->handleCallback($request);
            }
        } catch (OAuthException $exception) {
            return $this->_handleLoginFailure($exception->getMessage());
        }

        $user = $this->_userFromToken($driver, $accessToken);
        return $this->_loginUser($user);
    }

    /**
     * Find or create a user from a token
     */
    protected function _userFromToken(DriverContract $driver, AccessToken $token)
    {
        $account = SSOAccountsRecord::where([
            'ssoId' => $token->getResourceOwnerId(),
            'provider' => $driver::slug()
        ])->one();

        if (!$account) {
            $account = new SSOAccountsRecord();
            $account->ssoId = $token->getResourceOwnerId();
            $account->provider = $driver::slug();
        }

        $user = Craft::$app->users->getUserById($account->userId);
        if (!$user) {
            $user = $this->_newUser($driver, $token);
        }
        $this->_populateUser($user, $driver, $token);

        $account->userId = $user->id;
        $account->token = $token->getToken();
        $account->refreshToken = $token->getRefreshToken();
        Craft::$app->elements->saveElement($account);

        return $user;
    }

    /**
     * New up a user using the email and username from the account owner.
     *
     * @param DriverContract $driver
     * @param AccessToken $token
     * @return User
     */
    protected function _newUser(DriverContract $driver, AccessToken $token)
    {
        $user = new User();
        $map = $driver->getUserFieldMap();
        $owner =  new Dot($driver->getOwner($token)->toArray());

        $user->email = $owner->get($map['email']);
        $user->username = ($owner->has($map['username']) || Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) ? $owner->get($map['username']) : $owner->get($map['email']);

        return $user;
    }

    /**
     * Fill a user using data from the account owner.
     *
     * @param User $user
     * @param $driver
     * @param $token
     * @return User
     */
    protected function _populateUser(User $user, $driver, $token)
    {
        $owner = $driver->getOwner($token)->toArray();
        $map = $driver->getUserFieldMap();
        $restricted = ['email', 'username', 'preferredLocale', 'weekStartDay'];
        $tableFields = ['firstName', 'lastName'];
        $dot = new Dot($owner);

        foreach ($map as $field => $dotNotation) {
            if (!in_array($field, $restricted) && $dot->has($dotNotation)) {
                if (in_array($field, $tableFields)) {
                    $user->$field = $dot->get($dotNotation);
                } else {
                    $user->setFieldValue($field, $dot->get($dotNotation));
                }
            }
        }

        return $user;
    }


    /**
     * Log in the user using the stored token.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    protected function _loginUser(User $user)
    {
        if (!Craft::$app->getUser()->getIsGuest()) {
            // Too easy.
            return $this->_handleSuccessfulLogin(false);
        }

        $rememberMe = (bool)Craft::$app->getRequest()->getBodyParam('rememberMe');

        // Delay randomly between 0 and 1.5 seconds.
        usleep(random_int(0, 1500000));

        // Get the session duration
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if ($rememberMe && $generalConfig->rememberedUserSessionDuration !== 0) {
            $duration = $generalConfig->rememberedUserSessionDuration;
        } else {
            $duration = $generalConfig->userSessionDuration;
        }

        // Try logging them in
        if (!Craft::$app->getUser()->login($user, $duration)) {
            // Unknown error
            return $this->_handleLoginFailure(null, $user);
        }

        return $this->_handleSuccessfulLogin(true);
    }

    /**
     * Handles a failed login attempt.
     *
     * @param string|null $authError
     * @param User|null $user
     * @return Response|null
     * @throws ServiceUnavailableHttpException
     */
    private function _handleLoginFailure(string $authError = null, User $user = null)
    {
        $message = UserHelper::getLoginFailureMessage($authError, $user);

        // Fire a 'loginFailure' event
        $event = new LoginFailureEvent([
            'authError' => $authError,
            'message' => $message,
            'user' => $user,
        ]);
        $this->trigger(self::EVENT_LOGIN_FAILURE, $event);

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson([
                'errorCode' => $authError,
                'error' => $event->message
            ]);
        }

        Craft::$app->getSession()->setError($event->message);

        Craft::$app->getUrlManager()->setRouteParams([
            'rememberMe' => (bool)Craft::$app->getRequest()->getBodyParam('rememberMe'),
            'errorCode' => $authError,
            'errorMessage' => $event->message,
        ]);

        return null;
    }

    /**
     * Redirects the user after a successful login attempt, or if they visited the Login page while they were already
     * logged in.
     *
     * @param bool $setNotice Whether a flash notice should be set, if this isn't an Ajax request.
     * @return Response
     */
    private function _handleSuccessfulLogin(bool $setNotice): Response
    {
        // Get the return URL
        $userSession = Craft::$app->getUser();
        $returnUrl = $userSession->getReturnUrl();

        // Clear it out
        $userSession->removeReturnUrl();

        // If this was an Ajax request, just return success:true
        $request = Craft::$app->getRequest();
        if ($request->getAcceptsJson()) {
            $return = [
                'success' => true,
                'returnUrl' => $returnUrl
            ];

            if (Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
                $return['csrfTokenValue'] = $request->getCsrfToken();
            }

            return $this->asJson($return);
        }

        if ($setNotice) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Logged in.'));
        }

        return $this->redirectToPostedUrl($userSession->getIdentity(), $returnUrl);
    }
}
