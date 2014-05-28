<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 13-12-6
 * Time: 下午3:26
 */

namespace common;
use ZPHP\Core\Config as ZConfig,
    ZPHP\Cache\Factory as ZCache,
    ZPHP\Common\Route as ZRoute,
    ZPHP\Conn\Factory as ZConn;


class Utils
{
    /*   
     *   作用:处理消息,生成json串返回给客户端
     *   $code:0代表错误,1代表正确;  
     *   $msg:传入信息,在该函数中增加flag以验证合法性;
     */
    public static function msgSocketFormat($msg, $type)
    {
        $flag = substr(md5(rand()), 0, 8);

        if (isset($type)) {
            $data = array(
                    "type" => $type,
                    "msg" => $msg
                );
            return "[BEGIN_SSDUTXYYX_{$flag}]" . json_encode($data) . "[END_SSDUTXYYX_{$flag}]";
        } else {
            return "[BEGIN_SSDUTXYYX_{$flag}]" . $msg . "[END_SSDUTXYYX_{$flag}]";
        }
    }
    
    public static function msgHttpFormat($code, $msg)
    {
        $data = array(
                "code" => $code,
                "msg" => $msg,
                "flag" => "[HTTP_SSDUTXYYX]"
            );
        return json_encode($data);
    }
    /*
     *   作用:检查参数是否合法
     *   $data:解析后的json
     *   $tochck:字符串数组,在该函数中检查data中是否有以tocheck中成员为索引的参数
     */
    public static function checkRequest($data, $to_check=array())
    {
        if (!isset($data['flag']) || $data['flag'] != "[HTTP_SSDUTXYYX]") {
            return false;
        }
        foreach ($to_check as $key => $value) {
            if (!isset($data[$value]))
                return false;
        } 
        return true;
    }
  
    /*
     *    作用:返回适合zphp mvc中view的数组
     */
    public static function showMsg($msg)
    {
        return array(
            '_view_mode'=>'Php',
            'msg'=>$msg,
            '_tpl_file'=>'return.php',
        );
    }

    public static function returnError()
    {
        return array(
            '_view_mode'=>'Php',
            '_tpl_file'=>'error.php',
        );
    }
} 