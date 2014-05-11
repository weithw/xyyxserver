<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 
 */
namespace ZPHP\Storage;
use ZPHP\Core\Factory as CFactory;

class Factory
{
    public static function getInstance($adapter = 'Redis', $config)
    {
        $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        return CFactory::getInstance($className, $config);
    }
}