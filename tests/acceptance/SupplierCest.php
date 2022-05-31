<?php

use yii\helpers\Url;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Exception\ElementNotInteractableException;

class SupplierCest
{
    protected $browserDownloadDir = '/tmp';

    protected $filePattern = 'suppliers_*.csv';

    const SESSION_NAME = 'supplierAcceptanceSession';

    public function _before(\AcceptanceTester $I)
    {
        if (!$I->loadSessionSnapshot(self::SESSION_NAME)) {
            $I->amOnPage(Url::toRoute('/site/login'));
            $I->fillField(WebDriverBy::name('LoginForm[username]'), 'admin');
            $I->fillField(WebDriverBy::name('LoginForm[password]'), 'admin');
            $I->click(WebDriverBy::name('login-button'));
            $I->seeElement(WebDriverBy::xpath("//button[@type='submit'][contains(text(), 'Logout')]"));

            $I->saveSessionSnapshot(self::SESSION_NAME);
        }

        $I->amOnPage(Url::toRoute('/supplier/index'));
    }

    protected function waitForSummaryChannge(AcceptanceTester $I, ?string $currentText)
    {
        $currentText = $currentText ?: $I->grabTextFrom(WebDriverBy::className('summary'));
        $I->waitForJs("return typeof jQuery !== 'undefined' && $('.summary').text() !== '{$currentText}'", 10);
    }

    protected function waitForFileExists(int $wait = 10): array
    {
        $retry = $wait;
        $pattern = $this->browserDownloadDir . '/' . $this->filePattern;

        do {
            $files = glob($pattern);
            if ($files) {
                for ($i = sizeof($files) - 1; $i >= 0; $i--) {
                    // wait for file write
                    if (stat($files[$i])['size'] > 0) {
                        return $files;
                    }
                }
            }

            sleep(1);
        } while (--$retry);

        throw new TimeoutException("Waited for {$wait} secs but path {$pattern} still not exists");
    }

