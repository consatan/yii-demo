<?php

use yii\helpers\Url;

class SupplierFormCest
{
    public function _before(\FunctionalTester $I)
    {
        $I->amOnRoute('site/login');
        $I->submitForm('#login-form', [
            'LoginForm[username]' => 'admin',
            'LoginForm[password]' => 'admin',
        ]);
    }

    public function openSupplierPage(\FunctionalTester $I)
    {
        $I->amOnRoute('supplier/index');

        $I->expectTo('supplier title');
        $I->see('Supplier', 'h1');
        $I->see('Supplier', '.breadcrumb-item.active');

        $I->expectTo('create and export button');
        $I->seeLink('Create Supplier', Url::toRoute('/supplier/create'));
        $I->seeElement('#column-select-btn:disabled');

        $I->expectTo('items summary');
        $I->see('Showing 1-20 of', '.summary');

        $I->expectTo('sort by field link');
        $I->seeLink('ID');
        $I->seeLink('Name');
        $I->seeLink('Code');
        $I->seeLink('Status');

        $I->expectTo('pagination block');
        $I->seeElement('ul.pagination');
        $I->seeLink('10', Url::toRoute(['/supplier/index', 'page' => 10]));
        $I->seeLink('>', Url::toRoute(['/supplier/index', 'page' => 2]));
        $I->seeLink('>>', Url::toRoute(['/supplier/index', 'page' => 250]));
    }

    public function createSupplierWithIncorrect(\FunctionalTester $I)
    {
        $I->amOnRoute('supplier/create');
        $I->expectTo('see breadcrumb');
        $I->see('Create Supplier', '.breadcrumb-item.active');

        $I->submitForm('#supplier-form', []);
        $I->expectTo('see name error');
        $I->see('Name cannot be blank.', '.help-block');

        $I->submitForm('#supplier-form', [
            'Supplier[name]' => 'abc',
            'Supplier[code]' => 'etc',
        ]);

        $I->expectTo('see code error');
        $I->see('Code "etc" has already been taken.', '.help-block');

        $I->submitForm('#supplier-form', [
            'Supplier[name]' => 'abc',
            'Supplier[code]' => '',
            'Supplier[t_status]' => 'out of range',
        ]);

        $I->expectTo('see stats error');
        $I->see('Status is invalid.');

        $I->submitForm('#supplier-form', [
            'Supplier[name]' => 'abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz0123456789',
            'Supplier[code]' => '',
        ]);

        $I->expectTo('see name out of length');
        $I->see('Name should contain at most 50 characters.', '.help-block');

        $I->submitForm('#supplier-form', [
            'Supplier[name]' => 'abc',
            'Supplier[code]' => 'abcd',
        ]);

        $I->expectTo('see code out of length');
        $I->see('Code should contain at most 3 characters.', '.help-block');
    }

    public function createSupplierSuccessfully(\FunctionalTester $I)
    {
        $I->amOnRoute('supplier/create');
        $I->submitForm('#supplier-form', [
            'Supplier[name]' => 'tester',
            'Supplier[code]' => '',
            'Supplier[t_status]' => 'ok',
        ]);
        $I->dontSeeElement('#supplier-form');
        $I->expectTo('redirect to supplier index');
        $I->seeCurrentUrlEquals(Url::toRoute('supplier/index'));
    }

    public function incorrectExportParam(AcceptanceTester $I)
    {
        $I->amGoingTo('invalid export url');
        $I->amOnPage(Url::to(['/supplier/export', 'SupplierSearch[export_ids]' => 'abc']));
        $I->see('Bad Request');
        $I->see('Export Ids is invalid.');
    }
}
