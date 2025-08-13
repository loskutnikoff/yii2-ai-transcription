<?php

use yii\db\Migration;

/**
 * Class m250726_092310_alter_dsf_call_transcription_check_list_survey_kpi_answer
 */
class m250726_092310_alter_dsf_call_transcription_check_list_survey_kpi_answer extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('dsf_call_transcription_check_list_survey', 'kpi_answer', $this->boolean()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('dsf_call_transcription_check_list_survey', 'kpi_answer', $this->boolean()->notNull()->defaultValue(0));
    }
}
