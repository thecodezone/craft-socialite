<?php


namespace CodeZone\socialite\drivers;


use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;

class GenericDriver extends Driver
{
    /**
     * Instantiate the provider.
     * @param array $config
     * @return AbstractProvider
     */
    protected function provider(array $config): AbstractProvider
    {
        return new GenericProvider($config);
    }
}