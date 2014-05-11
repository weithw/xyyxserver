<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 对列接口
 */
namespace ZPHP\Queue;
use ZPHP\Core\Factory as CFactory;

class Factory
{
    public static function getInstance($adapter = 'Redis', $config = null)
    {
        $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        return CFactory::getInstance($className, $config);
    }
}
