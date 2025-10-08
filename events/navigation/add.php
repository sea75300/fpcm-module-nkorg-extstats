<?php

namespace fpcm\modules\nkorg\extstats\events\navigation;

final class add extends \fpcm\module\event {

    public function run() : \fpcm\module\eventResult
    {
        $item = (new \fpcm\model\theme\navigationItem())
                ->setDescription('MODULE_NKORGEXTSTATS_HEADLINE')
                ->setIcon('fa fa-chart-pie fa-fw')
                ->setUrl('extstats/statistics');
        
        $this->data->add(\fpcm\model\theme\navigationItem::AREA_AFTER, $item);
        
        return (new \fpcm\module\eventResult())->setData($this->data);
    }

    public function init()
    {
        return true;
    }

}
