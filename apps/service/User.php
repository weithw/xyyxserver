<?php
namespace service;

use common,
    entity,
    ZPHP\Cache\Factory as ZCache,
    ZPHP\Core\Config as ZConfig;
class User extends Base
{
    public function __construct()
    {
        $this->dao = common\loadClass::getDao('User');
    }
    
    public function checkUser($login_method, $password)
    {                                 
        $user = $this->fetchOne(array(   //根据手机号\用户名\微信号+密码查找用户信息,即登录
                $login_method[0]=>$login_method[1],
                "password"=>"{$password}",
            )
        );

        if(empty($user)) {
            return common\Utils::msgFormat(0,"Login Failed!");
        } else {
            $user["flag"] = "[HTTP_SSDUTXYYX]";
            return json_encode($user);
        }
    }

    public function addUser($phone, $username, $password, $sex)
    {
        $config = ZConfig::getField('cache', 'net');
        $cacheHelper = ZCache::getInstance($config['adapter'], $config);
        
        if($cacheHelper->hexists("phonetoid","{$phone}_ptoi") || 
            $this->fetchOne(array('phone'=>$phone)) ) {
            return common\Utils::msgFormat(0,"Phonenum already taken!");
        } 
        if($cacheHelper->hexists("nametoid","{$username}_ntoi") || 
            $this->fetchOne(array('username'=>$username)) ) {
            return common\Utils::msgFormat(0,"Username already taken!");
        } 
        $result = $this->add(array(
                                'phone' => "{$phone}",
                                'username' => "{$username}",
                                'password' => "{$password}",
                                'sex' => "{$sex}",
                            )
        );
        if (empty($result))
            return common\Utils::msgFormat(0,"Register Failure!");
        else {
            $userID = $this->fetchOne(array('phone'=>$phone))['ID'];   
            $cacheHelper->hsetitop($userID, $phone);
            $cacheHelper->hsetptoi($phone, $userID);
            $cacheHelper->hsetiton($userID, $username);
            $cacheHelper->hsetntoi($username, $userID);
            return common\Utils::msgFormat(1,"Register Success!");
        }
    }

    public function addFriend($yourphone, $add_method)   //添加好友(使用redis)
    {
        $config = ZConfig::getField('cache', 'net');
        $cacheHelper = ZCache::getInstance($config['adapter'], $config);
        $yourID = $cacheHelper->hgetptoi($yourphone);

        $key = "{$yourID}_friend";
        if ($add_method[0] == "friendname") {
            $friendID = $cacheHelper->hgetntoi($add_method[1]);
        } else if ($add_method[0] == "friendphone") {
            $friendID = $cacheHelper->hgetptoi($add_method[1]);
        }
        if (!$friendID) {
            return common\Utils::msgFormat(0,"Friend doesn't exist!");
        }
        if ($cacheHelper->sismember($key,$friendID)) {
            return common\Utils::msgFormat(0,"You have added this friend!");
        } 
        $result = $cacheHelper->sadd($key,$friendID);  
        if (empty($result))
            return common\Utils::msgFormat(0,"Add {$add_method[1]} Failed!");
        else 
            return common\Utils::msgFormat(1,"Add {$add_method[1]} Success!");      
    }

    public function delFriend($yourphone, $del_method)   //添加好友(使用redis)
    {
        $config = ZConfig::getField('cache', 'net');
        $cacheHelper = ZCache::getInstance($config['adapter'], $config);
        $yourID = $cacheHelper->hgetptoi($yourphone);
        $key = "{$yourID}_friend";
        if ($del_method[0] == "friendname") {
            $friendID = $cacheHelper->hgetntoi($del_method[1]);
        } else if ($del_method[0] == "friendphone") {
            $friendID = $cacheHelper->hgetptoi($del_method[1]);
        }
        if ($cacheHelper->sismember($key,$friendID)) {
            $result = $cacheHelper->srem($key, $friendID);
            if (empty($result))
                return common\Utils::msgFormat(0,"Delete {$del_method[1]} Failed!");
            else
                return common\Utils::msgFormat(1,"Delete {$del_method[1]} Success!");
        }  
        return common\Utils::msgFormat(0,"User:{$del_method[1]} isn't your friend!");      
    }