    protected function cleanDir()
    {
        foreach (glob($this->browserDownloadDir . '/' . $this->filePattern) as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    protected static function xpathContainsIgnoreCase(string $path, string $text): WebDriverBy
    {
        return WebDriverBy::xpath(sprintf(
            '%s[contains(translate(text(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz"), "%s")]',
            $path,
            $text
        ));
    }

    public function supplierPageWorks(AcceptanceTester $I)
    {
        $I->wantTo('ensure that supplier page works');
        $I->see('Supplier', ['xpath' => '//h1']);
        $I->see('Supplier', ['xpath' => '//li[contains(@class, "breadcrumb-item") and contains(@class, "active")]']);

        $I->expectTo('create and export button');
        $I->seeLink('Create Supplier', Url::toRoute('/supplier/create'));
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and @disabled]'));

        $I->expectTo('items summary');
        $I->see('Showing 1-20 of', ['class' => 'summary']);

        $I->expectTo('sort by field link');
        $I->seeLink('ID');
        $I->seeLink('Name');
        $I->seeLink('Code');
        $I->seeLink('Status');

        $I->expectTo('pagination block');
        $I->seeElement(WebDriverBy::xpath('//ul[@class="pagination"]'));
        $I->seeLink('10', Url::toRoute(['/supplier/index', 'page' => 10]));
        $I->seeLink('>', Url::toRoute(['/supplier/index', 'page' => 2]));
        $I->seeLink('>>', Url::toRoute(['/supplier/index', 'page' => 250]));

        $I->expectTo('click page 2');
        $I->click('2');
        $I->waitForText('Showing 21-40 of', 10, WebDriverBy::className('summary'));
    }

    public function searchByIdEquals(AcceptanceTester $I)
    {
        $summaryText = $I->grabTextFrom(WebDriverBy::className('summary'));
        $I->wantTo('filter by id=20');
        $I->pressKey(WebDriverBy::xpath('//input[@type="text" and @name="SupplierSearch[id]"]'), '20', WebDriverKeys::ENTER);
        $this->waitForSummaryChannge($I, $summaryText);
        $I->see('Showing 1-1 of 1 item.', ['class' => 'summary']);
        $I->dontSeeElement(WebDriverBy::xpath('//ul[@class="pagination"]'));
    }

    public function searchByIdGreaterThan(AcceptanceTester $I)
    {
        $summaryText = $I->grabTextFrom(WebDriverBy::className('summary'));
        $I->wantTo('filter by id>=20');
        $I->pressKey(WebDriverBy::xpath('//input[@type="text" and @name="SupplierSearch[id]"]'), '>=20', WebDriverKeys::ENTER);
        $this->waitForSummaryChannge($I, $summaryText);
        $I->see('Showing 1-20 of', ['class' => 'summary']);
        $I->seeElement(WebDriverBy::xpath('//ul[@class="pagination"]'));
    }

    public function searchByIdLessThan(AcceptanceTester $I)
    {
        $summaryText = $I->grabTextFrom(WebDriverBy::className('summary'));
        $I->wantTo('filter by id<=10');
        $I->pressKey(WebDriverBy::xpath('//input[@type="text" and @name="SupplierSearch[id]"]'), '<=10', WebDriverKeys::ENTER);
        $this->waitForSummaryChannge($I, $summaryText);
        $I->see('Showing 1-10 of 10 items.', ['class' => 'summary']);
        $I->dontSeeElement(WebDriverBy::xpath('//ul[@class="pagination"]'));
    }

    public function searchByName(AcceptanceTester $I)
    {
        $I->wantTo('grab current .summary text');
        $summaryText = $I->grabTextFrom(WebDriverBy::className('summary'));
        $selector = WebDriverBy::xpath('//input[@type="text" and @name="SupplierSearch[name]"]');

        $I->wantTo('filter by name like %ben%');
        $I->pressKey($selector, 'ben', WebDriverKeys::ENTER);
        $this->waitForSummaryChannge($I, $summaryText);
        $I->seeElement(self::xpathContainsIgnoreCase('//tbody[1]/tr[1]/td[3]', 'ben'));
    }

    public function searchByCode(AcceptanceTester $I)
    {
        $I->wantTo('filter by name like %be%');
        $summaryText = $I->grabTextFrom(WebDriverBy::className('summary'));
        $selector = WebDriverBy::xpath('//input[@type="text" and @name="SupplierSearch[code]"]');

        $I->pressKey($selector, 'be', WebDriverKeys::ENTER);
        $this->waitForSummaryChannge($I, $summaryText);
        $I->seeElement(self::xpathContainsIgnoreCase('//tbody[1]/tr[1]/td[4]', 'be'));
    }

    public function searchByStatus(AcceptanceTester $I)
    {
        $summaryText = $I->grabTextFrom(WebDriverBy::className('summary'));
        $I->seeElement(WebDriverBy::xpath('//option[@value="" and text()="All"]'));

        $I->wantTo('filter by OK status');
        $selector = WebDriverBy::xpath('//select[@name="SupplierSearch[t_status]"]');
        $I->selectOption($selector, 'OK');
        $this->waitForSummaryChannge($I, $summaryText);
        $I->seeElement(WebDriverBy::xpath('//tbody[1]/tr[1]/td[5][text()="OK"]'));
        $I->dontSeeElement(WebDriverBy::xpath('//tbody/tr/td[5][text()!="OK"]'));

        $I->wantTo('filter by All status');
        $summaryText = $I->grabTextFrom(WebDriverBy::className('summary'));

        $I->selectOption($selector, '');
        $this->waitForSummaryChannge($I, $summaryText);
        $I->seeElement(WebDriverBy::xpath('//tbody[1]/tr/td[5][text()="OK"]'));
        $I->seeElement(WebDriverBy::xpath('//tbody[1]/tr/td[5][text()="Hold"]'));
    }

    public function searchByMultipleFilter(AcceptanceTester $I)
    {
        $I->wantTo('filter by id>=20');
        $summaryText = $I->grabTextFrom(WebDriverBy::className('summary'));
        $I->pressKey(WebDriverBy::xpath('//input[@type="text" and @name="SupplierSearch[id]"]'), '>=20', WebDriverKeys::ENTER);
        $this->waitForSummaryChannge($I, $summaryText);
        $I->seeElement(WebDriverBy::xpath('//tbody[1]/tr[1]/td[2][text()>=20]'));

        $I->wantTo('filter by name like %ben%');
        $summaryText = $I->grabTextFrom(WebDriverBy::className('summary'));
        $I->pressKey(WebDriverBy::xpath('//input[@type="text" and @name="SupplierSearch[name]"]'), 'ben', WebDriverKeys::ENTER);
        $this->waitForSummaryChannge($I, $summaryText);
        $I->seeElement(WebDriverBy::xpath('//tbody[1]/tr[1]/td[2][text()>=20]'));
        $I->seeElement(self::xpathContainsIgnoreCase('//tbody[1]/tr[1]/td[3]', 'ben'));

        $I->wantTo('filter by OK status');
        $summaryText = $I->grabTextFrom(WebDriverBy::className('summary'));
        $selector = WebDriverBy::xpath('//select[@name="SupplierSearch[t_status]"]');
        $I->selectOption(WebDriverBy::xpath('//select[@name="SupplierSearch[t_status]"]'), 'OK');
        $this->waitForSummaryChannge($I, $summaryText);
        $I->seeElement(WebDriverBy::xpath('//tbody[1]/tr[1]/td[2][text()>=20]'));
        $I->seeElement(self::xpathContainsIgnoreCase('//tbody[1]/tr[1]/td[3]', 'ben'));
        $I->seeElement(WebDriverBy::xpath('//tbody[1]/tr[1]/td[5][text()="OK"]'));
        $I->dontSeeElement(WebDriverBy::xpath('//tbody/tr/td[5][text()!="OK"]'));
    }

    public function checkFirstCheckbox(AcceptanceTester $I)
    {
        $I->wantTo('click first row checkbox');
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and @disabled]'));
        $I->checkOption(WebDriverBy::xpath('(//input[@name="selection[]"])[1]'));
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and not(@disabled)]'));

