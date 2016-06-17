<?php
include_once("./SDK/WeChatController.class.php");

$weixin = new WeChatController(FALSE);
// $access_token = $weixin->get_access_token();
$access_token = "5rtio6zUpAEzthRiSAhkyURVSd5ZjxcTTwSrahe977j6nmjGt6vNuq0quHYFT8Wt1y7W034hKWGXhV-6m3SKsTGBqYUssoUTyoKV9aBX4b2SXypShLj_8yGb1c0rY8YjCYPdAHAKGX";
echo "ACCESS TOKEN:<br>".$access_token."<br>";

$type = "image";
$url = "http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=".$access_token."&type=".$type;
echo "URL:<br>".$url."<br>";


$dir = "/home/web/weixin/src/pics/";
$file = "sjtu.png";
$media = array('media' => "@".$dir.$file, 'wx' => "none");

echo "MEDIA:<br>";
var_dump($media);

echo "<br>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
// curl_setopt($ch, CURLOPT_SAFE_UPLOAD, 0); // 否则在PHP5.6以上取不到文件 此行需要在CURLOPT_POSTFIELDS之前
// curl_setopt($ch, CURLOPT_POSTFIELDS, $media);
curl_setopt($ch, CURLOPT_POSTFIELDS, implode("\r\n", $media));
// curl_setopt($ch, CURLOPT_POSTFIELDS, "media=@".$dir.$file."\r\n");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$res = curl_exec($ch);
var_dump($res);
curl_close($ch);


$ref = array("asuna.jpg" => "xu63t1qfxWlwYecxmkGv0k_XiZ17FQO1avhSMiUSRJ1QleN0mGg5jG5fbqdPQi5q", "mikoto.jpg" => "jVYpsyRgxf7u5l4FYm73sJ-Q0iG1wys6cF8_phAisAVbO243B0Ii7Q87VNltO69i");



