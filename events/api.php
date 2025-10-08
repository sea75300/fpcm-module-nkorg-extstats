<?php

namespace fpcm\modules\nkorg\extstats\events;

use fpcm\classes\loader;
use fpcm\model\system\session;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

final class api extends \fpcm\module\api {

    /**
     * @var session
     */
    protected $session;

    /**
     * @var bool
     */
    protected $excludeCount = null;

    public function init() : void
    {
        $this->session = loader::getObject('\fpcm\model\system\session');
        $this->excludeCount = $this->excludeCount() || $this->session->exists();
    }
    
    final public function countAll()
    {
        $this->visitorsCount();
        $this->linksCount();
        $this->referrerCount();
    }

    final public function visitorsCount()
    {
        if ($this->excludeCount) {
            return true;
        }

        $countObj = new \fpcm\modules\nkorg\extstats\models\countVisit();

        $fn = 'save';
        if ($countObj->exists()) {
            $countObj->init();
            $fn = 'update';
        }

        if ($countObj->updateUnique()) {
            $countObj->setCountUnique($countObj->getCountUnique() + 1);
        }

        $countObj->setCountHits($countObj->getCountHits() + 1);
        call_user_func([$countObj, $fn]);

        if (!\fpcm\classes\loader::getObject('\fpcm\model\system\config')->module_nkorgextstats_calc_unique) {
            return true;
        }

        /* @var $config \fpcm\model\system\config */
        $duration = (int) \fpcm\classes\loader::getObject('\fpcm\model\system\config')->module_nkorgextstats_cookie_duration;
        if (!$duration || $duration < 600) {
            $duration = 3600;
        }

        $cookie = new \fpcm\model\http\cookie('extstatsts');
        $cookie->setExpires((time() + $duration));
        $cookie->set('nkorg-extstats-cookie');
        return true;
    }

    final public function linksCount()
    {
        if ($this->excludeCount) {
            return true;
        }

        $countObj = new \fpcm\modules\nkorg\extstats\models\countLink();

        $fn = 'save';
        if ($countObj->exists()) {
            $countObj->init();
            $fn = 'update';
        }

        $countObj->setCountHits($countObj->getCountHits() + 1);
        $countObj->setLastHit(time());
        $countObj->setLastip(\fpcm\classes\loader::getObject('\fpcm\model\http\request')->getIp());
        
        $usrAgent = filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_URL) ?? '';
        
        $countObj->setLastagent($usrAgent ? substr($usrAgent, 0, 128) : '');
        call_user_func([$countObj, $fn]);
        return true;
    }

    final public function referrerCount()
    {
        if ($this->excludeCount) {
            return true;
        }

        $countObj = new \fpcm\modules\nkorg\extstats\models\countReferrer();
        if (!$countObj->isExternal()) {
            return true;
        }

        $fn = 'save';
        if ($countObj->exists()) {
            $countObj->init();
            $fn = 'update';
        }

        $countObj->setCountHits($countObj->getCountHits() + 1);
        $countObj->setLastHit(time());
        call_user_func([$countObj, $fn]);
        return true;
    }

    private function excludeCount()
    {
        if ($this->excludeCount !== null) {
            return $this->excludeCount;
        }
        
        $base = dirname(__DIR__).DIRECTORY_SEPARATOR.'crawlerDetect'.DIRECTORY_SEPARATOR;

        require_once $base.'Fixtures/AbstractProvider.php';
        require_once $base.'Fixtures/Crawlers.php';
        require_once $base.'Fixtures/Exclusions.php';
        require_once $base.'Fixtures/Headers.php';
        require_once $base.'CrawlerDetect.php';

        return (new CrawlerDetect())->isCrawler();
    }

}
