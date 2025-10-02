<?php

namespace fpcm\modules\nkorg\extstats\models;

class countVisit extends dbObj {

    const TABLE = 'module_nkorgextstats_counts_visits';

    protected $countdt = 0;
    protected $countunique = 0;
    protected $counthits = 0;

    public function __construct()
    {
        $this->table = self::TABLE;
        parent::__construct();
        $this->countdt = (new \DateTime())->setTime(0, 0 ,0)->getTimestamp();
        $this->init();
    }
    
    public function init()
    {
        $data = $this->dbcon->selectFetch(
            (new \fpcm\model\dbal\selectParams($this->table))
            ->setWhere('countdt = ?')
            ->setParams([$this->countdt])
        );

        if (!$data) {
            return false;
        }

        $this->objExists = true;
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getCountUnique() {
        return $this->countunique;
    }

    public function getCountHits() {
        return $this->counthits;
    }

    public function setCountUnique($countunique) {
        $this->countunique = $countunique;
        return $this;
    }

    public function setCountHits($counthits) {
        $this->counthits = $counthits;
        return $this;
    }

    public function updateUnique()
    {
        if (!$this->config->module_nkorgextstats_calc_unique) {
            return true;
        }
        
        return \fpcm\classes\loader::getObject('\fpcm\model\http\request')->fromCookie('extstatsts') ? false : true;
    }

}