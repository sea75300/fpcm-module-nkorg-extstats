<?php

namespace fpcm\modules\nkorg\extstats\models;

class counter extends \fpcm\model\abstracts\tablelist {

    use \fpcm\module\tools;

    const MODE_MONTH = 1;
    const MODE_YEAR = 2;
    const MODE_DAY = 3;
    const SRC_ARTICLES = 'articles';
    const SRC_COMMENTS = 'comments';
    const SRC_SHARES = 'shares';
    const SRC_FILES = 'files';
    const SRC_VISITORS = 'visitors';
    const SRC_LINKS = 'links';
    const SRC_REFERRER = 'referrer';
    const SORT_COUNT = 0;
    const SORT_DATE = 1;
    const SORT_LINK = 2;
    const SORT_REFERER = 3;

    const LINK_MAX_GRAPH = 15;

    protected $mode;
    protected $months;
    protected $table;
    protected $createTimeVar = 'createtime';

    protected $chart;

    public function setChart(\fpcm\components\charts\chart $chart): void
    {
        $this->chart = $chart;
    }

    public function getChart() : \fpcm\components\charts\chart
    {
        return $this->chart;
    }

    public function deleteLinkEntry($id)
    {
        $result = $this->dbcon->delete(countLink::TABLE, 'id = ?', [
            (int) $id
        ]);

        return $result ? true : false;
    }

    public function deleteReferrerEntry($id)
    {
        $result = $this->dbcon->delete(countReferrer::TABLE, 'id = ?', [
            (int) $id
        ]);

        return $result ? true : false;
    }

    public function fetchArticles($start, $stop, $mode = 1)
    {
        $this->table = \fpcm\classes\database::tableArticles;
        return $this->fetchData($start, $stop, $mode);
    }

    public function fetchComments($start, $stop, $mode = 1)
    {
        $this->table = \fpcm\classes\database::tableComments;
        return $this->fetchData($start, $stop, $mode);
    }

    public function fetchFiles($start, $stop, $mode = 1)
    {
        $this->table = \fpcm\classes\database::tableFiles;
        $this->createTimeVar = 'filetime';
        return $this->fetchData($start, $stop, $mode);
    }

    public function fetchShares($start, $stop, $mode = 1)
    {
        $where = '1=1';

        $where .= (trim($start) ? ' AND ' . $this->createTimeVar . ' >= ' . strtotime($start) : '');
        $where .= (trim($stop) ? ' AND ' . $this->createTimeVar . ' < ' . strtotime($stop) : '');

        $values = $this->dbcon->selectFetch(
            (new \fpcm\model\dbal\selectParams())
                ->setTable(\fpcm\classes\database::tableArticles)
                ->setItem("id, title")
                ->setWhere($where)
                ->setFetchAll(true)
        );

        if (!$values) {
            return [];
        }

        $articles = [];
        foreach ($values as $value) {
            $articles[$value->id] = $value->title;
        }

        $values = $this->dbcon->selectFetch(
            (new \fpcm\model\dbal\selectParams())
                ->setTable(\fpcm\classes\database::tableShares)
                ->setItem("sum(sharecount) AS counted, article_id")
                ->setWhere("article_id IN (". implode(',', array_keys($articles)).") GROUP BY article_id")
                ->setFetchAll(true)
        );

        if (!$values) {
            return [];
        }


        $data = [];
        foreach ($values as $value) {

            if (!isset($articles[$value->article_id])) {
                continue;
            }

            $len = strlen($articles[$value->article_id]);

            $data['labels'][] = ( $len >= 20 ? substr($articles[$value->article_id], 0, 20).'...' : $articles[$value->article_id] ). ' ('.$value->article_id.')';
            $data['values'][] = (string) $value->counted;
            $data['colors'][] = \fpcm\components\charts\chartItem::getRandomColor();
        }

        $this->chart->setLabels($data['labels']);
        $this->chart->setValues((new \fpcm\components\charts\chartItem($data['values'], $data['colors']))->setFill(true));

        return $data['labels'];
    }

