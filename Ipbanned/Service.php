<?php

namespace Box\Mod\Ipbanned;

use FOSSBilling\InjectionAwareInterface;

class Service implements InjectionAwareInterface
{

    protected ?\Pimple\Container $di;

    public function setDi(\Pimple\Container $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?\Pimple\Container
    {
        return $this->di;
    }

    public function install()
    {
        $sql = '
         create table `ip_attempts` (
                    `id` bigint unsigned not null auto_increment primary key, 
                    `ip_address` varchar(255) not null,
                    `attempts` int not null default 0,
                    `is_ban` tinyint(1) not null default 0,
                    `expires_at` datetime null,
                    `updated_at` datetime not null,
                    `created_at` datetime not null
                    ) default character set utf8mb4 collate utf8mb4_general_ci;
';
        $this->di['db']->exec($sql);

        return true;
    }

    public function getSearchQuery($data, $sql = 'SELECT *')
    {
        $sql .= ' FROM ip_attempts';
        $ipAddress = (isset($data['search']) && !empty($data['search'])) ? $data['search'] : null;
        $where = [];
        $params = [];
        if ($ipAddress) {
            $where[] = 'ip_address like :ip ';
            $params[':ip'] = '%' . $ipAddress . '%';
        }
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql = $sql . ' ORDER BY created_at desc';

        return [$sql, $params];
    }


    public function uninstall()
    {
        $sql = '
DROP TABLE IF EXISTS `ip_attempts`;
    ';
        $this->di['db']->exec($sql);

    }

    public static function onBeforeClientLogin(\Box_Event $event)
    {
        $di = $event->getDi();
        $parameters = $event->getParameters();
        $ipAddress = $parameters['ip'];
        $ipAttempts = $di['db']->findOne('IpAttempts', 'ip_address=:ip', ['ip' => $ipAddress]);
        if ($ipAttempts) {
            $date = time();
            $expires = strtotime($ipAttempts->expires_at);
            if ($ipAttempts->is_ban == 1) {
                if ($date >= $expires) {
                    $di['db']->trash($ipAttempts);
                }
                throw new \Box_Exception('Your IP address is blocked. Please try again later');
            }
        }
    }

    public static function onEventClientLoginFailed(\Box_Event $event)
    {
        $di = $event->getDi();
        $extension = $di['mod_service']('extension');
        $config = $extension->getConfig('mod_ipbanned');
        $parameters = $event->getParameters();
        $daysBaned = $config['ban_day'];
        $ipAddress = $parameters['ip'];
        $whiteList = explode(',', trim($config['white_list']));
        if (in_array($ipAddress, $whiteList)) {
            $ipAttempts = $di['db']->findOne('IpAttempts', 'ip_address=:ip', ['ip' => $ipAddress]);
            if (!is_null($ipAttempts)) {
                if ($ipAttempts->is_ban == 1) {
                    throw new \Box_Exception('Your IP address is blocked. Please try again later');
                }
                if ($ipAttempts->attempts >= $config['attempts']) {
                    $ipAttempts->is_ban = 1;
                    $ipAttempts->expires_at = date('Y-m-d H:i:s', strtotime("+ $daysBaned day"));
                    $di['db']->store($ipAttempts);
                    throw new \Box_Exception('Your IP address is blocked. Please try again later');
                }
                $ipAttempts->attempts += 1;
                $ipAttempts->updated_at = date('Y-m-d H:s:i');
            } else {
                $ipAttempts = $di['db']->dispense('IpAttempts');
                $ipAttempts->ip_address = $ipAddress;
                $ipAttempts->attempts = 1;
                $ipAttempts->updated_at = date('Y-m-d H:s:i');
                $ipAttempts->created_at = date('Y-m-d H:s:i');
            }
            $di['db']->store($ipAttempts);
        }
    }

}
