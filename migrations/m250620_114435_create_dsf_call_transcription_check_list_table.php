<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%dsf_call_transcription_check_list}}`.
 */
class m250620_114435_create_dsf_call_transcription_check_list_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('dsf_call_transcription_check_list', [
            'id' => $this->primaryKey(),
            'call_transcription_id' => $this->integer()->notNull(),
            'check_list_id' => $this->integer()->notNull(),
            'status' => $this->smallInteger()->notNull()->defaultValue(0),
            'finished_at' => $this->dateTime(),
            'total_earned_points' => $this->decimal(19, 4),
            'total_earned_blocks_points' => $this->text(),
            'is_final_check_complete' => $this->boolean()->notNull()->defaultValue(0),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);

        $this->createIndex('check_list_id_call_transcription_id', 'dsf_call_transcription_check_list', ['check_list_id', 'call_transcription_id'], true);

        $this->addForeignKey(
            'fk_dsf_call_transcription_check_list_call_transcription_id',
            'dsf_call_transcription_check_list',
            'call_transcription_id',
            'dsf_call_transcription',
            'id'
        );
        $this->addForeignKey(
            'fk_dsf_call_transcription_check_list_check_list_id',
            'dsf_call_transcription_check_list',
            'check_list_id',
            'dsf_check_list',
            'id'
        );
        $this->addForeignKey(
            'fk_dsf_call_transcription_check_list_created_by',
            'dsf_call_transcription_check_list',
            'created_by',
            'common_user',
            'id'
        );
        $this->addForeignKey(
            'fk_dsf_call_transcription_check_list_updated_by',
            'dsf_call_transcription_check_list',
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
        $this->dropTable('dsf_call_transcription_check_list');
    }
}
