<?php

use yii\db\Migration;

/**
 * Class m250726_083735_add_is_excluded_in_dsf_call_transcription_check_list_survey
 */
class m250726_083735_add_is_excluded_in_dsf_call_transcription_check_list_survey extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('dsf_call_transcription_check_list_survey', 'is_excluded', $this->boolean()->notNull()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('dsf_call_transcription_check_list_survey', 'is_excluded');
    }
}
