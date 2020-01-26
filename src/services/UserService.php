<?php


namespace CodeZone\socialite\services;

use CodeZone\socialite\Socialite;
use craft\base\Component;

class UserService extends Component
{
    public function token($provider)
    {
        $driver = Socialite::$plugin->drivers->find($provider);
        return $driver->getAccessToken();
    }
}