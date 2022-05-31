<?php

namespace tests\unit\models;

use app\models\Supplier;
use app\models\SupplierForm;
use yii\db\ActiveQuery;
use yii\data\ActiveDataProvider;

class SupplierFormTest extends \Codeception\Test\Unit
{
    /**
     * @dataProvider searchDataProvider
     */
    public function testSearch(array $params, array $expected)
    {
        $model = new SupplierForm();
        $dataProvider = $model->search(['SupplierForm' => $params]);

        $this->assertFalse($model->hasErrors());
        $this->assertInstanceOf(ActiveDataProvider::class, $dataProvider);

        $this->assertEquals($expected, $dataProvider->query->where);
    }

    public function searchDataProvider(): array
    {
        return [
            'id range filter' => [
                ['id' => '>= 10'],
                ['>=', 'id', 10],
            ],
            'id match filter' => [
                ['id' => 30],
                ['=', 'id', 30],
            ],
            'id equal filter' => [
                ['id' => '=10'],
                ['=', 'id', 10],
            ],
            'multi fields filter' => [
                [
                    'id' => '< 10',
                    'name' => 'abc',
                    'code' => 'xyz',
                    't_status' => 'ok',
                ],
                [
                    'and',
                    ['<', 'id', 10],
                    ['like', 'name', 'abc'],
                    ['like', 'code', 'xyz'],
                    ['t_status' => 'ok'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider searchFailDataProvider
     */
    public function testSearchValidationFail(array $params, array $expected)
    {
        $model = new SupplierForm();
        $dataProvider = $model->search(['SupplierForm' => $params]);

        $this->assertTrue($model->hasErrors());
        $this->assertInstanceOf(ActiveDataProvider::class, $dataProvider);

        $this->assertEquals($expected, $model->getErrors());
    }

    public function searchFailDataProvider(): array
    {
        return [
            'id not numeric' => [
                ['id' => 'abc'],
                ['id' => ['ID is invalid.']],
            ],
            'id not int' => [
                ['id' => 123.456],
                ['id' => ['ID is invalid.']],
            ],
            'id range format incorrect' => [
                ['id' => '=> 10'],
                ['id' => ['ID is invalid.']],
            ],
            'status not in range' => [
                ['t_status' => 'error'],
                ['t_status' => ['Status is invalid.']],
            ],
            'code over length' => [
                ['code' => 'abcd'],
                ['code' => ['Code should contain at most 3 characters.']],
            ],
            'name over length' => [
                ['name' => 'abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz0123456789'],
                ['name' => ['Name should contain at most 50 characters.']],
            ],
            'multi fields incorrect' => [
                ['id' => 123.456, 't_status' => 'error', 'code' => 'abcd'],
                [
                    'id' => ['ID is invalid.'],
                    't_status' => ['Status is invalid.'],
                    'code' => ['Code should contain at most 3 characters.'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider exportDataProvider
     */
    public function testExport(array $params, ?array $expected, array $expectedColumns = ['id'])
    {
        $model = new SupplierForm(['scenario' => SupplierForm::SCENARIO_EXPORT]);
        $dataProvider = $model->export(['SupplierForm' => $params]);

        $this->assertFalse($model->hasErrors());
        $this->assertInstanceOf(ActiveDataProvider::class, $dataProvider);

        $this->assertEquals($expected, $dataProvider->query->where);
        $this->assertEquals($expectedColumns, array_values($dataProvider->query->select));
        $this->assertEquals($expectedColumns, $model->export_columns);
    }

    public function exportDataProvider(): array
    {
        return [
            'export multi ids' => [
                ['export_ids' => '1,2,3'],
                ['in', 'id', [1, 2, 3]],
            ],
            'export one id' => [
                ['export_ids' => '1', 'export_columns' => ['id', 'name']],
                ['in', 'id', [1]],
                ['id', 'name'],
            ],
            'export ids and other filter' => [
                ['export_ids' => '1,2,3', 'name' => 'abc', 't_status' => 'ok'],
                ['in', 'id', [1, 2, 3]],
            ],
            'export other filter' => [
                ['name' => 'abc', 't_status' => 'ok', 'export_columns' => ['id', 'name', 'code', 't_status']],
                ['and', ['like', 'name', 'abc'], ['t_status' => 'ok']],
                ['id', 'name', 'code', 't_status'],
            ],
            'export columns without filter' => [
                ['export_columns' => ['id', 'name']],
                null,
                ['id', 'name'],
            ],
        ];
    }

    /**
     * @dataProvider exportFailDataProvider
     */
    public function testExportValidationFail(array $params, array $expected)
    {
        $model = new SupplierForm(['scenario' => SupplierForm::SCENARIO_EXPORT]);
        $dataProvider = $model->export(['SupplierForm' => $params]);

        $this->assertTrue($model->hasErrors());
        $this->assertInstanceOf(ActiveDataProvider::class, $dataProvider);

        $this->assertEquals($expected, $model->getErrors());
    }

    public function exportFailDataProvider(): array
    {
        return [
            'export_ids not numeric' => [
                ['export_ids' => 'abc'],
                ['export_ids' => ['Export Ids is invalid.']],
            ],
            'export_ids format incorrect' => [
                ['export_ids' => ',1,2,3'],
                ['export_ids' => ['Export Ids is invalid.']],
            ],
            'export_columns not in range' => [
                ['export_columns' => ['id', 'a']],
                ['export_columns' => ['Export Columns is invalid.']],
            ],
            'export_columns not array' => [
                ['export_columns' => 'id,name'],
                ['export_columns' => ['Export Columns is invalid.']],
            ],
            'search filter incorrect' => [
                ['t_status' => 'hello'],
                ['t_status' => ['Status is invalid.']],
            ],
            'multi filter incorrect' => [
                ['export_ids' => 'abc', 'export_columns' => 'id,name'],
                ['export_ids' => ['Export Ids is invalid.'], 'export_columns' => ['Export Columns is invalid.']],
            ],
        ];
    }

    public function testGetAttributeLabels()
    {
        $model = new SupplierForm();
        $labels = $model->attributeLabels();

        $this->assertEquals([
            'id' => 'ID',
            'name' => 'Name',
            'code' => 'Code',
            't_status' => 'Status',
        ], $labels);
    }
}
