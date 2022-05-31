<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[Supplier]].
 *
 * @see Supplier
 */
class SupplierQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return Supplier[]|array
     * @codeCoverageIgnore
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return Supplier|array|null
     * @codeCoverageIgnore
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
