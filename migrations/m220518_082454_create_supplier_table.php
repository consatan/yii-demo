<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%supplier}}`.
 */
class m220518_082454_create_supplier_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%supplier}}', [
            'id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(50)->notNull()->defaultValue(''),
            'code' => $this->char(3)->append('CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL'),
            't_status' => "enum('ok', 'hold') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'ok'",
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $this->createIndex('uk_code', '{{%supplier}}', 'code', true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%supplier}}');
    }
}
