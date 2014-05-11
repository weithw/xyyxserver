<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Cache\Adapter;
use ZPHP\Cache\ICache,
    ZPHP\Manager;

class Redis implements ICache
{
    private $redis;

    public function __construct($config)
    {
        if (empty($this->redis)) {
            $this->redis = Manager\Redis::getInstance($config);
        }
    }
    public function addMessage($uid,$message,$sendId)
    {
        /*
         * 时间+消息+发送人
         */
        $fromName=$this->hgetiton($sendId);
        $json = $this->get("{$uid}_msg_xyyx");
        $all_msg = json_decode($json);

        $new_msg = array();
        $new_msg['from_name'] = $fromName;
        $new_msg['time'] = date("Y:M:D:H:i:s",time());
        $new_msg['msg'] = $message;

        $all_msg[] = $new_msg;

        return $this->set("{$uid}_msg_xyyx", json_encode($all_msg));

    }
    /*
     * 取离线消息
     */
    public function getMessage($uid)
    {
        $message=$this->get("{$uid}_msg_xyyx");

        return $message;
    }

    public function delMessage($uid)
    {
        $message=$this->delete("{$uid}_msg_xyyx");
    }

    public function enable()
    {
        return true;
    }

    public function selectDb($db)
    {
        $this->redis->select($db);
    }

    public function add($key, $value, $expiration = 0)
    {
        return $this->redis->setNex($key, $expiration, $value);
    }

    public function sismember($key, $member)
    {
        return $this->redis->sismember($key, $member);
    }

    public function srem($key, $member)
    {
        return $this->redis->srem($key, $member);
    }

    public function smembers($key)
    {
        return $this->redis->smembers($key);
    }

    public function set($key, $value, $expiration = 0)
    {
        if ($expiration) {
            return $this->redis->setex($key, $expiration, $value);
        } else {
            return $this->redis->set($key, $value);
        }
    }

    public function hset($key, $field, $value)
    {
        return $this->redis->hset($key,$field, $value);
    }

    public function hget($key, $field)
    {
        return $this->redis->hget($key,$field);
    }

    public function hgetptoi($field)
    {
        return $this->redis->hget("phonetoid","{$field}_ptoi");
    }

    public function hgetitop($field)
    {
        return $this->redis->hget("idtophone","{$field}_itop");
    }

    public function hgetntoi($field)
    {
        return $this->redis->hget("nametoid","{$field}_ntoi");
    }

    public function hgetiton($field)
    {
        return $this->redis->hget("idtoname","{$field}_iton");
    }

    public function hsetptoi($field, $value)
    {
        return $this->redis->hset("phonetoid","{$field}_ptoi", $value);
    }

    public function hsetitop($field, $value)
    {
        return $this->redis->hset("idtophone","{$field}_itop", $value);
    }

    public function hsetntoi($field, $value)
    {
        return $this->redis->hset("nametoid","{$field}_ntoi", $value);
    }

    public function hsetiton($field, $value)
    {
        return $this->redis->hset("idtoname","{$field}_iton", $value);
    }

    public function hexists($key, $field)
    {
        return $this->redis->hexists($key, $field);
    }

    public function hdelptoi($field)
    {
        return $this->redis->hdel("phonetoid","{$field}_ptoi");
    }

    public function hdelntoi($field)
    {
        return $this->redis->hdel("nametoid","{$field}_ntoi");
    }

    public function sadd($key, $member)
    {
        return $this->redis->sadd($key, $member);
    }

    public function addToCache($key, $value, $expiration = 0)
    {
        return $this->set($key, $value, $expiration);
    }

    public function get($key)
    {
        return $this->redis->get($key);
    }

    public function getCache($key)
    {
        return $this->get($key);
    }

    public function delete($key)
    {
        return $this->redis->delete($key);
    }

    public function increment($key, $offset = 1)
    {
        return $this->redis->incrBy($key, $offset);
    }

    public function decrement($key, $offset = 1)
    {
        return $this->redis->decBy($key, $offset);
    }

    public function clear()
    {
        return $this->redis->flushDB();
    }
}