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

use CodeZone\socialite\Exception\OAuthException;
use CodeZone\socialite\Socialite;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use yii\web\HttpException;

/**
 * @author    CodeZone
 * @package   Socialite
 * @since     0.0.0
 */
class AuthController extends Controller
{
    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['handle'];

    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionHandle($slug)
    {
        if (!Craft::$app->getUser()->getIsGuest()) {
            return Craft::$app->getResponse()->redirect(
                UrlHelper::siteUrl(Craft::$app->getConfig()->getGeneral()->getPostLoginRedirect())
            );
        }
        $driver = Socialite::$plugin->drivers->find($slug);

        if (!$driver) {
            throw new HttpException(404);
        }

        try {
            $result = $driver->handleRequest(Craft::$app->getRequest());
        } catch (OAuthException $exception) {
            throw new HttpException(401, $exception->getMessage());
        }
        return $result;
    }
}
