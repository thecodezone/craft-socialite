<?php
/**
 * socialite plugin for Craft CMS 3.x
 *
 * Login to Craft with third-party services like Azure and Google. 
 *
 * @link      https://codezone.io
 * @copyright Copyright (c) 2020 CodeZone
 */

namespace CodeZone\socialite;

use craft\services\Plugins;
use CodeZone\socialite\drivers\AzureDriver;
use CodeZone\socialite\drivers\GenericDriver;
use CodeZone\socialite\listeners\RegisterCpUrlRules;
use CodeZone\socialite\listeners\RegisterDrivers;
use CodeZone\socialite\listeners\RegisterUrlRules;
use CodeZone\socialite\listeners\RegisterVariable;
use CodeZone\socialite\services\DriversService;
use CodeZone\socialite\services\ProvidersService as SocialiteServiceService;
use CodeZone\socialite\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

/**
 * Class Socialite
 *
 * @author    CodeZone
 * @package   Socialite
 * @since     0.0.0
 *
 * @property  SocialiteServiceService $socialiteService
 */
class Socialite extends Plugin
{
    // Properties
    // =========================================================================

    // Static Properties
    // =========================================================================

    /**
     * @var Socialite
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '0.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        RegisterUrlRules::listen(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES
        );

        RegisterVariable::listen(
            CraftVariable::class,
            CraftVariable::EVENT_INIT
        );

        RegisterDrivers::listen(
            Plugins::class,
            Plugins::EVENT_AFTER_LOAD_PLUGINS
        );

        $this->setComponents([
            'drivers' => DriversService::class
        ]);

        Craft::info(
            Craft::t(
                'socialite',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }
}
