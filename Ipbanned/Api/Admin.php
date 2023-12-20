<?php

namespace Box\Mod\Ipbanned\Api;

class Admin extends \Api_Abstract
{
    public function get_ban_list($data){
        $per_page = $data['per_page'] ?? $this->di['pager']->getPer_page();
        [$sql, $params] = $this->getService()->getSearchQuery($data);
        $pager = $this->di['pager']->getSimpleResultSet($sql, $params, $per_page);

        return $pager;
    }
}