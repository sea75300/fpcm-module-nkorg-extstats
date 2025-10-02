<?php

namespace fpcm\modules\nkorg\extstats\controller;

final class statistics extends \fpcm\controller\abstracts\module\controller {

    /**
     *
     * @var \fpcm\components\dataView\dataView
     */
    private $dv;

    /**
     *
     * @var bool
     */
    private $hasIpAgent;

    protected function getViewPath() : string
    {
        return 'index';
    }

    public function process()
    {

        $chartTypes = [
            $this->addLangVarPrefix('TYPEBAR') => \fpcm\components\charts\chart::TYPE_BAR,
            $this->addLangVarPrefix('TYPELINE') => \fpcm\components\charts\chart::TYPE_LINE,
            $this->addLangVarPrefix('TYPEPIE') => \fpcm\components\charts\chart::TYPE_PIE,
            $this->addLangVarPrefix('TYPEDOUGHNUT') => \fpcm\components\charts\chart::TYPE_DOUGHNUT,
            $this->addLangVarPrefix('TYPEPOLAR') => \fpcm\components\charts\chart::TYPE_POLAR,
        ];

        $sortTypes = [
            $this->addLangVarPrefix('LINKSORT_COUNT') => \fpcm\modules\nkorg\extstats\models\counter::SORT_COUNT,
            $this->addLangVarPrefix('LINKSORT_DATE') => \fpcm\modules\nkorg\extstats\models\counter::SORT_DATE,
            $this->addLangVarPrefix('LINKSORT_LINK') => \fpcm\modules\nkorg\extstats\models\counter::SORT_LINK
        ];

        $chartModes = [
            $this->addLangVarPrefix('BYYEAR') => \fpcm\modules\nkorg\extstats\models\counter::MODE_YEAR,
            $this->addLangVarPrefix('BYMONTH') => \fpcm\modules\nkorg\extstats\models\counter::MODE_MONTH,
            $this->addLangVarPrefix('BYDAY') => \fpcm\modules\nkorg\extstats\models\counter::MODE_DAY
        ];

        $dataSource = [
            $this->addLangVarPrefix('FROMARTICLES') => \fpcm\modules\nkorg\extstats\models\counter::SRC_ARTICLES,
            $this->addLangVarPrefix('FROMSHARES') => \fpcm\modules\nkorg\extstats\models\counter::SRC_SHARES,
            $this->addLangVarPrefix('FROMCOMMENTS') => \fpcm\modules\nkorg\extstats\models\counter::SRC_COMMENTS,
            $this->addLangVarPrefix('FROMFILES') => \fpcm\modules\nkorg\extstats\models\counter::SRC_FILES,
            $this->addLangVarPrefix('FROMVISITS') => \fpcm\modules\nkorg\extstats\models\counter::SRC_VISITORS,
            $this->addLangVarPrefix('FROMLINKS') => \fpcm\modules\nkorg\extstats\models\counter::SRC_LINKS,
            $this->addLangVarPrefix('FROMREFERRER') => \fpcm\modules\nkorg\extstats\models\counter::SRC_REFERRER
        ];

        $this->getSettings($source, $chartType, $chartMode, $modeStr, $start, $stop, $sortType, $search);
        if (!trim($chartType)) {
            $chartType = \fpcm\components\charts\chart::TYPE_BAR;
        }

        $hideMode = in_array($source, [\fpcm\modules\nkorg\extstats\models\counter::SRC_SHARES, \fpcm\modules\nkorg\extstats\models\counter::SRC_LINKS, \fpcm\modules\nkorg\extstats\models\counter::SRC_REFERRER]);
        $isLinks = in_array($source, [\fpcm\modules\nkorg\extstats\models\counter::SRC_LINKS, \fpcm\modules\nkorg\extstats\models\counter::SRC_REFERRER]);

        $this->view->assign('isLinks', $isLinks);
        $this->view->assign('start', $start ?? '');
        $this->view->assign('stop', $stop ?? '');
        $this->view->assign('chartTypes', $chartTypes);
        $this->view->assign('chartType', $chartType);
        $this->view->assign('sortTypes', $sortTypes);
        $this->view->assign('sortType', $sortType);
        $this->view->assign('chartModes', $chartModes);
        $this->view->assign('chartMode', $chartMode);
        $this->view->assign('search', $search);

        $buttons = [
            (new \fpcm\view\helper\select('source'))
                ->setClass('fpcm-ui-input-select-articleactions')
                ->setOptions($dataSource)->setSelected($source)
                ->setFirstOption(\fpcm\view\helper\select::FIRST_OPTION_DISABLED),

            (new \fpcm\view\helper\submitButton('setdatespan'))
                ->setText('GLOBAL_OK')
        ];

        if ($isLinks) {
            $buttons[] = (new \fpcm\view\helper\submitButton('removeEntries'))
                ->setText($this->addLangVarPrefix('HITS_LIST_DELETE'), [
                    'limit' => $this->config->module_nkorgextstats_link_compress
                ])
                ->setIcon('file-archive')
                ->setIconOnly(true);
        }

        $this->view->addButtons($buttons);

        $chart = new \fpcm\components\charts\chart($chartType, 'fpcm-nkorg-extendedstats-chart');

        $counter = new \fpcm\modules\nkorg\extstats\models\counter();
        $counter->setChart($chart);

        if ($this->buttonClicked('removeEntries')) {
            if (!$counter->cleanupLinks()) {
                $this->view->addNoticeMessage($this->addLangVarPrefix('CLEANUP_FAILED'), [
                    'limit' => $this->config->module_nkorgextstats_link_compress
                ]);
            }
            else {
                $this->view->addNoticeMessage($this->addLangVarPrefix('CLEANUP_SUCCESS'), [
                    'limit' => $this->config->module_nkorgextstats_link_compress
                ]);
            }
        }

        $articleList = new \fpcm\model\articles\articlelist();
        $minMax = $articleList->getMinMaxDate();

        $fn = 'fetch' . ucfirst($source);
        if (!method_exists($counter, $fn)) {
            $this->view->render();
            return true;
        }

        $values = call_user_func([$counter, $fn], $start, $stop, $chartMode, $sortType, $search);
        $this->view->assign('chart', $chart);
        $this->view->assign('notfound', empty($values) ? true : false);
        $this->view->assign('minDate', date('Y-m-d', $minMax['minDate']));

        $this->getDataview($values, $isLinks, $source);

        $this->view->addJsVars([
            'extStats' => [
                'delList' => $source,
                'chart' => $counter->getChart(),
                'hasList' => $isLinks && isset($values['listValues']),
                'showMode' => $hideMode ? false : true,
                'showDate' => $isLinks,
            ]
        ]);

        $this->view->addJslangVars([
            $this->addLangVarPrefix('HITS_LIST_LINK'),
            $this->addLangVarPrefix('HITS_LIST_COUNT'),
            $this->addLangVarPrefix('HITS_LIST_IP'),
            $this->addLangVarPrefix('HITS_LIST_USERAGENT'),
            $this->addLangVarPrefix('HITS_LIST_LATEST'),
        ]);

        $jsF = $chart->getJsFiles();
        $jsF[1] = \fpcm\view\view::ROOTURL_CORE_JS . $jsF[1];

        $this->view->addJsFiles($jsF);
        $this->view->addCssFiles($chart->getCssFiles());

        $this->view->addTabs('extstats', [
            (new \fpcm\view\helper\tabItem('stats'))
                ->setText( $this->language->translate(array_search($source, $dataSource)) . ' ' . (!$hideMode && $modeStr ? $this->language->translate($this->addLangVarPrefix('BY'.$modeStr)) : '') )
                ->setModulekey($this->getModuleKey())
                ->setFile(\fpcm\view\view::PATH_MODULE . 'index' )
        ]);

        $this->view->addFromModule(['module.js']);
        $this->view->setFormAction('extstats/statistics');
        $this->view->render();
        return true;
    }

