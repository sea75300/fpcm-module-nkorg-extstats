<?php

namespace fpcm\modules\nkorg\extstats\crons;

class anonymizeLinks extends \fpcm\model\abstracts\cron {

    public function run()
    {
        return $this->dbcon->update(
            (new \fpcm\module\module('nkorg/extstats'))->getFullPrefix('counts_links'),
            ['lastip', 'lastagent'],
            ['127.0.0.1', '', $this->lastExecTime],
            'lasthit < ?'
        );
    }

}