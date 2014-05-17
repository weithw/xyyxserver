<?php
namespace socket;
/*
      未完成,仅仅实现部分推送的功能
 */
use ZPHP\Cache\Factory as ZCache;
use ZPHP\Socket\ICallback;
use ZPHP\Core\Config as ZConfig;
use ZPHP\Common\Formater;
use ZPHP\Protocol;
use ZPHP\Core;
use common;
use ZPHP\Conn\Factory as CFactory;

class Swoole implements ICallback
{
    private $connection = null;
    private $_rpc = null;
    private function getConnection()
    {
        if (empty($this->connection)) {
            $config = ZConfig::get('connection');
            $this->connection = CFactory::getInstance($config['adapter'], $config);
        }
        return $this->connection;
    }

    public function onStart()
    {

        $params = func_get_args();
        $serv = $params[0];
        echo 'server start, swoole version: ' . SWOOLE_VERSION . PHP_EOL;
        $times = ZConfig::getField('socket', 'times');
        if(!empty($times)) {
            foreach ($times as $time) {
                $serv->addtimer($time);
            }
        }
    }

    public function onConnect()
    {
        $params = func_get_args();
        $fd = $params[1];
       echo "Client {$fd}：Connect" . PHP_EOL;
        $this->getConnection()->addFd($fd);
    }

