<?php

namespace fpcm\modules\nkorg\extstats\controller;

final class deleteentry extends \fpcm\controller\abstracts\module\ajaxController {

    public function request()
    {
        $this->response = new \fpcm\model\http\response;
        
        $id = $this->request->fromPOST('id', [
            \fpcm\model\http\request::FILTER_CASTINT
        ]);
        
        $obj= $this->request->fromPOST('obj');

        if (!$id || !$obj) {
            $this->response->setReturnData( new \fpcm\model\http\responseData(0) )->fetch();
        }
        
        switch ($obj) {
            case 'referrer' :
                $del = (new \fpcm\modules\nkorg\extstats\models\counter())->deleteReferrerEntry($id)  ? 1 : 0;
                break;
            case 'links' :
                $del = (new \fpcm\modules\nkorg\extstats\models\counter())->deleteLinkEntry($id)  ? 1 : 0;
                break;
            default:
                $del = 0;
                break;
        }

        $this->response->setReturnData( new \fpcm\model\http\responseData($del) )->fetch();
    }

}
