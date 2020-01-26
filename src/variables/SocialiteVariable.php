<?php
/**
 * socialite plugin for Craft CMS 3.x
 *
 * Login to Craft with third-party services like Azure and Google. 
 *
 * @link      https://codezone.io
 * @copyright Copyright (c) 2020 CodeZone
 */

namespace CodeZone\socialite\variables;

use CodeZone\socialite\Socialite;

use Craft;

/**
 * @author    CodeZone
 * @package   Socialite
 * @since     0.0.0
 */
class SocialiteVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param null $optional
     * @return string
     */
    public function url($provider)
    {
        $driver = Socialite::$plugin->drivers->find($provider);
        return $driver->getUrl();
    }

    public function get($provider, $endpoint)
    {
        $token = Socialite::$plugin->user->token($provider);

        if (!$token) {
            return false;
        }

        $driver = Socialite::$plugin->drivers->find($provider);

        return $driver->getProvider()->get($endpoint, $token);
    }
}
