<?php


namespace CodeZone\socialite\listeners;


use CodeZone\socialite\drivers\AzureDriver;
use CodeZone\socialite\drivers\GenericDriver;
use CodeZone\socialite\Socialite;
use yii\base\Event;

class RegisterDrivers extends Listener
{

    function handle(Event $event)
    {
        Socialite::$plugin->drivers->register(GenericDriver::class);
        Socialite::$plugin->drivers->register(AzureDriver::class);
    }
}