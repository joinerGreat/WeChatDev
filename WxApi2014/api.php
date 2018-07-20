<?php
error_reporting(E_ALL || ~E_NOTICE);
//TOKEN请查看微信公众平台的开发者配置
define("TOKEN", "");
//如果出现我的第四个坑，请取消注释下面的代码
// header("HTTP/1.0 200 OK");
include dirname(__FILE__)."/Lib/WeChatApi.class.php";
include dirname(__FILE__)."/Lib/WeChat.class.php";
class WxApi extends Wechat{

	public function responseMsg(){
		parent::responseMsg();

		if( $this-> keyword=="嗨" ){
			$this -> reImages( "欢迎来到微信开发！" );
		}
	}

}

$WxApi = new WxApi();
#注解该代码就开启了自动回复功能，但是在验证TOKEN阶段必须开启
// $WxApi ->valid();
$WxApi -> responseMsg();