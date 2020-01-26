<?php
/**
 * socialite plugin for Craft CMS 3.x
 *
 * Login to Craft with third-party services like Azure and Google. 
 *
 * @link      https://codezone.io
 * @copyright Copyright (c) 2020 CodeZone
 */

namespace CodeZone\socialite\services;

use CodeZone\socialite\drivers\DriverContract;
use CodeZone\socialite\Exception\OAuthException;
use craft\base\Component;

/**
 * @author    CodeZone
 * @package   Socialite
 * @since     0.0.0
 */
class DriversService extends Component
{
    // Private properties
    // =========================================================================

    private $_drivers = [];

    // Public Methods
    // =========================================================================

    /*
     * Register a driver
     * @return mixed
     */
    public function register($driver)
    {
        if ($driver::isConfigured()) {
            $driver = new $driver;
            if (!$driver instanceof DriverContract) {
                throw new OAuthException($driver . ' must implement ' . DriverContract::class);
            }
            $this->_drivers[$driver::slug()] = $driver;
        }
    }

    /**
     * Find an active driver
     */
    public function find($slug) {
        $driver = isset($this->_drivers[$slug]) ? $this->_drivers[$slug] : null;
        if($driver) {
            return $driver;
        };
        return null;
    }
}
