<?php
namespace CodeZone\socialite\tests;

use FunctionalTester;

class AzureAuthTest {
    public function testItRedirects(FunctionalTester $I)
    {
        $I->amOnPage('/socialite/azure/auth');
        $I->canSeeResponseCodeIsRedirection();
    }
}