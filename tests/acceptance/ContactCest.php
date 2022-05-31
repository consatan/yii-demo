<?php

use yii\helpers\Url;
use \Facebook\WebDriver\WebDriverBy;

class ContactCest
{
    // @var string
    const SESSION_NAME = 'contactAcceptanceSeesion';

    public function _before(\AcceptanceTester $I)
    {
        if (!$I->loadSessionSnapshot(self::SESSION_NAME)) {
            $I->amOnPage(Url::toRoute('/site/login'));
            $I->fillField(WebDriverBy::name('LoginForm[username]'), 'admin');
            $I->fillField(WebDriverBy::name('LoginForm[password]'), 'admin');
            $I->click(WebDriverBy::name('login-button'));
            $I->seeElement(WebDriverBy::xpath("//button[@type='submit'][contains(text(), 'Logout')]"));

            $I->loadSessionSnapshot(self::SESSION_NAME);
        }

        $I->amOnPage(Url::toRoute('/site/contact'));
    }

    public function contactPageWorks(AcceptanceTester $I)
    {
        $I->wantTo('ensure that contact page works');
        $I->see('Contact', ['xpath' => '//h1']);
    }

    public function contactFormCanBeSubmitted(AcceptanceTester $I)
    {
        $I->amGoingTo('submit contact form with correct data');
        $I->fillField(WebDriverBy::id('contactform-name'), 'tester');
        $I->fillField(WebDriverBy::id('contactform-email'), 'tester@example.com');
        $I->fillField(WebDriverBy::id('contactform-subject'), 'test subject');
        $I->fillField(WebDriverBy::id('contactform-body'), 'test content');
        $I->fillField(WebDriverBy::id('contactform-verifycode'), 'testme');

        $I->click(WebDriverBy::name('contact-button'));

        $I->expectTo('submit contact success');
        $I->see('Thank you for contacting us. We will respond to you as soon as possible.', ['class' => 'alert-success']);
        $I->dontSeeElement(WebDriverBy::id('contact-form'));
    }
}
