<?php

namespace fpcm\modules\nkorg\extstats\models;

class dbObj extends \fpcm\model\abstracts\dataset {

    protected function getEventModule(): string
    {
        return '';
    }

    public function save()
    {
        if (!$this->dbcon->insert($this->table, $this->getPreparedSaveParams())) {
            return false;
        }

        $this->id = $this->dbcon->getLastInsertId();
        return $this->id;
    }

    public function update()
    {
        $params = $this->getPreparedSaveParams();        
        $params[] = $this->getId();

        if ($this->dbcon->update($this->table, array_slice(array_keys($params), 0, -1), array_values($params), 'id = ?')) {
            return false;
        }

        return true;
    }
}
