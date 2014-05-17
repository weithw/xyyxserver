<?php
namespace service;

use common,
    entity,
    ZPHP\Cache\Factory as ZCache,
    ZPHP\Core\Config as ZConfig;
class Group extends Base
{
    public function __construct()
    {
        $this->dao = common\loadClass::getDao('Group');
    }

    public function buildGroup($phone, $groupname)
    {
        $config = ZConfig::getField('cache', 'net');
        $cacheHelper = ZCache::getInstance($config['adapter'], $config);
        $builderID = $cacheHelper->hgetptoi($phone);

        if($this->fetchOne($groupname)) {
            return common\Utils::msgFormat(0,"Group exists!");         
        }
        $result = $this->add(array('groupname' => "{$groupname}",
                                'builderID' => "{$builderID}",
                            )
        );
        if (empty($result))
            return common\Utils::msgFormat(0,"Build Failed!");
        else {
            $this->joinGroup($phone, $groupname);
            return common\Utils::msgFormat(1,"Build Success!");
        }
    }

    public function delGroup($phone, $groupname)
    {
        $config = ZConfig::getField('cache', 'net');
        $cacheHelper = ZCache::getInstance($config['adapter'], $config);
        $builderID = $cacheHelper->hgetptoi($phone);

        if (!$builderID)
            return common\Utils::msgFormat(0,"Delte Group Failed!");
        if($this->fetchOne($groupname)) {
            $result = $this->del(array('groupname' => "{$groupname}",
                                    'builderID' => "{$builderID}",
                                )
            );

            if (!$result)
                return common\Utils::msgFormat(0,"Delte Group in mysql Failed!");
            else {
                $members = $this->groupMember($groupname);
                foreach ($members as $key => $member) {
                    $this->quitGroup($member, $groupname);   //$member:phone num
                }
                $config = ZConfig::getField('cache', 'net');
                $cacheHelper = ZCache::getInstance($config['adapter'], $config);
                $key = "{$groupname}_member";
                $result = $cacheHelper->delete($key);               
                if ($result)
                    return common\Utils::msgFormat(0,"Delete Group in redis Failed!");
                return common\Utils::msgFormat(1,"Delete Group Success!");
            }
        }
        else{
            return common\Utils::msgFormat(0,"Group does't exist!");
        }
    }

    public function groupInfo($groupname)
    {
        $group = $this->fetchOne($groupname);
        if (empty($group))
            return common\Utils::msgFormat(0,"Group:{$groupname} does't exist!");
        else {
            $group["flag"] = "[HTTP_SSDUTXYYX]";
            $group["member"] = $this->groupMember($groupname);
            return json_encode($group);
        }
    }

    public function groupMember($groupname)
    {
        $config = ZConfig::getField('cache', 'net');
        $cacheHelper = ZCache::getInstance($config['adapter'], $config);
        $key = "{$groupname}_member";
        $result = $cacheHelper->smembers($key);       //get the set of groups which key is $key
        if ($result){
            $groupmember = array();
            foreach ($result as $key => $value) {
                $member = $cacheHelper->hgetitop($value);   //返回组成员(phone num)
                $groupmember[] = $member;
            }  
            return $groupmember;
        } else {
            return null;
        }
    }

    public function joinGroup($phone, $groupname)
    {
        if(!$this->fetchOne($groupname)) {
            return common\Utils::msgFormat(0,"Group does't exist!");
        }   
            
        $config = ZConfig::getField('cache', 'net');
        $cacheHelper = ZCache::getInstance($config['adapter'], $config);
        $userID = $cacheHelper->hgetptoi($phone);
        if (!$userID)
            return common\Utils::msgFormat(0,"UserID doesn't exist!");
        $key_user = "{$userID}_group";
        $key_group = "{$groupname}_member";
        if ($cacheHelper->sismember($key_user,$groupname)) {
            return common\Utils::msgFormat(0,"The user:{$phone} is in {$groupname}!");
        } 
        if ($cacheHelper->sismember($key_group,$userID)) {
            return common\Utils::msgFormat(0,"The user:{$phone} is in {$groupname}!");
        } 
        $result_user = $cacheHelper->sadd($key_user,$groupname); 
        $cacheHelper->sadd($key_user,"need to refresh");   
        $result_group = $cacheHelper->sadd($key_group,$userID);

        if (empty($result_user) || empty($result_group))
            return common\Utils::msgFormat(0,"Join {$groupname} Failed!");
        else 
            return common\Utils::msgFormat(1,"Join {$groupname} Success!");        
    }

    public function quitGroup($phone, $groupname)
    {
        $config = ZConfig::getField('cache', 'net');
        $cacheHelper = ZCache::getInstance($config['adapter'], $config);
        $userID = $cacheHelper->hgetptoi($phone);
        $key_user = "{$userID}_group";
        $key_group = "{$groupname}_member";
        if ($cacheHelper->sismember($key_user,$groupname) 
            && $cacheHelper->sismember($key_group,$userID)) {
            $result_user = $cacheHelper->srem($key_user, $groupname);
            $result_group = $cacheHelper->srem($key_group, $userID);
            if (empty($result_user) || empty($result_group))
                return common\Utils::msgFormat(0,"Quit {$groupname} Failed!");
            else
                return common\Utils::msgFormat(1,"Quit {$groupname}Success!");
        }  
        return common\Utils::msgFormat(0,"You are not in {$groupname}!");      
    }
} 