    public function onReceive()
    {
        $params = func_get_args();
        $_data = $params[3];
        $serv = $params[0];
        $fd = $params[1];
        echo "from {$fd}: get data: {$_data}".PHP_EOL;
        echo dirname(dirname(__DIR__))."log.txt";
        if(!file_exists(dirname(dirname(__DIR__))."/log.txt")){       //如果文件不存在（默认为当前目录下）
            $fh = fopen(dirname(dirname(__DIR__))."/log.txt","w");
            fclose($fh);            //关闭文件
        }
        error_log("  [".date("Y-m-d H:i:s") ."]*  from {$fd}: get data: {$_data}".PHP_EOL,
3,dirname(dirname(__DIR__))."/log.txt");
        $begin_flag = substr($_data, 0, 26);
        $end_flag = substr($_data, -24, 24);

        if (!(substr($begin_flag, 0, 17) == "[BEGIN_SSDUTXYYX_" &&
                substr($end_flag, 0, 15) == "[END_SSDUTXYYX_" &&
                substr($begin_flag, -9, 8) == substr($end_flag, -9, 8)) ) {
            echo substr($begin_flag, 0, 17)."|".substr($end_flag, 0, 15)."|".substr($begin_flag, -9, 8) ."|".substr($end_flag, -9, 8);
            \swoole_server_send($serv, $fd, "data error".PHP_EOL);
            return null;
        }

        $_data = substr($_data, 26, -24);
        $result = json_decode($_data, true);
        if(!is_array($result)) {
            return null;
        }

        $config = ZConfig::getField('cache', 'net');
        $cacheHelper = ZCache::getInstance($config['adapter'], $config);

        switch ($result['type']) {
            case "login":            
                echo $fd . " login!" . PHP_EOL;
                    $id = $cacheHelper->hgetptoi($result['phone']);
                    if (!empty($id)) {
                        $this->getConnection()->add($id, $fd);
                        $this->getConnection()->addFd($fd, $id);
                    }
                   $this->getOfflineMsg($serv,$id);
                  
                break;
            case "hb":  //心跳处理
                $uid = $this->getConnection()->getUid($fd);
                $this->getConnection()->uphb($uid);
                //刷新群组列表
                $key = "{$uid}_group";
                $uid = $this->getConnection()->getUid($fd);
                if ($cacheHelper->sismember($key,"need to refresh")) {
                    $cacheHelper->srem($key,"need to refresh");

                    $result = $cacheHelper->smembers($key);       //get the set of groups which key is $key
                    if ($result){
                        $grouplist = array();
                        foreach ($result as $key => $value) {
                            $grouplist[] = $value;
                        }
                        $result['grouplist'] = $grouplist;
                        $result['type'] = "grouplist";
                        \swoole_server_send($serv, $fd, common\Utils::msgSendFormat(json_encode($result)));
                    } 
                }

                //\swoole_server_send($serv, $fd, "Receive heartbeat.");
                break;
            case "message":
                echo $result['from_phone'] . " send: " . $result['msg'] . " to " . PHP_EOL;
                //var_dump($result['to_phone']);
            
                foreach ($result['to_phone'] as $key => $value) {
                    $to_id = $cacheHelper->hgetptoi($value);
                    //echo "value:";var_dump($value);echo "to_id:";var_dump($to_id);
                    
                    $to_usr = $this->getConnection()->get($to_id);
                    echo "to_usr:";
                    //var_dump($to_usr);
                    if (isset($to_usr['fd'])) {
                        \swoole_server_send($serv, $fd, "Send success!");
                        unset($result['to_phone']);
                        \swoole_server_send($serv, $to_usr['fd'], common\Utils::msgSendFormat(json_encode($result)));
                    } else {
                        \swoole_server_send($serv, $fd, "User:{$value} isn't online!");
                    }    
                }
                
                break;
            case "reply":
                echo $result['from_phone'] . " send: " . $result['msg'] . " to " . PHP_EOL;
                //var_dump($result['to_phone']);

                $to_id = $cacheHelper->hgetptoi($result['to_phone']);
                echo $result['from_id'] . " reply: " . $result['msg'] . " to " . $result['to_id'] . PHP_EOL;
        
                $to_usr = $this->getConnection()->get($to_id);
                if (isset($to_usr['fd'])) {
                    \swoole_server_send($serv, $fd, "Reply success!");
                    unset($result['to_phone']);
                    \swoole_server_send($serv, $to_usr['fd'], common\Utils::msgSendFormat(json_encode($result)));
                } else {
                    \swoole_server_send($serv, $fd, "User isn't online!");
                }
                
                break;
            case "chat":
                /*
                 * 聊天之前不断检测对方心跳
                 *if($this->heartbeat($sendId)){
                 *\swoole_server_send($serv,))
                 *}
                 */
                $sendId = $cacheHelper->hgetptoi($result['from_phone']);

                $msg=$result['msg'];
                if (isset($result['to_group'])) {
                    echo "group chat";
                    $groupname=$result['to_group'];
                    $this->sendToGroup($serv,$msg,$groupname,$sendId);
                } else if (isset($result['to_phone'])) {
                    $recvId = $cacheHelper->hgetptoi($result['to_phone']);
                    $this->sendOne($serv,$sendId,$recvId,$msg);
                }
                 \swoole_server_send($serv, $fd, "Chat success!");
                break;
            // case self::OLLIST:
            //     $routeResult = $this->_route(array(
            //         'a'=>'chat/main',
            //         'm'=>'online',
            //     ));
            //     if(!empty($routeResult)) {
            //         $this->sendOne($serv, $fd, self::OLLIST, $routeResult);
            //     }
            //     break;

        }
    }
    public function getOfflineMsg($serv,$id)
    {
        $config = ZConfig::getField('cache', 'net');
        $cacheHelper = ZCache::getInstance($config['adapter'], $config);
        $usrInfo=$this->getConnection()->get($id);
        $fd=$usrInfo['fd'];
        $offlineMessage=$cacheHelper->getMessage($id);
        //$params = func_get_args();
        //$serv = $params[0];

        if(!empty($offlineMessage)){
            $message_tosend = common\Utils::msgSendFormat($offlineMessage);
            \swoole_server_send($serv, $fd, $message_tosend);
            $cacheHelper->delMessage($id);
        }
    }
    public function onClose()
    {
        echo "in onclose" . PHP_EOL;
        $params = func_get_args();
        $serv = $params[0];
        $fd = $params[1];
        $uid = $this->getConnection()->getUid($fd);
        $this->getConnection()->delete($fd, $uid);
        //$this->sendToChannel($serv, self::LOGOUT, array($uid));
        echo $fd . " closed" . PHP_EOL;
    }

    public function onShutdown()
    {
        echo "server shut dowm\n";
        $this->getConnection()->clear();
    }

