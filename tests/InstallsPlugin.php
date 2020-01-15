<?php

namespace CodeZone\Tests;

trait InstallsPlugin
{
    protected function _setUp()
    {
        \Craft::$app->getPlugins()->installPlugin('socialite');
        return parent::_setUp();
    }
}