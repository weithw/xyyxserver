<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server\Adapter;
use ZPHP\Core,
    ZPHP\Protocol;

class Http
{
    public function run()
    {
        $server = Protocol\Factory::getInstance('Http');
        iconv_set_encoding("internal_encoding", "UTF-8");
		iconv_set_encoding("output_encoding", "UTF-8");
		iconv_set_encoding("input_encoding", "UTF-8");
        
        $server->parse($_REQUEST);
        
        Core\Route::route($server);
    }

}