<?php

namespace CodeZone\socialite\tests;


use Codeception\Test\Unit;
use CodeZone\Socialite\Tests\TestCase;
use CodeZone\socialite\Socialite;
use UnitTester;

class SettingsTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testItHasSettings()
    {
        $settings = \Craft::$app->getConfig()->getConfigFromFile('socialite');
        $this->assertEquals($settings, Socialite::getInstance()->getSettings()->toArray());
    }
}