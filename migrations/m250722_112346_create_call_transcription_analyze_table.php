<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%call_transcription_analyze}}`.
 */
class m250722_112346_create_call_transcription_analyze_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('dsf_call_transcription_analyze', [
            'id' => $this->primaryKey(),
            'call_transcription_id' => $this->integer()->notNull(),
            'data_json' => $this->json(),
            'is_successful' => $this->boolean(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);

        $this->addForeignKey(
            'fk_call_transcription_analyze_call_transcription_id',
            'dsf_call_transcription_analyze',
            'call_transcription_id',
            'dsf_call_transcription',
            'id'
        );
        $this->addForeignKey(
            'fk_call_transcription_analyze_created_by',
            'dsf_call_transcription_analyze',
            'created_by',
            'common_user',
            'id'
        );
        $this->addForeignKey(
            'fk_call_transcription_analyze_updated_by',
            'dsf_call_transcription_analyze',
            'updated_by',
            'common_user',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('dsf_call_transcription_analyze');
    }
}
