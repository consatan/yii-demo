<?php

namespace tests\unit\models;

use app\models\Supplier;
use app\models\SupplierSearch;
use yii\data\ActiveDataProvider;

class SupplierSearchTest extends \Codeception\Test\Unit
{
    protected function getDataProvider(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => Supplier::find(),
        ]);
    }

    /**
     * @dataProvider idProvider
     */
    public function testIDFilter(string $id, array $where)
    {
        $method = new \ReflectionMethod(SupplierSearch::class, 'filterID');
        $method->setAccessible(true);

        $dataProvider = $this->getDataProvider();
        $result = $method->invoke(new SupplierSearch(), $id, $dataProvider);
        verify($result)->equals(true);
        verify($dataProvider->query->where)->equals($where);
    }

    protected function idProvider(): array
    {
        return [
            ['10', ['=', 'id', '10']],
            ['<10', ['<', 'id', '10']],
            ['>=10', ['>=', 'id', '10']],
            ['[10, 20]', ['and', ['>=', 'id', '10'], ['<=', 'id', '20']]],
            ['(10, 20]', ['and', ['>', 'id', '10'], ['<=', 'id', '20']]],
            ['(10, 20)', ['and', ['>', 'id', '10'], ['<', 'id', '20']]],
            ['(20, 10]', ['and', ['>=', 'id', '10'], ['<', 'id', '20']]],
            ['  10  ', ['=', 'id', '10']],
            ['>  10', ['>', 'id', '10']],
            ['  ( 10  ,   20]  ', ['and', ['>', 'id', '10'], ['<=', 'id', '20']]],
            ['(10,10]', ['id' => '10']],
        ];
    }

    public function testIncorrectID()
    {
        $model = new SupplierSearch([
            'id' => '-10',
        ]);
        $dataProvider = $model->search([]);
        $this->assertInstanceOf(ActiveDataProvider::class, $dataProvider);
        verify($model->hasErrors())->equals(true);
        verify($model->getErrors())->equals(['id' => ['ID must be an integer or a range.']]);
    }

    /**
     * @dataProvider exportProvider
     */
    public function testExport(array $params, ?array $where)
    {
        $method = new \ReflectionMethod(SupplierSearch::class, 'export');
        $method->setAccessible(true);

        $dataProvider = $this->getDataProvider();
        $result = $method->invoke(new SupplierSearch(), $params, $dataProvider);
        verify($result)->equals($dataProvider);
        verify($result->query->where)->equals($where);
    }

    protected function exportProvider(): array
    {
        return [
            [['select_all' => 1], null],
            [['select_all' => 0, 'checked_ids' => '1,2,3'], ['in', 'id', [1, 2, 3]]],
            [['select_all' => 2, 'checked_ids' => '1,2,3'], ['in', 'id', [1, 2, 3]]],
            [['checked_ids' => '1,2,3'], ['in', 'id', [1, 2, 3]]],
            [['checked_ids' => '1,2,1,3,'], ['in', 'id', [1, 2, 3]]],
        ];
    }

    /**
     * @dataProvider incorrectExportProvider
     */
    public function testIncorrectExportParam(array $params)
    {
        $method = new \ReflectionMethod(SupplierSearch::class, 'export');
        $method->setAccessible(true);

        $model = new SupplierSearch();
        $dataProvider = $this->getDataProvider();
        $result = $method->invoke($model, $params, $dataProvider);
        verify($result)->equals($dataProvider);
        verify($model->hasErrors())->equals(true);
        verify($model->getErrors())->equals(['id' => ['Please select export ids']]);
    }

    protected function incorrectExportProvider(): array
    {
        return [
            [[]],
            [['select_all' => 0]],
            [['select_all' => 3]],
            [['select_all' => 3, 'checked_ids' => '']],
            [['checked_ids' => '']],
            [['checked_ids' => 'abc,def']],
        ];
    }

    /**
     * @dataProvider searchProvider
     */
    public function testSearch(array $params, bool $export, ?array $where)
    {
        $model = new SupplierSearch(array_intersect_key($params, [
            'id' => null,
            'name' => null,
            'code' => null,
            't_status' => null,
        ]));
        $result = $model->search($params, $refExport);

        verify($model->hasErrors())->equals(false);
        verify($result)->instanceOf(ActiveDataProvider::class);
        verify($result->query->where)->equals($where);
        verify($refExport)->equals($export);
    }

    protected function searchProvider(): array
    {
        return [
            [
                ['t_status' => 'hold', 'export' => 1, 'select_all' => 1],
                true,
                ['t_status' => 'hold'],
            ],
            [
                ['id' => '10', 'name' => 'ab', 'code' => 'cd', 't_status' => 'ok'],
                false,
                ['and', ['=', 'id', '10'], ['like', 'name', 'ab'], ['like', 'code', 'cd'], ['t_status' => 'ok']],
            ],
            [
                ['id' => '>10', 'name' => 'ab', 'code' => 'cd', 't_status' => 'ok'],
                false,
                ['and', ['>', 'id', '10'], ['like', 'name', 'ab'], ['like', 'code', 'cd'], ['t_status' => 'ok']],
            ],
            [
                ['name' => 'ab'],
                false,
                ['like', 'name', 'ab'],
            ],
            [
                ['t_status' => 'hold'],
                false,
                ['t_status' => 'hold'],
            ],
            [
                [],
                false,
                null,
            ],
        ];
    }

    public function testGetAttributeLabels()
    {
        $model = new SupplierSearch();
        $labels = $model->attributeLabels();

        $this->assertEquals($labels, [
            'id' => 'ID',
            'name' => 'Name',
            'code' => 'Code',
            't_status' => 'Status',
        ]);
    }
}
