<?php
include_once("./SDK/WeChatController.class.php");
define("DEBUG", TRUE);


$weixin = new WeChatController(DEBUG);
$weixin->trace_http();

// 第一次认证新的服务器主机时使用此条 if-branch
// if (!empty($_GET["echostr"])) {
// 	$weixin->valid();
// 	exit;
// }


$weixin->get_message();
$type = $weixin->msg_type; // 消息类型
// “同一用户在不同公众号中不一样 同一公众号中不同用户唯一”
$user_id = $weixin->msg['FromUserName']; // $user_id 关联了公众号appid和用户openid 


if ($type === 'text') {
	$keyword = $weixin->msg['Content']; // 用户的文本消息内容
	$weixin->log("[Management] ".'New message recieved...');
	$replyStr = "";

	if (is_numeric($keyword)) {
		$code = intval($keyword);
		$head = (int)($code / 1000);
		if ($head === 600 or $head === 601 or $head === 603) {
			include_once("search.php"); // 文本消息 调用查询程序
			$sclient = new SearchClient(DEBUG, $code, $user_id);
			$replyStr = $sclient->search();
			// $results['items'] = $sclient->search();//查询的代码
			// $reply = $weixin->makeNews($results);
		} else $replyStr = "请输入正确的上证A股代码";
	} else {
		if ($keyword === "github") {
			$replyStr = "http://github.com";
		} elseif ($keyword === "zhihu") {
			$replyStr = "http://zhihu.com";
		} elseif ($keyword === "google") {
			$replyStr = "http://www.google.com";
		} elseif ($keyword === "weibo") {
			$replyStr = "http://weibo.com";
		} elseif ($keyword === "jwc") {
			$replyStr = "http://electsys.sjtu.edu.cn";
		} elseif ($keyword === "mail") {
			$replyStr = "http://mail.sjtu.edu.cn";
		} else {
			$replyStr = "Hello User:".$user_id." \n ".$keyword;
			$replyStr .= "\n 请输入想去的网址";
		}
	}
	$reply = $weixin->generate_text($replyStr);
} elseif ($type === 'event') {
	if ($weixin->msg['Event'] == 'subscribe') {
		$reply = $weixin->generate_text('欢迎关注phonicavi的小站');
		$weixin->log("[Management]".'New guests added...');
	}
} elseif ($type === 'location') { // 用户发送的是位置信息  
	$reply = $weixin->generate_text('Hello 你发送了地址'.$user_id);
} elseif ($type === 'image') { // 用户发送的是图片
	// $reply = $weixin->generate_text('Hello 你发送了图片'.$user_id);
	// $media_id = "CrxHK6v-pEBAmo5E3Myabe0veAkIBLMK0UjTsrdQKnwv8nJzaf47pjCMzwPuGDBy";
	$media_id = "jVYpsyRgxf7u5l4FYm73sJ-Q0iG1wys6cF8_phAisAVbO243B0Ii7Q87VNltO69i";
	$reply = $weixin->generate_image($media_id);
} elseif ($type === 'voice') { // 用户发送的是声音
	$reply = $weixin->generate_text('Hello 你发送了声音'.$user_id);
}

$weixin->reply($reply);

