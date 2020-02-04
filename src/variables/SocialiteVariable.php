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
    {;
        return $this->driver($provider)->getUrl();
    }

    public function provider($slug)
    {
        $this->driver($slug)->getProvider();
    }

    public function driver($slug)
    {
        return Socialite::$plugin->drivers->find($slug);
    }
}