    public function fetchVisitors($start, $stop, $mode = 1)
    {
        $this->table = countVisit::TABLE;
        $this->createTimeVar = 'countdt';
        $this->mode = (int) $mode;
        $this->months = $this->language->translate('SYSTEM_MONTHS');

        $where = '1=1';
        $params = [];

        $where .= (trim($start) ? ' AND ' . $this->createTimeVar . ' >= ?'  : '');
        $where .= (trim($stop) ? ' AND ' . $this->createTimeVar . ' < ?' : '');
        $where .= ' GROUP BY dtstr '.$this->dbcon->orderBy(['dtstr ASC']);

        if (trim($start)) {
            $params[] = strtotime($start);
        }

        if (trim($stop)) {
            $params[] = strtotime($stop);
        }

        $items = 'SUM(countunique) AS sumunique, SUM(counthits) as sumhits, count(id) AS countedds, '. call_user_func([$this, 'getSelectItem' . ucfirst($this->dbcon->getDbtype())]);

        $values = $this->dbcon->selectFetch(
            (new \fpcm\model\dbal\selectParams($this->table))
                ->setItem($items)
                ->setWhere($where)
                ->setFetchAll(true)
                ->setParams($params)
        );

        if (!$values) {
            return [];
        }

        $data = [];
        foreach ($values as $value) {
            $data['labels'][] = $this->getLabel($value->dtstr);
            $data['unique']['values'][] = (string) ($value->sumunique ?? $value->countunique);
            $data['unique']['colors'][] = \fpcm\components\charts\chartItem::getRandomColor();
            $data['hits']['values'][] = (string) ($value->sumhits ?? $value->counthits);
            $data['hits']['colors'][] = \fpcm\components\charts\chartItem::getRandomColor();
        }

        $unique = $this->getObject()->getOption('calc_unique');
        if (!$unique) {
            $data['unique'] = [];
        }

        $this->chart->setLabels($data['labels']);

        $index  = 0;
        if ($unique) {
            $this->chart->setValues((new \fpcm\components\charts\chartItem($data['unique']['values'], $data['unique']['colors']))->setFill(true), 0);
            $index++;
        }

        $this->chart->setValues((new \fpcm\components\charts\chartItem($data['hits']['values'], $data['hits']['colors']))->setFill(true), $index);
        return $data['labels'];
    }

    public function fetchLinks($start, $stop, $mode = 1, $sort = 0, $search = '')
    {
        $this->table = countLink::TABLE;

        $where = '1=1 ';
        $this->createTimeVar = 'lasthit';

        $params = [];

        $this->getTmeQuery($start, $stop, $where, $params);

        if ($search !== null && trim($search)) {
            $where .= sprintf(' AND (url %s ? OR lastip %s ? OR lastagent %s ?) ', $this->dbcon->dbLike(), $this->dbcon->dbLike(), $this->dbcon->dbLike());
            $params = array_pad($params, count($params)+3, '%'.$search.'%');
        }

        $this->getOrder($sort, $where);

        $values = $this->dbcon->selectFetch(
            (new \fpcm\model\dbal\selectParams($this->table))
                ->setItem('url, counthits, lasthit, lastagent, lastip, id')
                ->setWhere($where)
                ->setParams($params)
                ->setFetchAll(true)
        );

        if (!$values) {
            return [];
        }

        $baseParsed = parse_url($this->getObject()->getOption('url_base'));

        $data = [];
        foreach ($values as $value) {

            $val = (string) ($value->counthits ?? 0);
            $parsed = parse_url($value->url);

            $orgUrl = $value->url;

            if (!is_array($parsed)) {
                $parsed = [];
            }

            if (!isset($parsed['path'])) {
                $parsed['path'] = '';
            }

            if (isset($parsed['path']) && count($parsed) === 1) {

                $tmpEx = explode('.', $parsed['path'], 3);
                $tmpExC = count($tmpEx);

                if (str_contains($parsed['path'], $baseParsed['host'])) {
                    $parsed['host'] = $baseParsed['host'];
                    $parsed['path'] = '';
                }
                elseif (
                    ( $tmpExC === 3 && str_contains($baseParsed['host'], $tmpEx[1]) ) ||
                    ( $tmpExC === 2 && str_contains($baseParsed['host'], $tmpEx[0]) )
                ) {
                    $parsed['host'] = $parsed['path'];
                    $parsed['path'] = '';
                }
            }

            $value->url = $parsed['scheme'] ?? $baseParsed['scheme'];
            $value->url .= '://';
            $value->url .= $parsed['user'] ?? '';
            $value->url .= isset($parsed['pass']) ? ':'.$parsed['pass'] : '';
            $value->url .= isset($parsed['user']) || isset($parsed['pass']) ? '@' : '';
            $value->url .= $parsed['host'] ?? (strpos($parsed['path'], $baseParsed['host']) === false ? $baseParsed['host'] : '');
            $value->url .= isset($parsed['port']) ? ':' . $parsed['port'] : '';
            $value->url .= $parsed['path'];
            $value->url .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
            $value->url .= $parsed['fragment'] ?? '';

            $data['labels'][] = strlen($value->url) >= 40 ? substr($value->url, 0, 40) . '...' : $value->url;
            $data['values'][] = $val;
            $data['colors'][] = \fpcm\components\charts\chartItem::getRandomColor();

            $label = sprintf(
                '%s<br><span class="fs-6 font-monospac text-secondary">%s</span>',
                (string) new \fpcm\view\helper\escape($value->url),
                (string) new \fpcm\view\helper\escape($orgUrl)
            );

            $data['listValues'][] = [
                'src' => \fpcm\modules\nkorg\extstats\models\counter::SRC_LINKS,
                'label' => $label,
                'latest' => date($this->config->system_dtmask, $value->lasthit),
                'lastip' => $value->lastip,
                'lastagent' => $value->lastagent,
                'value' => $val,
                'fullUrl' => $value->url,
                'intid' => $value->id
            ];
        }

        $data['labels'] = array_slice($data['labels'], 0, self::LINK_MAX_GRAPH);
        $data['values'] = array_slice($data['values'], 0, self::LINK_MAX_GRAPH);
        $data['colors'] = array_slice($data['colors'], 0, self::LINK_MAX_GRAPH);

        $this->chart->setLabels($data['labels']);
        $this->chart->setValues((new \fpcm\components\charts\chartItem($data['values'], $data['colors']))->setFill(true));

        return $data;
    }

