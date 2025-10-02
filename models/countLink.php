<?php

namespace fpcm\modules\nkorg\extstats\models;

class countLink extends dbObj {

    const TABLE = 'module_nkorgextstats_counts_links';

    protected $url = '';
    protected $urlhash = '';
    protected $counthits = 0;
    protected $lasthit = 0;
    protected $lastip = '';
    protected $lastagent = '';

    public function __construct()
    {
        $this->table = self::TABLE;
        parent::__construct();
        $this->url = $_SERVER['REQUEST_URI'] ?? 'localhost';
        if ($this->url === '/') {
            $this->url = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ??'localhost' );
        }

        /* @var $request \fpcm\model\http\request */
        $request = \fpcm\classes\loader::getObject('\fpcm\model\http\request');

        $this->url = $request->filter($this->url, [
            \fpcm\model\http\request::FILTER_STRIPTAGS,            
            \fpcm\model\http\request::FILTER_TRIM,            
        ]);
        
        $this->urlhash = \fpcm\classes\tools::getHash($this->url);        
        $this->init();
    }
    
    public function init()
    {
        $data = $this->dbcon->selectFetch(
            (new \fpcm\model\dbal\selectParams($this->table))
            ->setWhere('urlhash = ?')
            ->setParams([$this->urlhash])
        );

        if (!$data) {
            return false;
        }

        $this->objExists = true;
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }
    
    public function getCountHits() {
        return $this->counthits;
    }

    public function getLastHit() {
        return $this->lasthit;
    }

    public function setCountHits($counthits) {
        $this->counthits = $counthits;
        return $this;
    }

    public function setLastHit($lasthit) {
        $this->lasthit = $lasthit;
        return $this;
    }

    function setLastip($lastip) {
        $this->lastip = $lastip;
    }

    function setLastagent($lastagent) {
        $this->lastagent = $lastagent;
    }



}