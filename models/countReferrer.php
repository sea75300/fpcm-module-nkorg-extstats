<?php

namespace fpcm\modules\nkorg\extstats\models;

class countReferrer extends dbObj {

    use \fpcm\module\tools;
    
    const TABLE = 'module_nkorgextstats_counts_referrer';

    protected $refurl = '';
    protected $refhash = '';
    protected $counthits = 0;
    protected $lasthit = 0;

    public function __construct()
    {
        $this->table = self::TABLE;
        parent::__construct();
        $this->url = $_SERVER['HTTP_REFERER'] ?? 'localhost';

        /* @var $request \fpcm\model\http\request */
        $request = \fpcm\classes\loader::getObject('\fpcm\model\http\request');

        $this->refurl = $request->filter($this->url, [
            \fpcm\model\http\request::FILTER_STRIPTAGS,            
            \fpcm\model\http\request::FILTER_TRIM,            
        ]);
        
        $this->refhash = \fpcm\classes\tools::getHash($this->refurl);        
        $this->init();
    }
    
    public function init()
    {
        $data = $this->dbcon->selectFetch(
            (new \fpcm\model\dbal\selectParams($this->table))
            ->setWhere('refhash = ?')
            ->setParams([$this->refhash])
        );

        if (!$data) {
            return false;
        }

        $this->objExists = true;
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function isExternal()
    {
        if (!trim($this->url) || $this->url === 'localhost') {
            return false;
        }
        
        $parsed = parse_url($this->url);
        if ($parsed === false || !isset($parsed['host'])) {
            return false;
        }
        
        $baseParsed = parse_url($this->getObject()->getOption('url_base'));
        if (!isset($baseParsed['host'])) {
            return true;
        }

        return !($baseParsed['host'] === $parsed['host']);
    }

    public function getCountHits()
    {
        return $this->counthits;
    }

    public function getLastHit()
    {
        return $this->lasthit;
    }

    public function setCountHits($counthits)
    {
        $this->counthits = $counthits;
        return $this;
    }

    public function setLastHit($lasthit)
    {
        $this->lasthit = $lasthit;
        return $this;
    }

}