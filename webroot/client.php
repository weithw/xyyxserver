<?php
/*

		这个就是个用来调试的脚本

*/
$clients = array();
$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC); //同步阻塞
$ret = $client->connect('210.30.97.63', 8991, 0.5, 0);
if(!$ret) {
	echo "Connect Server fail.errCode=".$client->errCode;
} else {

	while(true)
	{
		$flag = 0;
		$data = array();
		$name = trim(fgets(STDIN));
		if ($name == '1') {
			$data['type'] = "login";
			$data['phone'] = "138";
		} else if ($name == '2') {
			$data['type'] = "login";
			$data['phone'] = "155";
		} else if ($name == '3') {
			$data['type'] = "message";
			$data['from_phone'] = "155";
			$data['to_phone'] = array();
			$data['to_phone'][] = "138";
			//$data['to_phone'][] = "178";
			$data['send_time'] = date("Y-m-d H:i:s");
			$data['msg'] = "中文行不行？";
		} else if ($name == '4') {
			$data['type'] = "reply";
			$data['from_phone'] = "138";
			$data['to_phone'] = "155";
			$data['reply_time'] = date("Y-m-d H:i:s");
			$data['msg'] = "dangerous!!!";
			$data['send_time'] = "2010-10-10 10:10:10";
		} else if ($name == '5'){
			$data['type'] = "chat";
			$data['from_phone'] = "44444444444";
			$data['to_phone'] = "11111111111";
			$data['msg'] = "aaa";			
		} else if ($name == '6'){
			$data['type'] = "chat";
			$data['from_phone'] = "138";
			$data['to_phone'] = "155";
			$data['msg'] = "bbb";			
		} else  {
			$flag = 1;
		}

		if ($flag != 1) {
			$client->send("[BEGIN_SSDUTXYYX_10101010]" . json_encode($data) . "[END_SSDUTXYYX_10101010]");           //json格式传递参数 
			$clients[$client->sock] = $client;
			$write = $error = array();
			$read = array_values($clients);
			$n = swoole_client_select($read, $write, $error, 0.6);
			if($n > 0)
			{
				foreach($read as $index=>$c)
				{
					echo "[Recv] ".$c->recv()."\n";       //显示返回值
					//unset($clients[$c->sock]);
				}
			}
		} else {
			echo "nothing to send.";
		}

	}
}
