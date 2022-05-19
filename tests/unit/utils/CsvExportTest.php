<?php

namespace tests\unit\utils;

use yii\db\ActiveQuery;
use yii\base\InvalidArgumentException;

use app\utils\CsvExport;

class CsvExportTest extends \Codeception\Test\Unit
{
    protected $query = null;

    protected $data = [];

    protected function _before()
    {
        $this->query = $this->make(ActiveQuery::class, [
            'asArray' => function() {
                return $this->query;
            },
            'batch' => function($size = 100) {
                return array_chunk($this->data, $size);
            }
        ]);
    }

    /**
     * @dataProvider csvDataProvider
     */
    public function testCsvExportToString(
        array $data,
        array $coldefs,
        string $csv,
        bool $printHeader = true,
        string $separator = ','
    ) {
        $this->assertInstanceOf(ActiveQuery::class, $this->query);

        $this->data = $data;
        $result = CsvExport::export($this->query, $coldefs, $printHeader, null, $separator);

        $this->assertEquals($result, $csv);
    }

    /**
     * @dataProvider csvDataProvider
     */
    public function testCsvExportToDownload(
        array $data,
        array $coldefs,
        string $csv,
        bool $printHeader = true,
        string $separator = ','
    ) {
        $this->assertInstanceOf(ActiveQuery::class, $this->query);
        $filename = 'export.csv';

        $this->data = $data;
        ob_start();
        $result = CsvExport::export($this->query, $coldefs, $printHeader, $filename, $separator);
        $output = ob_get_clean();

        $this->assertTrue(is_string($result));
        $this->assertEquals($output, $csv);
    }

    protected function csvDataProvider(): array
    {
        $data = [
            ['id' => 1, 'name' => 'a'],
            ['id' => 2, 'name' => 'b'],
            ['id' => 3, 'name' => 'c'],
            ['id' => 4, 'name' => 'd'],
            ['id' => 5, 'name' => 'e'],
        ];

        return [
            [$data, ['id' => ['raw'], 'name' => ['raw']], "id,name\r\n1,a\r\n2,b\r\n3,c\r\n4,d\r\n5,e\r\n"],
            [$data, ['id' => ['raw'], 'name' => ['raw']], "1,a\r\n2,b\r\n3,c\r\n4,d\r\n5,e\r\n", false],
            [$data, ['id' => ['raw'], 'name' => ['raw']], "1;a\r\n2;b\r\n3;c\r\n4;d\r\n5;e\r\n", false, ';'],
            [$data, ['id' => null, 'name' => null], "id,name\r\n1,a\r\n2,b\r\n3,c\r\n4,d\r\n5,e\r\n"],
            [[
                ['id' => 1, 'date' => '2022-05-19'],
                ['id' => 2, 'date' => '2022-05-19 20:15:00'],
                ['id' => 3, 'date' => '2022-05-19T20:15:00+08:00'],
            ], ['id' => ['raw'], 'date' => [['date', 'php:Y-m-d']]], "id,date\r\n1,2022-05-19\r\n2,2022-05-19\r\n3,2022-05-19\r\n"],
        ];
    }

    public function testCsvExportException()
    {
        try {
            $this->data = [['id' => 1, 'date' => 'a'], ['id' => 2, 'date' => 'b']];
            CsvExport::export($this->query, ['id' => ['raw'], 'date' => ['php:Y-m-d']]);
        } catch (\Throwable $t) {
            $this->assertInstanceOf(InvalidArgumentException::class, $t);
            $this->assertStringContainsString('php:Y-m-d', $t->getMessage());
        }
    }
}