    private function getSettings(&$source, &$chartType, &$chartMode, &$modeStr, &$start, &$stop, &$sortType, &$search)
    {
        $source = $this->request->fromPOST('source');
        if ($source === null || !trim($source)) {
            $source = $this->config->module_nkorgextstats_show_visitors
                    ? \fpcm\modules\nkorg\extstats\models\counter::SRC_VISITORS
                    : \fpcm\modules\nkorg\extstats\models\counter::SRC_ARTICLES;
        }

        $search = $this->request->fromPOST('search');

        $chartType = $this->request->fromPOST('chartType');
        if ($chartType === null || !trim($chartType)) {
            $chartType = 'bar';
        }

        $chartMode = $this->request->fromPOST('chartMode', [
            \fpcm\model\http\request::FILTER_CASTINT
        ]);

        $sortType = $this->request->fromPOST('sortType', [
            \fpcm\model\http\request::FILTER_CASTINT
        ]);

        if ($chartMode === null || !trim($chartMode)) {
            $chartMode = $this->config->module_nkorgextstats_show_visitors
                    ? \fpcm\modules\nkorg\extstats\models\counter::MODE_DAY
                    : \fpcm\modules\nkorg\extstats\models\counter::MODE_MONTH;
        }

        $modeStr = $chartMode === \fpcm\modules\nkorg\extstats\models\counter::MODE_YEAR
                 ? 'YEAR'
                 : ($chartMode === \fpcm\modules\nkorg\extstats\models\counter::MODE_DAY ? 'DAY' : 'MONTH' );

        $start = $this->request->fromPOST('dateFrom');
        $stop = $this->request->fromPOST('dateTo');

        if ($start === null || !\fpcm\classes\tools::validateDateString($start)) {
            $start = date('Y-m-d', time() - $this->config->module_nkorgextstats_timespan_default * 86400);
        }

        if ($stop === null || trim($stop) && !\fpcm\classes\tools::validateDateString($stop)) {
            $stop = '';
        }

        return true;
    }

