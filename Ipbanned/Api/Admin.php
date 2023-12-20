<?php

namespace Box\Mod\Ipbanned\Api;

use Box\Mod\System\Service;

class Admin extends \Api_Abstract
{
    public function get_ban_list($data)
    {
        $per_page = $data['per_page'] ?? $this->di['pager']->getPer_page();
        [$sql, $params] = $this->getService()->getSearchQuery($data);
        $pager = $this->di['pager']->getSimpleResultSet($sql, $params, $per_page);

        return $pager;
    }

    public function delete($data)
    {
        $id = $data['id'];
        $ipAttempt = $this->di['db']->getExistingModelById('IpAttempts', $id, 'IP Address not found');
        $this->di['db']->trash($ipAttempt);
        return true;
    }
}