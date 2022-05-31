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
    // @var string
    const SESSION_NAME = 'supplierAcceptanceSession';

    // @var string
    protected $browserDownloadDir = '/tmp';

    // @var string
    protected $filePattern = 'suppliers_*.csv';

    // @var array
    protected $idInput = ['xpath' => '//input[@type="text" and @name="SupplierForm[id]"]'];

    // @var array
    protected $nameInput = ['xpath' => '//input[@type="text" and @name="SupplierForm[name]"]'];

    // @var array
    protected $codeInput = ['xpath' => '//input[@type="text" and @name="SupplierForm[code]"]'];

    // @var array
    protected $statusSelect = ['xpath' => '//select[@name="SupplierForm[t_status]"]'];

    // @var array
    protected $columnSelectionButton = ['id' => 'column-select-btn'];

    // @var array
    protected $columnSelectionButtonEnable = ['xpath' => '//button[@id="column-select-btn" and not(@disabled)]'];

    // @var array
    protected $columnSelectionButtonDisabled = ['xpath' => '//button[@id="column-select-btn" and @disabled]'];

    // @var array
    protected $paginationBlock = ['xpath' => '//ul[@class="pagination"]'];

    // @var array
    protected $summaryBlock = ['class' => 'summary'];

    // @var array
    protected $exportTip = ['id' => 'export-tip'];

    // @var array
    protected $exportTipLink = ['xpath' => '//span[@id="export-tip"]//a'];

    // @var array
    protected $exportModal = ['id' => 'export-modal'];

    // @var array
    protected $exportButton = ['id' => 'export-btn'];

    // @var array
    protected $firstRowCheckbox = ['xpath' => '//tbody[1]/tr[1]/td[1]/input[@type="checkbox"]'];

    // @var array
    protected $selectAllCheckbox = ['class' => 'select-on-check-all'];

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

    /**
     * waiting for summary block text change
     *
     * @param \AcceptanceTester $I
     * @param ?string $currentText
     * @param ?string $expect
     * @param int $timeout
     *
     * @return void
     * @throws \Facebook\WebDriver\Exception\TimeoutException
     */
    protected function waitForSummaryChannge(
        \AcceptanceTester $I,
        ?string $currentText = null,
        ?string $expect = null,
        int $timeout = 10
    ) {
        $currentText = $currentText ?: $I->grabTextFrom($this->summaryBlock);
        $I->waitForJs("return typeof jQuery !== 'undefined' && $('.summary').text() !== '{$currentText}'", $timeout);

        if (null !== $expect) {
            $I->see($expect, $this->summaryBlock);
        }
    }

    /**
     * waiting for file exists
     *
     * @param int $timeout
     *
     * @return array file list by `glob` search
     * @throws \Facebook\WebDriver\Exception\TimeoutException
     */
    protected function waitForFileExists(int $timeout = 10): array
    {
        $retry = $timeout;
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

        throw new TimeoutException("Waited for {$timeout} secs but path {$pattern} still not exists");
    }

    /**
     * clean download dir
     *
     * @return void
     */
    protected function cleanDownloadDir()
    {
        foreach (glob($this->browserDownloadDir . '/' . $this->filePattern) as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * get string contains ignore case xpath expression
     *
     * @param string $path
     * @param string $text
     *
     * @return \Facebook\WebDriver\WebDriverBy
     */
    protected static function xpathContainsIgnoreCase(string $path, string $text): WebDriverBy
    {
        return WebDriverBy::xpath(sprintf(
            '%s[contains(translate(text(), "%s", "%s"), "%s")]',
            $path,
            "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ",
            "abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz",
            $text
        ));
    }

    public function supplierPageWorks(\AcceptanceTester $I)
    {
        $I->wantTo('ensure that supplier page works');

        $I->see('Supplier', ['xpath' => '//h1']);
        // current breadcrumb
        $I->see('Supplier', ['xpath' => '//li[contains(@class, "breadcrumb-item") and contains(@class, "active")]']);

        $I->expect('create and export button');
        $I->seeLink('Create Supplier', Url::toRoute('/supplier/create'));
        $I->seeElement($this->columnSelectionButtonDisabled);

        $I->expect('items summary');
        $I->see('Showing 1-20 of', $this->summaryBlock);

        $I->expect('sort by field link');
        $I->seeLink('ID');
        $I->seeLink('Name');
        $I->seeLink('Code');
        $I->seeLink('Status');

        $I->expect('pagination block');
        $I->seeElement($this->paginationBlock);
        $I->seeLink('10', Url::toRoute(['/supplier/index', 'page' => 10]));
        $I->seeLink('>', Url::toRoute(['/supplier/index', 'page' => 2]));
        $I->seeLink('>>', Url::toRoute(['/supplier/index', 'page' => 250]));

        $I->amGoingTo('click page 2');
        $I->click('2');
        $I->waitForText('Showing 21-40 of', 10, $this->summaryBlock);
    }

    public function searchByIdEquals(\AcceptanceTester $I)
    {
        $I->wantTo('filter by id=20');

        $summaryText = $I->grabTextFrom($this->summaryBlock);
        $I->pressKey($this->idInput, '20', WebDriverKeys::ENTER);
        $this->waitForSummaryChannge($I, $summaryText, 'Showing 1-1 of 1 item.');
        $I->dontSeeElement($this->paginationBlock);
    }

    public function searchByIdGreaterThan(\AcceptanceTester $I)
    {
        $I->wantTo('filter by id>20');

        $summaryText = $I->grabTextFrom($this->summaryBlock);
        $I->pressKey($this->idInput, '>20', WebDriverKeys::ENTER);
        $this->waitForSummaryChannge($I, $summaryText, 'Showing 1-20 of');
        $I->seeElement($this->paginationBlock);
    }

    public function searchByIdLessThanAndEquals(\AcceptanceTester $I)
    {
        $I->wantTo('filter by id<=10');

        $summaryText = $I->grabTextFrom($this->summaryBlock);
        $I->pressKey($this->idInput, '<=10', WebDriverKeys::ENTER);
        $this->waitForSummaryChannge($I, $summaryText, 'Showing 1-10 of 10 items.');
        $I->dontSeeElement($this->paginationBlock);
    }

    public function searchByName(\AcceptanceTester $I)
    {
        $I->wantTo('filter by name like %ben%');

        $summaryText = $I->grabTextFrom($this->summaryBlock);
        $I->pressKey($this->nameInput, 'ben', WebDriverKeys::ENTER);
        $this->waitForSummaryChannge($I, $summaryText);
        $I->seeElement(self::xpathContainsIgnoreCase('//tbody[1]/tr[1]/td[3]', 'ben'));
    }

    public function searchByCode(\AcceptanceTester $I)
    {
        $I->wantTo('filter by name like %be%');

        $summaryText = $I->grabTextFrom($this->summaryBlock);
        $I->pressKey($this->codeInput, 'be', WebDriverKeys::ENTER);
        $this->waitForSummaryChannge($I, $summaryText);
        $I->seeElement(self::xpathContainsIgnoreCase('//tbody[1]/tr[1]/td[4]', 'be'));
    }

    public function searchByStatus(\AcceptanceTester $I)
    {
        $I->wantToTest('filter by OK status then filter All status');

        $summaryText = $I->grabTextFrom($this->summaryBlock);
        $I->expect('dropdown default All status');
        $I->seeElement(WebDriverBy::xpath('//option[@value="" and text()="All"]'));

        $I->expectTo('filter by OK status');
        $I->selectOption($this->statusSelect, 'OK');
        $this->waitForSummaryChannge($I, $summaryText);

        $I->expect('dropdown status is OK now');
        $I->seeElement(WebDriverBy::xpath('//tbody[1]/tr[1]/td[5][text()="OK"]'));
        $I->expect('only ok status');
        $I->dontSeeElement(WebDriverBy::xpath('//tbody/tr/td[5][text()!="OK"]'));

        $I->expectTo('filter by All status');

        $summaryText = $I->grabTextFrom($this->summaryBlock);
        $I->selectOption($this->statusSelect, '');
        $this->waitForSummaryChannge($I, $summaryText);

        $I->expect('ok and hold status');
        $I->seeElement(WebDriverBy::xpath('//tbody[1]/tr/td[5][text()="OK"]'));
        $I->seeElement(WebDriverBy::xpath('//tbody[1]/tr/td[5][text()="Hold"]'));
    }

    public function searchByMultipleFilter(\AcceptanceTester $I)
    {
        $I->wantToTest('filter by id>=20 and name like %ben% and OK status');

        $idGe20 = WebDriverBy::xpath('//tbody[1]/tr[1]/td[2][text()>=20]');
        $nameLikeBen = self::xpathContainsIgnoreCase('//tbody[1]/tr[1]/td[3]', 'ben');

        $I->expectTo('filter by id>=20');
        $summaryText = $I->grabTextFrom($this->summaryBlock);
        $I->pressKey($this->idInput, '>=20', WebDriverKeys::ENTER);

        $this->waitForSummaryChannge($I, $summaryText);
        $I->seeElement($idGe20);

        $I->expectTo('filter by name like %ben%');
        $summaryText = $I->grabTextFrom($this->summaryBlock);
        $I->pressKey($this->nameInput, 'ben', WebDriverKeys::ENTER);

        $this->waitForSummaryChannge($I, $summaryText);
        $I->seeElement($idGe20);
        $I->seeElement($nameLikeBen);

        $I->expectTo('filter by OK status');
        $summaryText = $I->grabTextFrom($this->summaryBlock);
        $I->selectOption($this->statusSelect, 'OK');

        $this->waitForSummaryChannge($I, $summaryText);
        $I->seeElement($idGe20);
        $I->seeElement($nameLikeBen);

        $I->expect('only ok status');
        $I->seeElement(WebDriverBy::xpath('//tbody[1]/tr[1]/td[5][text()="OK"]'));
        $I->dontSeeElement(WebDriverBy::xpath('//tbody/tr/td[5][text()!="OK"]'));
    }

    public function checkFirstCheckbox(\AcceptanceTester $I)
    {
        $I->wantToTest('click 1st record then uncheck it');

        $firstCheckbox = WebDriverBy::xpath('(//input[@name="selection[]"])[1]');

        $I->seeElement($this->columnSelectionButtonDisabled);
        $I->checkOption($firstCheckbox);
        $I->seeElement($this->columnSelectionButtonEnable);

        $I->uncheckOption($firstCheckbox);
        $I->seeElement($this->columnSelectionButtonDisabled);
    }

    public function selectAllTip(\AcceptanceTester $I)
    {
        $I->wantToTest(
            'select all in current page then select all records whose match this search, last clear selection.'
        );

        $I->expectTo('select all in current page');
        $I->checkOption($this->selectAllCheckbox);
        $I->seeElement($this->columnSelectionButtonEnable);
        $I->see('Select all suppliers that match this search', $this->exportTip);

        $I->expectTo('select all match this search');
        $I->click($this->exportTipLink);
        $I->see('Clear selection', $this->exportTip);

        $checkbox = WebDriverBy::name('selection[]');
        $I->expectTo('clear selection');
        $I->seeElement($this->columnSelectionButtonEnable);
        $I->seeCheckboxIsChecked($this->selectAllCheckbox);
        $I->seeCheckboxIsChecked($checkbox);
        $I->click($this->exportTipLink);

        $I->expect('export button disabled, all checkbox uncheck');
        $I->dontSeeCheckboxIsChecked($this->selectAllCheckbox);
        $I->dontSeeCheckboxIsChecked($checkbox);
        $I->seeElement($this->columnSelectionButtonDisabled);
    }

    public function checkAllCheckBoxWithoutUseCheckAll(\AcceptanceTester $I)
    {
        $I->wantTo('check all checkbox without use check-all checkbox');

        for ($i = 1; $i <= 20; $i++) {
            $I->checkOption(WebDriverBy::xpath("(//input[@name='selection[]'])[$i]"));
        }

        $I->expect('check-all checkbox checked');
        $I->seeCheckboxIsChecked($this->selectAllCheckbox);
        $I->seeCheckboxIsChecked(WebDriverBy::name('selection[]'));
        $I->seeElement($this->columnSelectionButtonEnable);
        $I->see('Select all suppliers that match this search', $this->exportTip);
    }

    public function clickExportButtonToSelectExportColumns(\AcceptanceTester $I)
    {
        $I->wantToTest('export columns selection modal');

        $I->dontSeeElement($this->exportModal);
        $I->seeElement($this->columnSelectionButtonDisabled);

        $I->checkOption($this->firstRowCheckbox);
        $I->seeElement($this->columnSelectionButtonEnable);
        $I->waitForElementClickable($this->columnSelectionButton);
        $I->click($this->columnSelectionButton);

        $columnID = WebDriverBy::id('column-id');
        $columnName = WebDriverBy::id('column-name');
        $columnCode = WebDriverBy::id('column-code');
        $columnStatus = WebDriverBy::id('column-t_status');

        $I->expect('columns select modal showed');
        $I->see('Select export columns', ['xpath' => '//h5']);
        $I->seeElement($this->exportModal);
        $I->seeCheckboxIsChecked($columnID);
        $I->seeCheckboxIsChecked($columnName);
        $I->seeCheckboxIsChecked($columnCode);
        $I->seeCheckboxIsChecked($columnStatus);

        $I->uncheckOption($columnName);
        $I->dontSeeCheckboxIsChecked($columnName);

        $I->expectTo('close modal');
        // modal close button
        $I->click(WebDriverBy::xpath('//div[@id="export-modal"]//button[@class="close"]'));
        $I->dontSee('Select export columns', ['xpath' => '//h5']);
        $I->dontSeeElement($this->exportModal);
        $I->wait(1);

        $I->expectTo('reopen modal');
        $I->waitForElementClickable($this->columnSelectionButton);
        $I->click($this->columnSelectionButton);

        $I->expect('all checkbox checked when modal showed');
        $I->see('Select export columns', ['xpath' => '//h5']);
        $I->seeElement($this->exportModal);
        $I->seeCheckboxIsChecked($columnID);
        $I->seeCheckboxIsChecked($columnName);
        $I->seeCheckboxIsChecked($columnCode);
        $I->seeCheckboxIsChecked($columnStatus);
    }

    public function exportOneRecordToCsv(\AcceptanceTester $I)
    {
        $I->wantTo('export 1st record to csv');

        $this->cleanDownloadDir();

        $I->seeElement($this->columnSelectionButtonDisabled);
        $I->checkOption($this->firstRowCheckbox);
        $I->seeElement($this->columnSelectionButtonEnable);
        $I->waitForElementClickable($this->columnSelectionButton);
        $I->click($this->columnSelectionButton);

        $I->waitForElementClickable($this->exportButton);
        $I->click($this->exportButton);

        $I->expect('downloading csv file');
        $files = $this->waitForFileExists();
        $csvFile = array_pop($files);

        $I->openFile($csvFile);
        $I->seeInThisFile("ID,Name,Code,Status\n");
        $I->seeNumberNewLines(3);
        $I->deleteThisFile();
    }

    public function exportCurrentPageAllRecordsWithoutCodeColumnToCsv(\AcceptanceTester $I)
    {
        $I->wantToTest('export all records in current page without Code column');

        $this->cleanDownloadDir();

        $I->seeElement($this->columnSelectionButtonDisabled);
        $I->checkOption($this->selectAllCheckbox);
        $I->seeElement($this->columnSelectionButtonEnable);

        $I->expectTo('uncheck code column in modal');
        $I->waitForElementClickable($this->columnSelectionButton);
        $I->click($this->columnSelectionButton);

        $I->see('Select export columns', ['xpath' => '//h5']);
        $I->seeElement($this->exportModal);

        $I->uncheckOption(WebDriverBy::id('column-code'));
        $I->waitForElementClickable($this->exportButton);
        $I->click($this->exportButton);

        $I->expect('downloading csv file');
        $files = $this->waitForFileExists();
        $csvFile = array_pop($files);

        $I->openFile($csvFile);
        $I->seeInThisFile("ID,Name,Status\n");
        $I->seeNumberNewLines(22);
        $I->deleteThisFile();
    }

    public function exportAllRecordsWithFilterToCsv(\AcceptanceTester $I)
    {
        $I->wantToTest('export all records whose match id<=30');

        $this->cleanDownloadDir();

        $summaryText = $I->grabTextFrom($this->summaryBlock);
        $I->seeElement($this->columnSelectionButtonDisabled);
        $I->pressKey($this->idInput, '<=30', WebDriverKeys::ENTER);
        $this->waitForSummaryChannge($I, $summaryText);

        $I->seeElement($this->paginationBlock);
        $I->seeElement($this->columnSelectionButtonDisabled);

        $I->expectTo('select all records in current page');
        $I->checkOption($this->selectAllCheckbox);
        $I->seeElement($this->columnSelectionButtonEnable);

        $I->expectTo('select all match search');
        $I->see('Select all suppliers that match this search', $this->exportTip);
        $I->click($this->exportTipLink);

        $I->waitForElementClickable($this->columnSelectionButton);
        $I->click($this->columnSelectionButton);

        $I->see('Select export columns', ['xpath' => '//h5']);
        $I->seeElement($this->exportModal);
        $I->waitForElementClickable($this->exportButton);
        $I->click($this->exportButton);

        $I->expect('downloading csv file');
        $files = $this->waitForFileExists();
        $csvFile = array_pop($files);

        $I->openFile($csvFile);
        $I->seeInThisFile("ID,Name,Code,Status\n");
        $I->seeNumberNewLines(32);
        $I->deleteThisFile();
    }
}
