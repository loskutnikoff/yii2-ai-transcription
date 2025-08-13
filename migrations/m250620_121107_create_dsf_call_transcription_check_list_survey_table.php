<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%dsf_call_transcription_check_list_survey}}`.
 */
class m250620_121107_create_dsf_call_transcription_check_list_survey_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(
            'dsf_call_transcription_check_list_survey',
            [
                'id' => $this->primaryKey(),
                'check_list_id' => $this->integer()->notNull(),
                'question_id' => $this->integer()->notNull(),
                'kpi_answer' => $this->boolean()->notNull()->defaultValue(0),
                'comment' => $this->text(),

                'created_by' => $this->integer(),
                'updated_by' => $this->integer(),
                'created_at' => $this->dateTime(),
                'updated_at' => $this->dateTime(),
            ]
        );

        $this->addForeignKey(
            'fk_dsf_call_transcription_check_list_survey_check_list_id',
            'dsf_call_transcription_check_list_survey',
            'check_list_id',
            'dsf_call_transcription_check_list',
            'id'
        );
        $this->addForeignKey(
            'fk_dsf_call_transcription_check_list_survey_question_id',
            'dsf_call_transcription_check_list_survey',
            'question_id',
            'dsf_check_list_question',
            'id',
            'RESTRICT',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_dsf_call_transcription_check_list_survey_created_by',
            'dsf_call_transcription_check_list_survey',
            'created_by',
            'common_user',
            'id'
        );
        $this->addForeignKey(
            'fk_dsf_call_transcription_check_list_survey_updated_by',
            'dsf_call_transcription_check_list_survey',
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
        $this->dropTable('dsf_call_transcription_check_list_survey');
    }
}
