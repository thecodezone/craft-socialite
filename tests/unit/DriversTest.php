<?php


namespace unit;


use Codeception\Test\Unit;
use CodeZone\socialite\drivers\AzureDriver;
use CodeZone\socialite\drivers\GenericDriver;
use CodeZone\socialite\Socialite;

class DriversTest extends Unit
{
    public function testItCanFindDriver()
    {
        $this->assertNull(Socialite::$plugin->drivers->find('missing'));
        $this->assertInstanceOf(GenericDriver::class, Socialite::$plugin->drivers->find('generic'));
        $this->assertInstanceOf(AzureDriver::class, Socialite::$plugin->drivers->find('azure'));
    }
}