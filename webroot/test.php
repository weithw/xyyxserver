<?php
/*

		这个就是个用来调试的脚本

*/
function send_post($url, $post_data) {

	$postdata = http_build_query($post_data);
	$options = array(
		'http' =>array(
			'method' =>'POST',
			'header' => 'Content-type:application/x-www-form-urlencoded',
			'content' => $postdata,
			'timeout' => 15 * 60 // 超时时间（单位:s）
		)
	);
	$context = stream_context_create($options);
	$result = file_get_contents($url, false, $context);

	return $result;
}

$post_data = array(
	'json' => '{"username":"xxxxxxxxx","password":"123456", "flag":"[HTTP_SSDUTXYYX]","phone":"188xxxx"}'
);
$result = send_post('http://127.0.0.1/reg', $post_data);
var_dump($result);