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
    public $id;
    public $name;
    public $code;
    public $t_status;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'name', 'code', 't_status'], 'safe'],
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
     * @param bool &$export
     *
     * @return \yii\data\ActiveDataProvider
     */
    public function search(array $params, ?bool &$export = false): ActiveDataProvider
    {
        $query = Supplier::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        if ($this->id && !$this->filterID($this->id, $dataProvider)) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'code', $this->code])
            ->andFilterWhere(['t_status' => $this->t_status]);

        if (!empty($params['export'])) {
            $export = true;
            return $this->export($params, $dataProvider);
        }

        return $dataProvider;
    }

    /**
     * get export dataProvider
     *
     * @param array $params
     * @param \yii\data\ActiveDataProvider $dataProvider
     *
     * @return \yii\data\ActiveDataProvider
     */
    protected function export(array $params, ActiveDataProvider $dataProvider): ActiveDataProvider
    {
        $selectAll = isset($params['select_all']) && 1 === (int)$params['select_all'];
        if (!$selectAll) {
            if (empty($params['checked_ids'])) {
                $this->addError('id', 'Please select export ids');

                return $dataProvider;
            }

            $checkedIds = array_values(array_unique(
                array_map('intval', array_filter(explode(',', trim($params['checked_ids'])), 'is_numeric'))
            ));
            if (empty($checkedIds)) {
                $this->addError('id', 'Please select export ids');

                return $dataProvider;
            }

            $dataProvider->query->andFilterWhere(['in', 'id', $checkedIds]);
        }

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
}
