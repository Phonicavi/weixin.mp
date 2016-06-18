<?php
date_default_timezone_set("PRC");

/**
* HttpClient: curl_get & curl_post
*/
class HttpClient
{
	static private $log_file = "/home/web/weixin/logs/http.log";

	static public function curl_get($url)
	{
		self::log("URL = ".$url);
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL => $url,
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_FOLLOWLOCATION => 1
		));
		$data = curl_exec($ch);
		curl_close($ch);
		self::log("return data:\n".$data);
		return $data;
	}

	static public function curl_post($url, $param)
	{
		self::log("POST-URL = ".$url);
		self::log("POST-PARM = ".$param);
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL => $url,
			CURLOPT_HEADER => 0,
			CURLOPT_POST => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_SAFE_UPLOAD => 0, /* 否则在PHP5.6以上取不到文件 此行需要在CURLOPT_POSTFIELDS之前 */
			CURLOPT_POSTFIELDS => $param
		));
		$data = curl_exec($ch);
		curl_close($ch);
		self::log("return data:\n".$data);
		return $data;
	}

	static private function log($contents)
	{
		$log = date('[Y-m-d H:i:s] ').$contents."\n";
		file_put_contents(__DIR__.self::$log_file, $log, FILE_APPEND);
	}
}
