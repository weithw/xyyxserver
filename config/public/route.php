<?php
    return array(
        'route'=>array(
            'static' => array(                  //静态路由
                '/reg' => array(
                    'main\\main',
                    'reg'
                ),
                '/login'=>array(
                    'main\\main',
                    'login'
                ),
                '/addfriend' => array(
                    'main\\main',
                    'addfriend'
                ),
                '/delfriend'=>array(
                    'main\\main',
                    'delfriend'
                ),
                '/friendlist'=>array(
                    'main\\main',
                    'friendlist'
                ),
                '/userinfo'=>array(
                    'main\\main',
                    'userinfo'
                ),
                '/buildgroup' => array(
                    'main\\main',
                    'buildgroup'
                ),
                '/joingroup'=>array(
                    'main\\main',
                    'joingroup'
                ),
                '/quitgroup'=>array(
                    'main\\main',
                    'quitgroup'
                ),
                '/grouplist'=>array(
                    'main\\main',
                    'grouplist'
                ),
                '/groupinfo'=>array(
                    'main\\main',
                    'groupinfo'
                ),
                '/delgroup'=>array(
                    'main\\main',
                    'delgroup'
                ),
                '/addgroupmem'=>array(
                    'main\\main',
                    'addgroupmem'
                ),
                '/updateuserinfo'=>array(
                    'main\\main',
                    'updateuserinfo'
                ),
                '/uploadicon.php'=>array(

                ),
            ),
            'dynamic' => array(                     //动态路由
                '/^\/(\d+)\/(.*?)$/iU' => array(    //匹配 http://host/uid/token
                    'main\\main',                   //ctrl类
                    'main',                         //具体执行的方法
                    array('uid', 'token'),          //对应的参数名
                    '/{uid}/{token}'                //反向返回的格式, 通过内置的
                ),
            ),
        ),
    );
