<?php

namespace fpcm\modules\nkorg\extstats\models;

class dashContainerStats extends \fpcm\model\abstracts\dashcontainer {

    use \fpcm\module\tools;

    /**
     * Container chart
     * @var \fpcm\components\charts\chart
     */
    private $chart;

    /**
     * Conuter instance
     * @var counter
     */
    private $counter;

    protected function initObjects()
    {       
        $this->chart = new \fpcm\components\charts\chart('bar', 'fpcm-nkorg-extstats-dashchart');

        $this->counter = new \fpcm\modules\nkorg\extstats\models\counter();
        $this->counter->setChart($this->chart);
        $l = $this->counter->fetchVisitors(
            date('Y-m-d', time() - 7 * FPCM_DATE_SECONDS),
            '',
            counter::MODE_DAY
        );

        return true;
    }


    public function getContent() : string
    {
        return implode(PHP_EOL, [
            '<div class="row no-gutters align-self-center align-content-center justify-content-center">',
            '   <div class="col-12">',
            $this->chart,
            '   </div>',
            '</div>'
        ]);
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

    public function getJavascriptFiles() : array
    {
        $files = $this->chart->getJsFiles();
        $files[1] = \fpcm\classes\dirs::getCoreUrl(\fpcm\classes\dirs::CORE_JS, $files[1]);
        $files[] = \fpcm\classes\dirs::getDataUrl(\fpcm\classes\dirs::DATA_MODULES, $this->getModuleKey() . '/js/moduleDashboard.js');
        
        return $files;
    }

    public function getJavascriptVars() : array 
    {
        return [
            'extstatsChartData' => $this->counter->getChart()
        ];
    }

}