    private function getDataview($values, $isLinks, $source) : bool
    {
        if (!$isLinks || !isset($values['listValues'])) {
            return false;
        }

        $this->hasIpAgent = $source !== \fpcm\modules\nkorg\extstats\models\counter::SRC_REFERRER;

        $this->dv = new \fpcm\components\dataView\dataView('extendedstats-list');
        $this->view->addJsFiles($this->dv->getJsFiles());
        $this->view->addJsLangVars($this->dv->getJsLangVars());

        $this->dv->addColumns([
            (new \fpcm\components\dataView\column('btn', ''))->setSize('1')->setAlign('center'),
            (new \fpcm\components\dataView\column('link', $this->addLangVarPrefix('HITS_LIST_LINK'), 'flex-grow'))->setSize('4'),
            (new \fpcm\components\dataView\column('count', $this->addLangVarPrefix('HITS_LIST_LINK')))->setSize('1')->setAlign('center'),
            (new \fpcm\components\dataView\column('latest', $this->addLangVarPrefix('HITS_LIST_LINK')))->setSize('2')->setAlign('center'),
        ]);

        if ($this->hasIpAgent) {

            $this->dv->addColumns([
                (new \fpcm\components\dataView\column('ip', $this->addLangVarPrefix('HITS_LIST_IP')))->setSize('2')->setAlign('center'),
                (new \fpcm\components\dataView\column('useragent', $this->addLangVarPrefix('HITS_LIST_USERAGENT')))->setSize('2')->setAlign('center')
            ]);

        }

        array_walk($values['listValues'], [$this, 'addDvRow']);
        $this->view->addJsVars($this->dv->getJsVars());
        return true;
    }

    private function addDvRow(array $value)
    {
        if (!count($value)) {
            return false;
        }

        $btn = [
            (string) (new \fpcm\view\helper\button('entry_' . $value['intid']))->setText('GLOBAL_DELETE')->setIcon('trash')->setIconOnly(true)->setData(['entry' =>  $value['intid']])->setClass('fpcm-extstats-links-delete'),
            (string) (new \fpcm\view\helper\openButton('open_' . $value['intid']))->setText('GLOBAL_OPENNEWWIN')->setUrl($value['fullUrl'])->setTarget('_blank')->setRel('external')
        ];

        $row = [
            new \fpcm\components\dataView\rowCol('btn', implode(' ', $btn) , '', \fpcm\components\dataView\rowCol::COLTYPE_ELEMENT),
            new \fpcm\components\dataView\rowCol('link', $value['label'] ),
            new \fpcm\components\dataView\rowCol('count', $value['value'] ),
            new \fpcm\components\dataView\rowCol('latest', $value['latest'] ),
        ];

        if ($this->hasIpAgent) {
            $row[] = new \fpcm\components\dataView\rowCol('ip', $value['lastip'] ?? $this->language->translate('GLOBAL_NOTFOUND') );
            $row[] = new \fpcm\components\dataView\rowCol('useragent', $value['lastagent'] ?? $this->language->translate('GLOBAL_NOTFOUND') );
        }

        $this->dv->addRow(new \fpcm\components\dataView\row($row));
        return true;
    }

}
