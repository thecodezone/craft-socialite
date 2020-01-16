<?php


namespace CodeZone\socialite\drivers;


use League\OAuth2\Client\Provider\AbstractProvider;
use TheNetworg\OAuth2\Client\Provider\Azure;

class AzureDriver extends Driver
{
    /**
     * Instantiate the provider.
     * @param array $config
     * @return AbstractProvider
     */
    protected function provider(array $config): AbstractProvider
    {
        return new Azure($config);
    }

    /**
     * Return an array that maps user field keys to provider field keys.
     * @return array
     */
    protected function userFieldMap(): array
    {
        return [
            'firstName' => 'given_name',
            'lastName' => 'family_name',
            'email' => 'upn'
        ];
    }
}