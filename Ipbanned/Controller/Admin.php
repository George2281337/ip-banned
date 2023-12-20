<?php

namespace Box\Mod\Ipbanned\Controller;

use Box\Mod\System\Service;

class Admin implements \FOSSBilling\InjectionAwareInterface
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

    public function fetchNavigation()
    {
        return [
            'group' => [
                'index' => 200,
                'location' => 'ipbanned',
                'label' => __trans('IP Ban list'),
                'uri' => $this->di['url']->adminLink('ipbanned'),
                'class' => 'ip-banned',
                'sprite_class' => 'dark-sprite-icon sprite-users',
            ],
            'subpages' => [
                [
                    'location' => 'ipbanned',
                    'label' => __trans('Overview'),
                    'uri' => $this->di['url']->adminLink('ipbanned'),
                    'index' => 100,
                    'class' => '',
                ],
            ],
        ];
    }

    public function register(\Box_App &$app)
    {
        $app->get('/ipbanned', 'get_index', [], static::class);
        $app->get('/ipbanned/delete/:id', 'delete', ['id' => '[0-9]+'], static::class);
    }

    public function delete(\Box_App $app, $id)
    {
        /**
         * @var Service $systemService
         */
        $systemService = $this->di['mod_service']('system');
        try {
            $ipAttempt = $this->di['db']->getExistingModelById('IpAttempts', $id, 'IP Address not found');
            $this->di['db']->trash($ipAttempt);
            $systemService->setAlertsMessages(__trans('IP address delete from ban list'),'success');
        } catch (\Exception $exception) {
            $systemService->setAlertsMessages($exception->getMessage(),'error');
        }
            return $app->redirect('/ipbanned');

    }


    public function get_index(\Box_App $app)
    {
        return $app->render('mod_ipbanned_index');
    }

}