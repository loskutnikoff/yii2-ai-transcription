<?php

use yii\db\Migration;

class m250604_095154_create_table_dsf_call_transcription extends Migration
{
    public function safeUp()
    {
        $this->createTable('dsf_call_transcription', [
            'id' => $this->primaryKey(),
            'entity_id' => $this->integer()->notNull(),
            'entity_type' => $this->integer()->notNull(),
            'text' => $this->text(),
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
