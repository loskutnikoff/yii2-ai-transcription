<?php

use yii\db\Migration;

/**
 * Class m250726_083127_alter_dsf_call_transcription_check_list_total_earned_blocks_points
 */
class m250726_083127_alter_dsf_call_transcription_check_list_total_earned_blocks_points extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('dsf_call_transcription_check_list', 'total_earned_blocks_points', $this->json());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('dsf_call_transcription_check_list', 'total_earned_blocks_points', $this->text());
    }
}
