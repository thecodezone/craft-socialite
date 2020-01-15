<?php


namespace CodeZone\socialite\drivers;


use League\OAuth2\Client\Provider\AbstractProvider;
use TheNetworg\OAuth2\Client\Provider\Azure;

class AzureDriver extends Driver
{

    protected function provider(): AbstractProvider
    {
        return new Azure($this->getConfig());
    }
}