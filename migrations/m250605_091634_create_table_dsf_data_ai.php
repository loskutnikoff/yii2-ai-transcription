<?php

use yii\db\Migration;

/**
 * Class m250605_091634_create_table_dsf_data_ai
 */
class m250605_091634_create_table_dsf_data_ai extends Migration
{
    public function safeUp()
    {
        $this->createTable('dsf_data_ai', [
            'id' => $this->primaryKey(),
            'entity_id' => $this->integer()->notNull(),
            'entity_type' => $this->integer()->notNull(),
            'data_json' => $this->json(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'updated_by' => $this->integer()->notNull(),
        ], 'ENGINE=InnoDB');
    }

    public function safeDown()
    {
        $this->dropTable('dsf_call_transcription');
    }
}
