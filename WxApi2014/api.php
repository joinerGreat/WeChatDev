<?php
error_reporting(E_ALL || ~E_NOTICE);
define("TOKEN", "");
include dirname(__FILE__)."/Lib/WeChatApi.class.php";
include dirname(__FILE__)."/Lib/WeChat.class.php";
class WxApi extends Wechat{

	public function responseMsg(){
		parent::responseMsg();

		if( !empty($this-> keyword) ){
			$this -> reText("");
		}
	}

}

$WxApi = new WxApi();
#注解该代码就开启了自动回复功能
// $WxApi ->valid();
$WxApi -> responseMsg();