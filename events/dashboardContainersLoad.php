<?php

namespace fpcm\modules\nkorg\extstats\events;

final class dashboardContainersLoad extends \fpcm\module\event {

    public function run() : \fpcm\module\eventResult
    {
        if (!$this->getObject()->getOption('show_visitors')) {
            return (new \fpcm\module\eventResult())->setData($this->data);
        }
        
        $this->data[] = 'fpcm\modules\nkorg\extstats\models\dashContainerStats';
        
        return (new \fpcm\module\eventResult())->setData($this->data);
    }

    public function init() : bool
    {
        return true;
    }

}
