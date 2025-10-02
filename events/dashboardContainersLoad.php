<?php

namespace fpcm\modules\nkorg\extstats\events;

final class dashboardContainersLoad extends \fpcm\module\event {

    public function run()
    {
        if (!$this->getObject()->getOption('show_visitors')) {
            return $this->data;
        }
        
        $this->data[] = 'fpcm\modules\nkorg\extstats\models\dashContainerStats';
        return $this->data;
    }

    public function init() : bool
    {
        return true;
    }

}
