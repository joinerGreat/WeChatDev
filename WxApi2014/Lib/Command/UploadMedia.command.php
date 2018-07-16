 <?php
include "Common.php";
include LIB."WeChatApi.class.php";
include LIB."WeChat.class.php";
$WeChat = new WeChat();
//把第7行 代码的文件修改为Media目录下的文件名称即可
$media_data = MEDIA_UPLOAD."info3.jpg"; 
$str = $WeChat -> UploadMedia($media_data);
$json = json_decode($str);
var_dump($json);
