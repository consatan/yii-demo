<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Supplier;

/**
 * SupplierSearch represents the model behind the search form of `app\models\Supplier`.
 */
class SupplierSearch extends Supplier
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'name', 'code'], 'safe'],
            ['t_status', 'in', 'range' => ['ok', 'hold']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        $labels = parent::attributeLabels();
        $labels['t_status'] = 'Status';
        return $labels;
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
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
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        if ($this->id && !$this->filterID($this->id, $dataProvider)) {
            return $dataProvider;
        }

        $dataProvider->query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'code', $this->code])
            ->andFilterWhere(['t_status' => $this->t_status]);

        return $dataProvider;
    }

    /**
     * ID column filter
     * support following format:
     * - single number
     * - number range, e.g. >10
     * - number interval, e.g. [10, 400)
     *
     * @param string $id
     * @param \yii\data\ActiveDataProvider $dataProvider
     *
     * @return bool
     */
    protected function filterID(string $id, ActiveDataProvider $dataProvider): bool
    {
        $query = $dataProvider->query;
        if (1 === preg_match('/^(=|[><]=?)?\s*(\d+)$/', trim($id), $match)) {
            $val = (int)$match[2];
            $cond = $match[1] ?? '=';

            $query->andFilterCompare('id', "{$cond}{$val}");
        } elseif (1 === preg_match('/^(\[|\()\s*(\d+)\s*,\s*(\d+)\s*(\]|\))$/', trim($id), $match)) {
            $cond1 = $match[1] === '[' ? '>=' : '>';
            $cond2 = $match[4] === ']' ? '<=' : '<';

            $val1 = (int)$match[2];
            $val2 = (int)$match[3];

            if ($val1 === $val2) {
                $query->andFilterWhere(['id' => $val1]);
            } else {
                // [20, 10)  wrong format, convert to (10, 20]
                if ($val1 > $val2) {
                    $tmp = $val1;
                    $val1 = $val2;
                    $val2 = $tmp;

                    $tmp = $cond1;
                    $cond1 = $cond2;
                    $cond2 = $tmp;

                    $cond1 = $cond1 === '<' ? '>' : '>=';
                    $cond2 = $cond2 === '>' ? '<' : '<=';
                }

                $query->andFilterCompare('id', "{$cond1}{$val1}")->andFilterCompare('id', "{$cond2}{$val2}");
            }
        } else {
            $this->addError('id', 'ID must be an integer or a range.');

            return false;
        }

        return true;
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
        $ids = trim($params['ids'] ?? '');
        if (empty($ids)) {
            return $this->search($params);
        }

        $dataProvider = $this->getDataProvider();
        if (1 !== preg_match('/^(\d+,)*\d+$/', $ids)) {
            $this->addError('ids', 'invalid ids format');
            return $dataProvider;
        }

        $dataProvider->query->where(['in', 'id', explode(',', $ids)]);
        return $dataProvider;
    }

    protected function getDataProvider()
    {
        $query = Supplier::find();
        return new ActiveDataProvider([
            'query' => $query,
            // 'pagination' => [
            //     'pageSize' => 50,
            // ]
        ]);
    }
}
