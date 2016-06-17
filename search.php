<?php
// include_once("./SDK/WeChatController.class.php");
date_default_timezone_set("PRC");
//$weixin = new WeChatController(FASLE);
//$access_token = $weixin->get_access_token();
//echo $access_token;

/**
* SearchClient
*/
class SearchClient
{
	private $debug = FALSE;
	private $code = '';
	private $user_id = '';

	public function __construct($debug=FALSE, $code, $user_id)
	{
		$this->debug = $debug;
		$this->code = $code;
		$this->user_id = $user_id;
	}

	public function search()
	{
		$ch = curl_init();
		$real_code = '0'.$this->code; // 上证加0 深证加1
		$start = date('Ymd');
		$end = date('Ymd');
		$url = "http://quotes.money.163.com/service/chddata.html?code=".$real_code."&start=".$start."&end=".$end."&fields=TCLOSE;HIGH;LOW;TOPEN;LCLOSE;CHG;PCHG;VOTURNOVER;VATURNOVER";
		// echo "URL:<br>";
		// echo $url;
		// echo "<br>";

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		$res = curl_exec($ch);
		curl_close($ch);

		// echo "RESPONSE:<br>";
		// $arr = str_getcsv(iconv("GB2312", "UTF-8", $res));
		// var_dump($arr);
		// echo "<br>";

		return iconv("GB2312", "UTF-8", $res);
	}
}

