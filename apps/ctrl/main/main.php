<?php

namespace ctrl\main;

use common,
    ctrl\Base,
    ZPHP\Cache\Factory as ZCache,
    ZPHP\Core\Config as ZConfig;

class main extends Base
{
    public $username;

    public function main()
    {
        return common\Utils::returnError();
    }

    /*
     *   作用:登录,提供三种方式:1,phone+password2,username+password3,weixin+password
     *   成功返回:用户的详细信息({"username":"ghw","weixin":null,"phone":"188","intro":null,"flag":"[HTTP_SSDUTXYYX]"})
     *   失败返回:{"code":0,"msg":"Login Failed!","flag":"[HTTP_SSDUTXYYX]"}
     */
    public function login()                
    {
        $data = $this->getString($this->params, 'json');
        $data = str_replace('\"', '"', $data); //\" 替换为 "  否则无法解析json
        $data = str_replace("\'", '"', $data);
        // var_dump($data);
        $data = json_decode($data, true);

        if (common\Utils::checkRequest($data, array('phone', 'password'))) {   //三种登录方式
            $password = $data['password'];
            $login_method = array('phone',$data['phone']);
        } else if (common\Utils::checkRequest($data, array('username', 'password'))) {
            $password = $data['password'];
            $login_method = array('username',$data['username']);
        } else if (common\Utils::checkRequest($data, array('weixin', 'password'))) {
            $weixin = $data['weixin'];
            $password = $data['password'];
            $login_method = array('weixin',$weixin);
        } else
            return common\Utils::showMsg("Illegal request!");

        $service = common\loadClass::getService('User');
        $result = $service->checkUser($login_method, $password);
        
        return common\Utils::showMsg($result);
    }
    /*
     *   作用:注册,必要参数为phone,username,password
     *   成功返回:{"code":1,"msg":"Register Success!","flag":"[HTTP_SSDUTXYYX]"}
     *   失败返回:{"code":0,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     */
    public function reg()                   
    {
        $data = $this->getString($this->params, 'json');
        $data = str_replace('\"', '"', $data); 
        $data = str_replace("\'", '"', $data);
        // var_dump($data);
        $data = json_decode($data, true);
        if (common\Utils::checkRequest($data, array('phone', 'username', 'password'))) {
            $phone = $data['phone'];
            $username = $data['username'];
            $password = $data['password'];
        } else 
            return common\Utils::showMsg("Illegal request!");
        $sex = "";
        if (isset($data['sex']))
            $sex = $data['sex'];
        $service = common\loadClass::getService('User');
        $result = $service->addUser($phone, $username, $password, $sex);

        return common\Utils::showMsg($result);
    }
    /*
     *   作用:添加好友,提供两种方式:1,friend phone num2,friend name
     *       还需要添加者的phone num
     *   成功返回:{"code":1,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     *   失败返回:{"code":0,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     */
    public function addfriend()             
    {
        $data = $this->getString($this->params, 'json');
        $data = str_replace('\"', '"', $data); 
        $data = str_replace("\'", '"', $data);
        // var_dump($data);
        $data = json_decode($data, true);
        if (common\Utils::checkRequest($data, array('phone', 'friendname'))) {
            $phone = $data['phone'];
            $add_method = array('friendname',$data['friendname']);
        } else if (common\Utils::checkRequest($data, array('phone', 'friendphone'))) {
            $phone = $data['phone'];   //your phone num
            $add_method = array('friendphone',$data['friendphone']);  
        } else
            return common\Utils::showMsg("Illegal request!");

        $service = common\loadClass::getService('User');
        $result = $service->addFriend($phone, $add_method);
        return common\Utils::showMsg($result);
    }
    /*
     *   作用:删除好友,提供两种方式:1,friend phone num2,friend name
     *       还需要删除者的phone num
     *   成功返回:{"code":1,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     *   失败返回:{"code":0,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     */
    public function delfriend()             
    {
        $data = $this->getString($this->params, 'json');
        $data = str_replace('\"', '"', $data); 
        $data = str_replace("\'", '"', $data);
        // var_dump($data);
        $data = json_decode($data, true);
        if (common\Utils::checkRequest($data, array('phone', 'friendname'))) {
            $phone = $data['phone'];
            $del_method = array('friendname', $data['friendname']);
        } else if (common\Utils::checkRequest($data, array('phone', 'friendphone'))) {
            $phone = $data['phone'];
            $del_method = array('friendphone', $data['friendphone']);
        } else
            return common\Utils::showMsg("Illegal request!");

        $service = common\loadClass::getService('User');
        $result = $service->delFriend($phone, $del_method);
        return common\Utils::showMsg($result);
    }
    /*
     *   作用:查看好友列表,需要查询者的phone num
     *   成功返回:{"friendlist":["155","166"],"flag":"[HTTP_SSDUTXYYX]"}  (好友的手机号列表)
     *   失败返回:{"code":0,"msg":"No Friend!","flag":"[HTTP_SSDUTXYYX]"}
     */
    public function friendlist()           
    {
        $data = $this->getString($this->params, 'json');
        $data = str_replace('\"', '"', $data); 
        $data = str_replace("\'", '"', $data);
        // var_dump($data);
        $data = json_decode($data, true);
        if (common\Utils::checkRequest($data, array('phone'))) {
            $phone = $data['phone'];
        } else 
            return common\Utils::showMsg("Illegal request!");

        $service = common\loadClass::getService('User');
        $result = $service->friendList($phone);
        return common\Utils::showMsg($result);
    }
    /*
     *   作用:查询用户详细信息,提供两种方式:1,phone num  2,username
     *   成功返回:用户的详细信息({"username":"ghw","weixin":null,"phone":"188","intro":null,"flag":"[HTTP_SSDUTXYYX]"})
     *   失败返回:{"code":0,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     */
    public function userinfo()
    {
        $data = $this->getString($this->params, 'json');
        $data = str_replace('\"', '"', $data); //\" 替换为 "  否则无法解析json
        $data = str_replace("\'", '"', $data);

        $data = json_decode($data, true);

        if (common\Utils::checkRequest($data, array('username'))) {
            $getinfo_method = array('username', $data['username']);
        } else if (common\Utils::checkRequest($data, array('phone'))) {
            $getinfo_method = array('phone', $data['phone']);
        } else
            return common\Utils::showMsg("Illegal request!");

        $service = common\loadClass::getService('User');
        $result = $service->userInfo($getinfo_method);

        return common\Utils::showMsg($result);        
    }
    /*
     *   作用:更新用户信息,需要更新者的旧的phone num以及需要更新的信息
     *   成功返回:{"code":1,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     *   失败返回:{"code":0,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     */
    public function updateuserinfo()
    {
        $data = $this->getString($this->params, 'json');
        $data = str_replace('\"', '"', $data); //\" 替换为 "  否则无法解析json
        $data = str_replace("\'", '"', $data);
        //var_dump($data);
        $data = json_decode($data, true);

        if (common\Utils::checkRequest($data, array('oldphone'))) {
            $oldphone = $data['oldphone'];
            $to_update = array();
            if (isset($data['phone'])) {
                $to_update['phone'] = $data['phone'];
            } 
            if (isset($data['username'])) {
                $to_update['username'] = $data['username'];
            } 
            if (isset($data['password'])) {
                $to_update['password'] = $data['password'];
            } 
            if (isset($data['weixin'])) {
                $to_update['weixin'] = $data['weixin'];
            } 
            if (isset($data['intro'])) {
                $to_update['intro'] = $data['intro'];
            } 
            if (isset($data['sex'])) {
                $to_update['sex'] = $data['sex'];
            } 
        } else
            return common\Utils::showMsg("Illegal request!");

        $service = common\loadClass::getService('User');
        $result = $service->updateUserInfo($oldphone, $to_update);
        return common\Utils::showMsg($result);    
    }
    /*
     *   作用:创建群组,需要创建者的phone num
     *   成功返回:{"code":1,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     *   失败返回:{"code":0,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     */
    public function buildgroup()                
    {
        $data = $this->getString($this->params, 'json');
        $data = str_replace('\"', '"', $data); //\" 替换为 "  否则无法解析json
        $data = str_replace("\'", '"', $data);
        // var_dump($data);
        $data = json_decode($data, true);
        if (common\Utils::checkRequest($data, array('phone', 'groupname'))) {
            $phone = $data['phone'];
            $groupname = $data['groupname'];
        } else 
            return common\Utils::showMsg("Illegal request!");

        $service = common\loadClass::getService('Group');
        $result = $service->buildGroup($phone, $groupname);

        return common\Utils::showMsg($result);
    }
    /*
     *   作用:加入群组,需要加入者的phone num
     *   成功返回:{"code":1,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     *   失败返回:{"code":0,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     */
    public function joingroup()             //加入一个群组  ok
    {
        $data = $this->getString($this->params, 'json');
        $data = str_replace('\"', '"', $data); //\" 替换为 "  否则无法解析json
        $data = str_replace("\'", '"', $data);
        // var_dump($data);
        $data = json_decode($data, true);
        if (common\Utils::checkRequest($data, array('phone', 'groupname'))) {
            $phone = $data['phone'];
            $groupname = $data['groupname'];
        } else 
            return common\Utils::showMsg("Illegal request!");
        $service = common\loadClass::getService('Group');
        $result = $service->joinGroup($phone, $groupname);
        return common\Utils::showMsg($result);
    }
    /*
     *   作用:退出群组,需要退出者的phone num
     *   成功返回:{"code":1,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     *   失败返回:{"code":0,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     */
    public function quitgroup()             //退出一个群组   ok
    {
        $data = $this->getString($this->params, 'json');
        $data = str_replace('\"', '"', $data); //\" 替换为 "  否则无法解析json
        $data = str_replace("\'", '"', $data);
        // var_dump($data);
        $data = json_decode($data, true);
        if (common\Utils::checkRequest($data, array('phone', 'groupname'))) {
            $phone = $data['phone'];
            $groupname = $data['groupname'];
        } else 
            return common\Utils::showMsg("Illegal request!");
        $service = common\loadClass::getService('Group');
        $result = $service->quitGroup($phone, $groupname);
        return common\Utils::showMsg($result);
    }
    /*
     *   作用:解散群组,需要该群组创建者的phone num
     *   成功返回:{"code":1,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     *   失败返回:{"code":0,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     */
    public function delgroup()       
    {
        $data = $this->getString($this->params, 'json');
        $data = str_replace('\"', '"', $data); //\" 替换为 "  否则无法解析json
        $data = str_replace("\'", '"', $data);
        // var_dump($data);
        $data = json_decode($data, true);
        if (common\Utils::checkRequest($data, array('phone', 'groupname'))) {
            $phone = $data['phone'];
            $groupname = $data['groupname'];
        } else 
            return common\Utils::showMsg("Illegal request!");

        $service = common\loadClass::getService('Group');
        $result = $service->delGroup($phone, $groupname);
        return common\Utils::showMsg($result);
    }
    /*
     *   作用:查看用户的群组列表,需要该查询者的phone num
     *   成功返回:群组列表
     *   失败返回:{"code":0,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     */
    public function grouplist()            //返回群组列表  ok
    {
        $data = $this->getString($this->params, 'json');
        $data = str_replace('\"', '"', $data); //\" 替换为 "  否则无法解析json
        $data = str_replace("\'", '"', $data);
        // var_dump($data);
        $data = json_decode($data, true);
        if (common\Utils::checkRequest($data, array('phone'))) {
            $phone = $data['phone'];
        } else 
            return common\Utils::showMsg("Illegal request!");

        $service = common\loadClass::getService('User');
        $result = $service->groupList($phone);
        return common\Utils::showMsg($result);
    }
    /*
     *   作用:查看用户的群组列表,需要该查询者的phone num
     *   成功返回:群组的详细信息
     *   失败返回:{"code":0,"msg":"xxx","flag":"[HTTP_SSDUTXYYX]"}
     */
    public function groupinfo()            //根据群组名返回群组详细信息   ok
    {
        $data = $this->getString($this->params, 'json');
        $data = str_replace('\"', '"', $data); //\" 替换为 "  否则无法解析json
        $data = str_replace("\'", '"', $data);
        // var_dump($data);
        $data = json_decode($data, true);
        if (common\Utils::checkRequest($data, array('groupname'))) {
            $groupname = $data['groupname'];
        } else 
            return common\Utils::showMsg("Illegal request!");

        $service = common\loadClass::getService('Group');
        $result = $service->groupInfo($groupname);
        return common\Utils::showMsg($result);        
    }
}
