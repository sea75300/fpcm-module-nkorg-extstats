<?php

namespace fpcm\modules\nkorg\extstats\events\cron;

final class includeDumpTables extends \fpcm\module\event {

    public function run()
    {
        $db = \fpcm\classes\loader::getObject('\fpcm\classes\database');

        $this->data[] = $db->getTablePrefixed('module_nkorgextstats_counts_links');
        $this->data[] = $db->getTablePrefixed('module_nkorgextstats_counts_visits');
        return $this->data;
    }

    public function init(): bool
    {
        return false;
    }

}