    public function sendOne($serv, $sendId,$recvId,$data)
    {

        if (empty($serv) || empty($recvId) || empty($data)||empty($sendId)) {
            return false;
        }

        $config = ZConfig::getField('cache', 'net');
        $cacheHelper = ZCache::getInstance($config['adapter'], $config);
        /*
         *online 如果在线的话就直接发
        */
        $to_usr=$this->getConnection()->get($recvId);
        echo "recvId: ";var_dump($recvId);
        echo "to fd: ";var_dump($to_usr);
        if(!empty($to_usr)){
            $json = array();
            $fd=$to_usr['fd'];
            $json['from_name']=$cacheHelper->hgetiton($sendId);
            $json['time']=date("Y-m-d H:i:s",time());
            $json['msg']=$data;
            $msg_tosend = common\Utils::msgSendFormat(json_encode($json));
            return  \swoole_server_send($serv,$fd,$msg_tosend);
        }
        /*
         * *offline 如果没有在线的话就直接存起来就好，在线上的话才发，首先是先判断再说
         */
        else{
            $cacheHelper->addMessage($recvId,$data,$sendId);
        }

    }

    public function sendToChannel($serv, $cmd, $data, $channel = 'ALL')
    {
        $list = $this->getConnection()->getChannel($channel);
        if (empty($list)) {
           echo "{$channel} empty==".PHP_EOL;
            return;
        }

        foreach ($list as $fd) {
           echo "send to {$fd}===".PHP_EOL;
            $this->sendOne($serv, $fd, $cmd, $data);
        }
    }

    public function heartbeat()
    {

    }


    public function sendToGroup($serv, $data, $groupName,$sendId)
    {
        $config = ZConfig::getField('cache', 'net');
        $cacheHelper = ZCache::getInstance($config['adapter'], $config);
        $sendFd=$this->getConnection()->get($sendId);
        $key = "{$groupName}_member";
        $result = $cacheHelper->smembers($key);       //get the set of groups which key is $key
        if (!empty($result)){
            foreach ($result as $index => $recvId) {
                if ($recvId != $sendId)
                    $this->sendOne($serv,$sendId,$recvId,$data);
            }
        }
        else
            \swoole_server_send($serv,$sendFd,'groupName not exist!') ;   //json_encode

    }
    public function hbcheck($serv)
    {
        echo "in hbcheck" . PHP_EOL;
        $list = $this->getConnection()->getChannel();
        if (empty($list)) {
            return;
        }

        foreach ($list as $uid => $fd) {
            if (!$this->getConnection()->heartbeat($uid,10)) {
                $this->getConnection()->delete($fd, $uid);
                \swoole_server_close($serv, $fd);
            }
        }
    }

    public function onTimer()
    {
        echo "in ontimer" . PHP_EOL;
        $params = func_get_args();
        $serv = $params[0];
        $interval = $params[1];
        var_dump($interval);
        switch ($interval) {
            case 10000: //heartbeat check
                $this->hbcheck($serv);
                break;
        }

    }

    public function rpc($params)
    {
        if ($this->_rpc === null) {
            $this->_rpc = new \Yar_Client(ZConfig::getField('socket', 'rpc_host'));
        }
        try {
            $result = $this->_rpc->api($params);
            return $result;
        } catch (\Exception $e) {
            $result =  Formater::exception($e);
            return $result;
        }
    }


    private function _route($data)
    {
        try {
            $server = Protocol\Factory::getInstance(ZConfig::getField('socket', 'protocol', 'Rpc'));
            $server->parse($data);
            $result =  Core\Route::route($server);
            return $result;
        } catch (\Exception $e) {
            $result =  Formater::exception($e);
            ZPHP\Common\Log::info('zchat', [var_export($result, true)]);
            return null;
        }
    }


    public function onWorkerStart()
    {
        $params = func_get_args();
        $worker_id = $params[1];
        echo "WorkerStart[$worker_id]|pid=" . posix_getpid() . ".\n";
    }

    public function onWorkerStop()
    {
        $params = func_get_args();
        $worker_id = $params[1];
        echo "WorkerStop[$worker_id]|pid=" . posix_getpid() . ".\n";
    }

    public function onTask()
    {

    }

    public function onFinish()
    {
        
    }
}
