<?php

namespace CodeZone\socialite\drivers;


use craft\web\Request;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;

interface DriverContract
{
    public static function slug(): string;
    public static function label(): string;
    public static function isConfigured(): bool;
    public function getProvider(): AbstractProvider;
    public function handleConnect(Request $request);
    public function handleCallback(Request $request);
    public function getConfig();
    public function getUserFieldMap(): array;
    public function getOwner(AccessToken $accessToken): ResourceOwnerInterface;
}