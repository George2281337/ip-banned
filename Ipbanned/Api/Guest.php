<?php

namespace Box\Mod\Ipbanned\Api;

class Guest extends \Api_Abstract
{


    public function is_banned(){
        $ipAddress = $this->di['db']->findOne('IpAttempts','ip_address=:ip',['ip'=>$this->ip]);
        if ($ipAddress){
            return $ipAddress->is_banned;
        }else{
            return  false;
        }
    }
}