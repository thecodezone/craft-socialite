<?php

namespace CodeZone\socialite\tests;

use Codeception\Test\Unit;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use CodeZone\socialite\drivers\GenericDriver;
use CodeZone\socialite\Socialite;
use UnitTester;
use Craft;

class GenericDriverTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testItCanGetProvider()
    {
        $driver = new GenericDriver();
        $this->assertInstanceOf(GenericProvider::class, $driver->getProvider([
            'urlAuthorize' => 'http://www.google.com/'
        ]));
    }

    public function testItCanGetInfo()
    {
        $driver = new GenericDriver();
        $this->assertEquals('Generic', $driver::label());
        $this->assertEquals('generic', $driver::slug());
    }

    public function testItHandlesConnects()
    {
        $driver = new GenericDriver();
        $driver->handleConnect(Craft::$app->getRequest());
        $this->assertTrue(Craft::$app->getResponse()->getIsRedirection());
        $this->assertEquals($driver->getProvider()->getState(), \Craft::$app->getSession()->get($driver::SESSION_OAUTH_STATE));
    }

    public function testItHandlesCallbacks()
    {
        $code = 'test';
        $token = 'token';
        $accessToken = new AccessToken([
            'access_token' => $token
         ]);;

        $provider = $this->make(GenericProvider::class, [
            'getAccessToken' => \Codeception\Stub\Expected::once(function($grant, $options) use ($code, $accessToken) {
                $this->assertEquals($code, $options['code']);
                return $accessToken;
            })
        ]);
        $driver = new GenericDriver($provider);
        $request = Craft::$app->getRequest();
        $request->setQueryParams([
            'code' => 'test'
        ]);
        $this->assertEquals($accessToken, $driver->handleCallback($request));
        $this->assertEquals($token, $driver->getToken());
    }
}