        $I->wantTo('uncheck first row checkbox');
        $I->uncheckOption(WebDriverBy::xpath('(//input[@name="selection[]"])[1]'));
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and @disabled]'));
    }

    public function selectAllTip(AcceptanceTester $I)
    {
        $I->wantTo('select all');
        $I->checkOption(WebDriverBy::className('select-on-check-all'));
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and not(@disabled)]'));
        $I->see('Select all suppliers that match this search', ['id' => 'export-tip']);

        $I->wantTo('select all match this search');
        $I->click(WebDriverBy::xpath('//span[@id="export-tip"]//a'));
        $I->see('Clear selection', ['id' => 'export-tip']);

        $I->wantTo('clear selection');
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and not(@disabled)]'));
        $I->seeCheckboxIsChecked(WebDriverBy::className('select-on-check-all'));
        $I->seeCheckboxIsChecked(WebDriverBy::name('selection[]'));
        $I->click(WebDriverBy::xpath('//span[@id="export-tip"]//a'));

        $I->expectTo('export button disabled, all checkbox uncheck');
        $I->dontSeeCheckboxIsChecked(WebDriverBy::className('select-on-check-all'));
        $I->dontSeeCheckboxIsChecked(WebDriverBy::name('selection[]'));
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and @disabled]'));
    }

    public function checkAllCheckBoxWithoutUseCheckAll(AcceptanceTester $I)
    {
        $I->wantTo('check all checkbox without use check-all checkbox');
        for ($i = 1; $i <= 20; $i++) {
            $I->checkOption(WebDriverBy::xpath("(//input[@name='selection[]'])[$i]"));
        }

        $I->expectTo('check-all checkbox checked');
        $I->seeCheckboxIsChecked(WebDriverBy::name('selection[]'));
        $I->seeCheckboxIsChecked(WebDriverBy::className('select-on-check-all'));
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and not(@disabled)]'));
        $I->see('Select all suppliers that match this search', ['id' => 'export-tip']);
    }

    public function clickExportButtonToSelectExportColumns(AcceptanceTester $I)
    {
        $I->wantTo('click export button');
        $I->dontSeeElement(WebDriverBy::id('export-modal'));
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and @disabled]'));
        $I->checkOption(WebDriverBy::xpath('//tbody[1]/tr[1]/td[1]/input[@type="checkbox"]'));
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and not(@disabled)]'));
        $I->waitForElementClickable(WebDriverBy::id('column-select-btn'));
        $I->click(WebDriverBy::id('column-select-btn'));

        $I->expectTo('columns select modal showed');
        $I->see('Select export columns', ['xpath' => '//h5']);
        $I->seeElement(WebDriverBy::id('export-modal'));
        $I->seeCheckboxIsChecked(WebDriverBy::id('column-id'));
        $I->seeCheckboxIsChecked(WebDriverBy::id('column-name'));
        $I->seeCheckboxIsChecked(WebDriverBy::id('column-code'));
        $I->seeCheckboxIsChecked(WebDriverBy::id('column-t_status'));

        $I->uncheckOption(WebDriverBy::id('column-name'));
        $I->dontSeeCheckboxIsChecked(WebDriverBy::id('column-name'));

        $I->wantTo('close modal');
        $I->click(WebDriverBy::xpath('//div[@id="export-modal"]//button[@class="close"]'));
        $I->dontSee('Select export columns', ['xpath' => '//h5']);
        $I->dontSeeElement(WebDriverBy::id('export-modal'));
        $I->wait(1);

        $I->wantTo('reopen modal');
        $I->waitForElementClickable(WebDriverBy::id('column-select-btn'));
        $I->click(WebDriverBy::id('column-select-btn'));

        $I->expectTo('all checkbox checked when modal reopen');
        $I->see('Select export columns', ['xpath' => '//h5']);
        $I->seeElement(WebDriverBy::id('export-modal'));
        $I->seeCheckboxIsChecked(WebDriverBy::id('column-id'));
        $I->seeCheckboxIsChecked(WebDriverBy::id('column-name'));
        $I->seeCheckboxIsChecked(WebDriverBy::id('column-code'));
        $I->seeCheckboxIsChecked(WebDriverBy::id('column-t_status'));
    }

    public function exportOneRecordToCsv(AcceptanceTester $I)
    {
        $this->cleanDir();

        $I->wantTo('export 1 row to csv');
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and @disabled]'));
        $I->checkOption(WebDriverBy::xpath('//tbody[1]/tr[1]/td[1]/input[@type="checkbox"]'));
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and not(@disabled)]'));
        $I->waitForElementClickable(WebDriverBy::id('column-select-btn'));
        $I->click(WebDriverBy::id('column-select-btn'));

        $I->waitForElementClickable(WebDriverBy::id('export-btn'));
        $I->click(WebDriverBy::id('export-btn'));

        $I->expectTo('csv file');
        $files = $this->waitForFileExists();
        $I->openFile(array_pop($files));
        $I->seeInThisFile("ID,Name,Code,Status\n");
        $I->seeNumberNewLines(3);
        $I->deleteThisFile();
    }

    public function exportCurrentPageAllRecordsWithoutCodeColumnToCsv(AcceptanceTester $I)
    {
        $this->cleanDir();

        $I->wantTo('select all records in current page');
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and @disabled]'));
        $I->checkOption(WebDriverBy::className('select-on-check-all'));
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and not(@disabled)]'));

        $I->wantTo('uncheck code column in export');
        $I->waitForElementClickable(WebDriverBy::id('column-select-btn'));
        $I->click(WebDriverBy::id('column-select-btn'));
        $I->see('Select export columns', ['xpath' => '//h5']);
        $I->seeElement(WebDriverBy::id('export-modal'));
        $I->uncheckOption(WebDriverBy::id('column-code'));
        $I->waitForElementClickable(WebDriverBy::id('export-btn'));
        $I->click(WebDriverBy::id('export-btn'));

        $files = $this->waitForFileExists();
        $I->openFile(array_pop($files));
        $I->seeInThisFile("ID,Name,Status\n");
        $I->seeNumberNewLines(22);
        $I->deleteThisFile();
    }

    public function exportAllRecordsWithFilterToCsv(AcceptanceTester $I)
    {
        $this->cleanDir();

        $I->wantTo('filter by id <= 30');
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and @disabled]'));

        $summaryText = $I->grabTextFrom(WebDriverBy::className('summary'));
        $I->pressKey(WebDriverBy::xpath('//input[@type="text" and @name="SupplierSearch[id]"]'), '<=30', WebDriverKeys::ENTER);
        $this->waitForSummaryChannge($I, $summaryText);
        $I->seeElement(WebDriverBy::xpath('//ul[@class="pagination"]'));
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and @disabled]'));

        $I->wantTo('select all records');
        $I->checkOption(WebDriverBy::className('select-on-check-all'));
        $I->seeElement(WebDriverBy::xpath('//button[@id="column-select-btn" and not(@disabled)]'));
        $I->see('Select all suppliers that match this search', ['id' => 'export-tip']);
        $I->click(WebDriverBy::xpath('//span[@id="export-tip"]//a'));

        $I->waitForElementClickable(WebDriverBy::id('column-select-btn'));
        $I->click(WebDriverBy::id('column-select-btn'));
        $I->see('Select export columns', ['xpath' => '//h5']);
        $I->seeElement(WebDriverBy::id('export-modal'));
        $I->waitForElementClickable(WebDriverBy::id('export-btn'));
        $I->click(WebDriverBy::id('export-btn'));

        $files = $this->waitForFileExists();
        $I->openFile(array_pop($files));
        $I->seeInThisFile("ID,Name,Code,Status\n");
        $I->seeNumberNewLines(32);
        $I->deleteThisFile();
    }
}
