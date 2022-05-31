<?php

use yii\helpers\Url;

class LoginCest
{
    public function _before(\AcceptanceTester $I)
    {
        $I->amOnPage(Url::toRoute('/site/login'));
        $I->see('Login', 'h1');

        $I->amGoingTo('try to login with correct credentials');
        $I->fillField('input[name="LoginForm[username]"]', 'admin');
        $I->fillField('input[name="LoginForm[password]"]', 'admin');
        $I->click('login-button');
        $I->seeElement(['xpath' => "//button[@type='submit'][contains(text(), 'Logout')]"]);
    }

    public function ensureThatLoginWorks(AcceptanceTester $I)
    {
        $I->expectTo('see user info');
        $I->see('Logout');
    }

    public function ensureThatLoggedInWorks(AcceptanceTester $I)
    {
        $I->amOnPage(Url::toRoute('/site/login'));
        $I->expectTo('redirect to home page');
        $I->seeElement(['xpath' => "//button[@type='submit'][contains(text(), 'Logout')]"]);
        $I->dontSee('Login', 'h1');
        $I->seeInCurrentUrl(Url::to('/'));
    }

    public function ensureThatLogoutWorks(AcceptanceTester $I)
    {
        $I->click('.logout');

        $I->expectTo('redirect to home page');
        $I->seeLink('Login');
        $I->seeInCurrentUrl(Url::to('/'));
    }
}
