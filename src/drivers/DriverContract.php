<?php

namespace CodeZone\socialite\drivers;


use craft\web\Request;
use League\OAuth2\Client\Provider\AbstractProvider;

interface DriverContract
{
    public static function slug(): string;
    public static function label(): string;
    public static function isConfigured(): bool;
    public function getProvider(): AbstractProvider;
    public function handleRequest(Request $request);
    public function handleConnect(Request $request);
    public function handleCallback(Request $request);
    public function getConfig();
}