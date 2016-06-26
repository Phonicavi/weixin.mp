<?php
include_once __DIR__."/WeChat.config.php";
include_once __DIR__."/HttpClient.php";
date_default_timezone_set("PRC");

/**
* WeChatController
*/
class WeChatController
{
	const Weixin_Log = "weixin.log";

	private $appid = WeChatConfig::APPID;
	private $appsecret = WeChatConfig::APPSECRET;
	private $token = WeChatConfig::TOKEN;
	private $log_path = WeChatConfig::PATH_LOGGING;
	private $redis_host = WeChatConfig::REDISHOST;
	private $redis_port = WeChatConfig::REDISPORT;

	public $debug = FALSE;
	public $msg_type = 'text';
	public $msg = array();

	public function __construct($debug=FALSE)
	{
		$this->debug = $debug;
		$redis = new Redis();
		$redis->connect($this->redis_host, $this->redis_port);

		$access_token = '';
		if ($redis->exists('access_token')) {
			$access_token = $redis->get('access_token');
		} else {
			$access_token = $this->get_access_token();
			$redis->setex('access_token', 6000, $access_token);
		}
		$redis->close();
		$this->access_token = $access_token;
	}

	public function get_message()
	{
		$raw_post_data = file_get_contents("php://input"); // i.e. $GLOBALS['HTTP_RAW_POST_DATA']
		if ($this->debug) {
			$this->log("[HTTP_RAW_POST_DATA] ".$raw_post_data);
		}
		if (!empty($raw_post_data)) {
			$this->msg = (array)simplexml_load_string($raw_post_data, 'SimpleXMLElement', LIBXML_NOCDATA);
			$this->msg_type = strtolower($this->msg['MsgType']);
		}
	}

	public function generate_text($text='')
	{
		$CreateTime = time();
		$TextTemplate = "<xml>
			<ToUserName><![CDATA[{$this->msg['FromUserName']}]]></ToUserName>
			<FromUserName><![CDATA[{$this->msg['ToUserName']}]]></FromUserName>
			<CreateTime>{$CreateTime}</CreateTime>
			<MsgType><![CDATA[text]]></MsgType>
			<Content><![CDATA[%s]]></Content>
			</xml>";
		return sprintf($TextTemplate, $text);
	}

	public function generate_image($media_id='')
	{
		$CreateTime = time();
		$ImageTemplate = "<xml>
			<ToUserName><![CDATA[{$this->msg['FromUserName']}]]></ToUserName>
			<FromUserName><![CDATA[{$this->msg['ToUserName']}]]></FromUserName>
			<CreateTime>{$CreateTime}</CreateTime>
			<MsgType><![CDATA[image]]></MsgType>
			<Image><MediaId><![CDATA[%s]]></MediaId></Image>
			</xml>";
		return sprintf($ImageTemplate, $media_id);
	}

	public function generate_news($news_data=array())
	{
		$CreateTime = time();
		$NewsTemplateHeader = "<xml>
			<ToUserName><![CDATA[{$this->msg['FromUserName']}]]></ToUserName>
			<FromUserName><![CDATA[{$this->msg['ToUserName']}]]></FromUserName>
			<CreateTime>{$CreateTime}</CreateTime>
			<MsgType><![CDATA[news]]></MsgType>
			<Content><![CDATA[%s]]></Content>
			<ArticleCount>%s</ArticleCount><Articles>";
		$NewsTemplateItem = "<item>
			<Title><![CDATA[%s]]></Title>
			<Description><![CDATA[%s]]></Description>
			<PicUrl><![CDATA[%s]]></PicUrl>
			<Url><![CDATA[%s]]></Url>
			</item>";
		$NewsTemplateFooter = "</Articles>
			</xml>";
		$content = '';
		$items_count = count($news_data['items']);
		$items_count = $items_count < 10 ? $items_count : 10; // 微信公众平台图文回复消息 单次最多10条
		if ($items_count) {
			foreach ($news_data['items'] as $key => $item) {
				if ($key <= 9) {
					$part = sprintf($NewsTemplateItem, $item['title'], $item['description'], $item['picurl'], $item['url']);
					$content .= $part;
				}
			}
		}
		$header = sprintf($NewsTemplateHeader, $news_data['content'], $items_count);
		$footer = sprintf($NewsTemplateFooter);
		return $header.$content.$footer;
	}

	public function generate_notification($text='', $openid_tell, $appid)
	{
		$CreateTime = time();
		$TextTemplate = "<xml>
			<ToUserName><![CDATA[{$openid_tell}]]></ToUserName>
			<FromUserName><![CDATA[{$appid}]]></FromUserName>
			<CreateTime>{$CreateTime}</CreateTime>
			<MsgType><![CDATA[text]]></MsgType>
			<Content><![CDATA[%s]]></Content>
			</xml>";
		return sprintf($TextTemplate, $text);
	}

	public function reply($data)
	{
		if ($this->debug) {
			$this->log("[REPLY_DATA] ".$data);
		}
		echo $data;
	}

	public function trace_http()
	{
		$this->log("[REMOTE_ADDR] ".$_SERVER['REMOTE_ADDR']);
		$this->log("[QUERY_STRING] ".$_SERVER['QUERY_STRING']);
	}

	public function log($contents)
	{
		$log = date('[Y-m-d H:i:s] ').$contents."\n";
		file_put_contents($this->log_path.WeChatController::Weixin_Log, $log, FILE_APPEND);
	}

	public function valid()
	{
		$echostr = $_GET['echostr'];
		if ($this->check_signature()) {
			echo $echostr;
			$this->log("[Authorization] ".'认证成功: echostr = '.$echostr);
			exit;
		} else {
			$this->log("[Authorization] ".'认证失败: echostr = '.$echostr);
			exit;
		}
	}

	private function check_signature()
	{
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];

		$tmp_arr = array($this->token, $timestamp, $nonce);
		sort($tmp_arr);
		$tmp_str = implode($tmp_arr);
		$tmp_str = sha1($tmp_str);

		$this->log("[Authorization] ".'tmp_str = '.$tmp_str." *** signature = ".$signature);

		if ($tmp_str == $signature) return true;
		else return false;
	}

	private function get_access_token()
	{
		$get_access_token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->appsecret;
		$res = HttpClient::curl_get($get_access_token_url);
		// 这里要想办法应对微信接口返回错误的情况
		$json_obj = json_decode($res, TRUE);
		$access_token = $json_obj['access_token'];
		$limits = $json_obj['expires_in']; // 7200.sec

		return $access_token;
	}

	public function show_access_token()
	{
		return $this->access_token;
	}
}

