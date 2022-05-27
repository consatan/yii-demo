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
        $model = new SupplierSearch(array_intersect_key($params, [
            'id' => null,
            'name' => null,
            'code' => null,
            't_status' => null,
        ]));
        $dataProvider = $model->export($params);
        $this->assertInstanceOf(ActiveDataProvider::class, $dataProvider);
        $this->assertEquals($dataProvider->query->where, $where);
    }

    protected function exportProvider(): array
    {
        return [
            [[], null],
            [['ids' => '1,2,3'], ['in', 'id', [1, 2, 3]]],
            [
                ['id' => '>10', 'name' => 'ab', 'code' => 'cd', 't_status' => 'ok'],
                ['and', ['>', 'id', '10'], ['like', 'name', 'ab'], ['like', 'code', 'cd'], ['t_status' => 'ok']],
            ],
        ];
    }

    public function testIncorrectExportParam()
    {
        $params = ['ids' => 'abc'];
        $model = new SupplierSearch();
        $dataProvider = $model->export($params);
        $this->assertInstanceOf(ActiveDataProvider::class, $dataProvider);
        $this->assertTrue($model->hasErrors());
        $this->assertEquals(['ids' => ['invalid ids format']], $model->getErrors());
        $this->assertEquals(['invalid ids format'], \Yii::$app->session->getFlash('error'));
    }

    /**
     * @dataProvider searchProvider
     */
    public function testSearch(array $params, ?array $where)
    {
        $model = new SupplierSearch(array_intersect_key($params, [
            'id' => null,
            'name' => null,
            'code' => null,
            't_status' => null,
        ]));
        $result = $model->search($params);

        verify($model->hasErrors())->equals(false);
        verify($result)->instanceOf(ActiveDataProvider::class);
        verify($result->query->where)->equals($where);
    }

    protected function searchProvider(): array
    {
        return [
            [
                ['t_status' => 'hold', 'export' => 1, 'select_all' => 1],
                ['t_status' => 'hold'],
            ],
            [
                ['id' => '10', 'name' => 'ab', 'code' => 'cd', 't_status' => 'ok'],
                ['and', ['=', 'id', '10'], ['like', 'name', 'ab'], ['like', 'code', 'cd'], ['t_status' => 'ok']],
            ],
            [
                ['id' => '>10', 'name' => 'ab', 'code' => 'cd', 't_status' => 'ok'],
                ['and', ['>', 'id', '10'], ['like', 'name', 'ab'], ['like', 'code', 'cd'], ['t_status' => 'ok']],
            ],
            [
                ['name' => 'ab'],
                ['like', 'name', 'ab'],
            ],
            [
                ['t_status' => 'hold'],
                ['t_status' => 'hold'],
            ],
            [
                [],
                null,
            ],
        ];
    }

    public function testIncorrectSearchParam()
    {
        $params = ['t_status' => 'other'];
        $model = new SupplierSearch($params);
        $dataProvider = $model->search($params);
        $this->assertInstanceOf(ActiveDataProvider::class, $dataProvider);
        $this->assertTrue($model->hasErrors());
        $this->assertEquals(['t_status' => ['Status is invalid.']], $model->getErrors());
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
