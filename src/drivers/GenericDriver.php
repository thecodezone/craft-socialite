<?php


namespace CodeZone\socialite\drivers;


use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;

class GenericDriver extends Driver
{
    protected function provider(): AbstractProvider
    {
        return new GenericProvider(
            $this->getConfig()
        );
    }
}