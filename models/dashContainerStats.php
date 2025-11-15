<?php

namespace fpcm\modules\nkorg\extstats\models;

class dashContainerStats extends \fpcm\model\dashboard\types\chart {

    use \fpcm\module\tools;

    /**
     * Conuter instance
     * @var counter
     */
    private $counter;

    protected function initObjects()
    {
        $this->initChartInstance();
        
        $this->counter = new \fpcm\modules\nkorg\extstats\models\counter();
        $this->counter->setChart($this->chart);
        $this->counter->fetchVisitors(
            date('Y-m-d', time() - 7 * FPCM_DATE_SECONDS),
            '',
            counter::MODE_DAY
        );

        return true;
    }

    public function getHeadline() : string
    {
        return $this->language->translate($this->addLangVarPrefix('FROMVISITS'));
    }

    public function getName() : string
    {
        return 'nkorg_extstats_dashchart';
    }

    public function getHeight() : string
    {
        return self::DASHBOARD_HEIGHT_SMALL_MEDIUM;
    }

    public function getPosition()
    {
        return self::DASHBOARD_POS_MAX;
    }

    public function getJavascriptVars() : array
    {
        return [
            'extstatsChartData' => $this->counter->getChart()
        ];
    }

    /**
     * Returns chart name
     * @return string
     */
    protected function getChartName() : string
    {
        return 'fpcm-nkorg-extstats-dashchart';
    }

    /**
     * Returns chart type
     * @return string
     */
    protected function getChartType() : string
    {
        return 'bar';
    }

    /**
     * Returns container JS script file
     * @return string
     */
    protected function getContainerScript() : string
    {
        return 'moduleDashboard.js';
    }

}
