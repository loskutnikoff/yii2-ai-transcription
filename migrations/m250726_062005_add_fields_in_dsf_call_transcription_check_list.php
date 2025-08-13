<?php

use yii\db\Migration;

/**
 * Class m250726_062005_add_fields_in_dsf_call_transcription_check_list
 */
class m250726_062005_add_fields_in_dsf_call_transcription_check_list extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('dsf_call_transcription_check_list', 'max_available_points', $this->decimal(19, 4));
        $this->addColumn('dsf_call_transcription_check_list', 'max_available_blocks_points', $this->json());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('dsf_call_transcription_check_list', 'max_available_points');
        $this->dropColumn('dsf_call_transcription_check_list', 'max_available_blocks_points');
    }
}