    public function friendList($yourphone)   //查询好友列表(使用redis)
    {
        $config = ZConfig::getField('cache', 'net');
        $cacheHelper = ZCache::getInstance($config['adapter'], $config);
        $yourID = $cacheHelper->hgetptoi($yourphone);
        $key = "{$yourID}_friend";
        $result = $cacheHelper->smembers($key);         //get the set of friends which key is $key
        if ($result){
            $friendlist = array();
            foreach ($result as $key => $value) {   //value:list of phone
                $friend = $cacheHelper->hgetitop($value);   
                $friendlist[] = $friend;
            }    
            return json_encode(array("friendlist"=>$friendlist,"flag"=>"[HTTP_SSDUTXYYX]"));
        } else {
            return common\Utils::msgFormat(0,"No Friend!");
        }
    }

    public function groupList($yourphone)   //查询群组列表(使用redis)
    {
        $config = ZConfig::getField('cache', 'net');
        $cacheHelper = ZCache::getInstance($config['adapter'], $config);
        $yourID = $cacheHelper->hgetptoi($yourphone);
        if ($cacheHelper->sismember($key,"need to refresh")) {
            $cacheHelper->srem($key,"need to refresh");
        }
        $key = "{$yourID}_group";
        $result = $cacheHelper->smembers($key);       //get the set of groups which key is $key
        if ($result){
            $grouplist = array();
            foreach ($result as $key => $value) {
                $grouplist[] = $value;
            }    
            return json_encode(array("grouplist"=>$grouplist,"flag"=>"[HTTP_SSDUTXYYX]"));
        } else {
            return common\Utils::msgFormat(0,"No group!");
        }
    }

    public function userInfo($getinfo_method)
    {
        $user = $this->fetchOne(array(
                $getinfo_method[0]=>$getinfo_method[1],
            )
        );

        if ($user){
            $user["flag"] = "[HTTP_SSDUTXYYX]";

            return json_encode($user);
        }
        else
            return common\Utils::msgFormat(0,"User:{$getinfo_method[1]} does't exist!");
    }

    public function updateUserInfo($oldphone, $to_update)
    {
        $config = ZConfig::getField('cache', 'net');
        $cacheHelper = ZCache::getInstance($config['adapter'], $config);
        $yourID = $cacheHelper->hgetptoi($oldphone);
        $oldusername = $cacheHelper->hgetiton($yourID);
        if ($oldusername) {
            if (isset($to_update['username']) && $oldusername != $to_update['username']) {
                if($cacheHelper->hexists("nametoid","{$to_update['username']}_ntoi")) {
                    return common\Utils::msgFormat(0,"This username has been used!");
                }
            }
            if (isset($to_update['phone']) && $oldphone != $to_update['phone']) {
                if($cacheHelper->hexists("phonetoid","{$to_update['phone']}_ptoi")) {
                    return common\Utils::msgFormat(0,"This phonenum has been used!");
                }            
            }
            $result = $this->update(array('phone',$oldphone), $to_update);       
            if ($result){
                if (isset($to_update['username']) && $to_update['username'] == $oldusername) {  
                    $cacheHelper->hsetiton($yourID, $to_update['username']);
                    $cacheHelper->hsetntoi($to_update['username'], $yourID);  
                    $cacheHelper->hdelntoi($oldusername); 
                }
                if (isset($to_update['phone']) && $to_update['phone'] == $oldphone) {  
                    $cacheHelper->hsetitop($yourID, $to_update['phone']);
                    $cacheHelper->hsetptoi($to_update['phone'], $yourID);
                    $cacheHelper->hdelptoi($oldphone);
                    //更改对应头像的图片名
                    if (file_exists("icon/{$oldphone}.png")) {
                        \rename(dirname(dirname(__DIR__))."/webroot/icon/{$oldphone}.png", dirname(dirname(__DIR__))."/webroot/icon/{$to_update['phone']}.png");
                    } else if (file_exists("icon/{$oldphone}.jpg")) {
                        \rename(dirname(dirname(__DIR__))."/webroot/icon/{$oldphone}.jpg", dirname(dirname(__DIR__))."/webroot/icon/{$to_update['phone']}.jpg");
                    }
                }                      
                return common\Utils::msgFormat(1,"Update Success!");
            } else
                return common\Utils::msgFormat(0,"Update Failed(mysql)!");
        } else
            return common\Utils::msgFormat(0,"Update Failed(redis)!");
    }
} 