    public function fetchReferrer($start, $stop, $mode = 1, $sort = 0, $search = '')
    {
        $this->table = countReferrer::TABLE;

        $where = '1=1 ';
        $this->createTimeVar = 'lasthit';

        $params = [];

        $this->getTmeQuery($start, $stop, $where, $params);

        if ($search !== null && trim($search)) {
            $where .= sprintf(' AND (refurl %s ?) ', $this->dbcon->dbLike());
            $params[] = '%'.$search.'%';
        }

        $this->getOrder($sort === self::SORT_LINK ? self::SORT_REFERER : $sort, $where);

        $values = $this->dbcon->selectFetch(
            (new \fpcm\model\dbal\selectParams($this->table))
                ->setItem('refurl, counthits, lasthit, id')
                ->setWhere($where)
                ->setParams($params)
                ->setFetchAll(true)
        );

        if (!$values) {
            return [];
        }

        $data = [];
        foreach ($values as $value) {

            $val = (string) ($value->counthits ?? 0);

            $data['labels'][] = strlen($value->refurl) >= 40 ? substr($value->refurl, 0, 40) . '...' : $value->refurl;
            $data['values'][] = $val;
            $data['colors'][] = \fpcm\components\charts\chartItem::getRandomColor();
            $data['listValues'][] = [
                'src' => \fpcm\modules\nkorg\extstats\models\counter::SRC_REFERRER,
                'label' => (string) new \fpcm\view\helper\escape($value->refurl),
                'latest' => date($this->config->system_dtmask, $value->lasthit),
                'value' => $val,
                'fullUrl' => $value->refurl,
                'intid' => $value->id
            ];
        }

        $data['labels'] = array_slice($data['labels'], 0, self::LINK_MAX_GRAPH);
        $data['values'] = array_slice($data['values'], 0, self::LINK_MAX_GRAPH);
        $data['colors'] = array_slice($data['colors'], 0, self::LINK_MAX_GRAPH);

        $this->chart->setLabels($data['labels']);
        $this->chart->setValues((new \fpcm\components\charts\chartItem($data['values'], $data['colors']))->setFill(true));

        return $data;
    }

    public function cleanupLinks()
    {
        $result = $this->dbcon->selectFetch(
            (new \fpcm\model\dbal\selectParams(countLink::TABLE))
                ->setItem('id')
                ->setWhere('1=1 '.$this->dbcon->orderBy(['counthits DESC']).' '.$this->dbcon->limitQuery((int) $this->config->module_nkorgextstats_link_compress, 0) )
                ->setFetchAll(true)
        );

        if (!$result || !count($result)) {
            return true;
        }

        $ids = [];
        foreach ($result as $row) {
            $ids[] = (int) $row->id;
        }

        if (!count($ids)) {
            return true;
        }

        return $this->dbcon->delete(
            countLink::TABLE,
            'id NOT IN ('.implode(',', $ids).')'
        );
    }

