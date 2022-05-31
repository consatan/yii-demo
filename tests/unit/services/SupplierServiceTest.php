<?php

namespace tests\unit\services;

use app\models\SupplierSearch;
use app\services\SupplierService;
use yii\db\ActiveQuery;
use yii\data\ActiveDataProvider;

class SupplierServiceTest extends \Codeception\Test\Unit
{
    /**
     * @dataProvider exportDataProvider
     */
    public function testExportToCsv(array $columns, array $data, string $output)
    {
        $model = new SupplierSearch(['export_columns' => $columns]);
        $dataProvider = new ActiveDataProvider([
            'query' => $this->make(ActiveQuery::class, [
                'each' => function () use ($data) {
                    return $data;
                }
            ])
        ]);

        ob_start();
        SupplierService::exportToCsv($model, $dataProvider);
        $content = ob_get_flush();

        $this->assertEquals($output, $content);
    }

    public function exportDataProvider(): array
    {
        return [
            'all columns' => [
                ['id', 'name', 'code', 't_status'],
                [
                    ['id' => 1, 'name' => 'abc', 'code' => 'abc', 't_status' => 'ok'],
                    ['id' => 2, 'name' => 'def', 'code' => null, 't_status' => 'hold'],
                    ['id' => 3, 'name' => 'xyz', 'code' => 'xyz', 't_status' => 'ok'],
                ],
                "ID,Name,Code,Status\n1,abc,abc,ok\n2,def,,hold\n3,xyz,xyz,ok\n",
            ],
            'part columns' => [
                ['id', 't_status'],
                [
                    ['id' => 1, 't_status' => 'ok'],
                    ['id' => 2, 't_status' => 'hold'],
                    ['id' => 3, 't_status' => 'ok'],
                ],
                "ID,Status\n1,ok\n2,hold\n3,ok\n",
            ],
            "name column has space" => [
                ['id', 'name'],
                [
                    ['id' => 11, 'name' => 'has space'],
                    ['id' => 22, 'name' => 'hello world'],
                ],
                "ID,Name\n11,\"has space\"\n22,\"hello world\"\n",
            ],
            "name column has escape char" => [
                ['id', 'name'],
                [
                    ['id' => 11, 'name' => 'has "space"'],
                    ['id' => 22, 'name' => '"world"'],
                ],
                "ID,Name\n11,\"has \"\"space\"\"\"\n22,\"\"\"world\"\"\"\n",
            ],
        ];
    }

    public function testQueryFail()
    {
        $model = new SupplierSearch(['export_columns' => ['id']]);
        $dataProvider = new ActiveDataProvider([
            'query' => $this->make(ActiveQuery::class, [
                'each' => function () {
                    throw new \PDOException('SQLSTATE[HY000] [2002] Operation timed out', 2002);
                }
            ])
        ]);

        ob_start();
        try {
            SupplierService::exportToCsv($model, $dataProvider);
        } catch (\Throwable $t) {
            $this->assertStringContainsString('Operation timed out', $t->getMessage());
        } finally {
            $content = ob_get_flush();
        }

        $this->assertEquals("ID\n", $content);
    }
}
