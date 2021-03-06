<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Supplier;

/**
 * SupplierForm represents the model behind the search form of `app\models\Supplier`.
 */
class SupplierForm extends Supplier
{
    // @var string export scenario
    const SCENARIO_EXPORT = 'export';

    // @var string search scenario
    const SCENARIO_SEARCH = 'default';

    // @var int|string
    public $id;

    // @var string
    public $name;

    // @var string|null
    public $code;

    // @var string
    public $t_status;

    // @var string
    public $export_ids;

    // @var string[]
    public $export_columns;

    /** {@inheritdoc} */
    public function rules()
    {
        return [
            [['id', 'code', 'name', 't_status', 'export_ids', 'export_columns'], 'trim'],
            ['id', 'match', 'pattern' => '/^(=|[><]=?)?\s*(\d+)$/'],
            ['code', 'string', 'length' => [1, 3]],
            ['name', 'string', 'length' => [1, 50]],
            ['t_status', 'in', 'range' => ['ok', 'hold']],
            ['export_ids', 'match', 'pattern' => '/^(\d+,)*\d+$/', 'on' => self::SCENARIO_EXPORT],
            ['export_columns', 'default', 'value' => ['id'], 'on' => self::SCENARIO_EXPORT],
            ['export_columns', 'in', 'range' => $this->attributes(), 'allowArray' => true, 'on' => self::SCENARIO_EXPORT],
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return \yii\data\ActiveDataProvider
     */
    public function search(array $params): ActiveDataProvider
    {
        $dataProvider = $this->getDataProvider();

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $dataProvider->query->andFilterCompare('id', $this->id)
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'code', $this->code])
            ->andFilterWhere(['t_status' => $this->t_status]);

        return $dataProvider;
    }

    /**
     * Creates data provider instance with export
     *
     * @param array $params
     *
     * @return \yii\data\ActiveDataProvider
     */
    public function export(array $params): ActiveDataProvider
    {
        $dataProvider = $this->getDataProvider();

        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }

        if (empty($this->export_ids)) {
            $this->scenario = self::SCENARIO_SEARCH;
            $dataProvider = $this->search($params);
        } else {
            $dataProvider->query->where(['in', 'id', explode(',', $this->export_ids)]);
        }

        $dataProvider->query->select(array_unique($this->export_columns));

        return $dataProvider;
    }

    /**
     * Create data provider instance
     *
     * @return \yii\data\ActiveDataProvider
     */
    protected function getDataProvider(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => Supplier::find(),
            // 'pagination' => [
            //     'pageSize' => 50,
            // ]
        ]);
    }
}