    private function fetchData($start, $stop, $mode)
    {

        if ($start === null) {
            $start = '';
        }

        if ($stop === null) {
            $stop = '';
        }

        $this->mode = (int) $mode;

        $hash = \fpcm\classes\tools::getHash(__METHOD__ . json_encode(func_get_args()));

        $where = '1=1';

        $where .= (trim($start) ? ' AND ' . $this->createTimeVar . ' >= ' . strtotime($start) : '');
        $where .= (trim($stop) ? ' AND ' . $this->createTimeVar . ' < ' . strtotime($stop) : '');

        $values = $this->dbcon->selectFetch(
            (new \fpcm\model\dbal\selectParams())
                ->setTable($this->table)
                ->setItem("count(id) AS counted, " . call_user_func([$this, 'getSelectItem' . ucfirst($this->dbcon->getDbtype())]))
                ->setWhere($where . ' GROUP BY dtstr ' . $this->dbcon->orderBy(['dtstr ASC']))
                ->setFetchAll(true)
        );

        if (!$values) {
            return [];
        }

        $this->months = $this->language->translate('SYSTEM_MONTHS');

        $data = [];
        foreach ($values as $value) {
            $data['labels'][] = $this->getLabel($value->dtstr);
            $data['values'][] = (string) $value->counted;
            $data['colors'][] = \fpcm\components\charts\chartItem::getRandomColor();
        }

        $this->chart->setLabels($data['labels']);
        $this->chart->setValues((new \fpcm\components\charts\chartItem($data['values'], $data['colors']))->setFill(true));

        return $data['labels'];
    }

    private function getLabel($data)
    {
        $data = explode('-', $data);
        $month = intval($data[1] ?? 0);

        switch ($this->mode) {
            case self::MODE_DAY :
                return $data[2] . '. ' . $this->months[$month] . ' ' . $data[0];
                break;
            case self::MODE_YEAR :
                return $data[0];
                break;
        }

        return $this->months[$month] . ' ' . $data[0];
    }

    private function getSelectItemMysql()
    {
        switch ($this->mode) {
            case self::MODE_DAY :
                return "DATE_FORMAT(FROM_UNIXTIME({$this->createTimeVar}), '%Y-%m-%d' ) AS dtstr";
                break;
            case self::MODE_YEAR :
                return "DATE_FORMAT(FROM_UNIXTIME({$this->createTimeVar}), '%Y' ) AS dtstr";
                break;
        }

        return "DATE_FORMAT(FROM_UNIXTIME({$this->createTimeVar}), '%Y-%m' ) AS dtstr";
    }

    private function getSelectItemPgsql()
    {
        switch ($this->mode) {
            case self::MODE_DAY :
                return "to_char(to_timestamp({$this->createTimeVar}), 'YYYY-MM-DD') AS dtstr";
                break;
            case self::MODE_YEAR :
                return "to_char(to_timestamp({$this->createTimeVar}), 'YYYY') AS dtstr";
                break;
        }

        return "to_char(to_timestamp({$this->createTimeVar}), 'YYYY-MM') AS dtstr";
    }

    /**
     *
     * @param string $start
     * @param string $stop
     * @param string $where
     * @param array $params
     * @return bool
     * @since nkorg/extstats 4.4.1
     */
    private function getTmeQuery($start, $stop, string &$where, array &$params) : bool
    {
        $where .= (trim($start) ? ' AND ' . $this->createTimeVar . ' >= ?'  : '');
        $where .= (trim($stop) ? ' AND ' . $this->createTimeVar . ' < ?' : '');

        if (trim($start)) {
            $params[] = strtotime($start);
        }

        if (trim($stop)) {
            $params[] = strtotime($stop);
        }

        return true;
    }

    private function getOrder($type, &$where)
    {
        switch ($type) {
            case self::SORT_DATE :
                $order = 'lasthit DESC';
                break;
            case self::SORT_LINK :
                $order = 'url';
                break;
            case self::SORT_REFERER :
                $order = 'refurl';
                break;
            default:
                $order = 'counthits DESC';
                break;
        }


        $where .= $this->dbcon->orderBy([$order]);
        return true;
    }

}
