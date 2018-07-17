<?php
class WeChat
{
	//客户端的openId
	protected $fromUsername;
	//服务器的id
	protected $toUsername;
	//客户端上传的信息
	protected $keyword;
	//客户端上传的类型
	protected $sendType;
	//订阅类型或者菜单CLICK事件推送
	protected $Event;
	//菜单事件推送的EventKey
	protected $EventKey;
	//语音内容
	protected $Recognition;
	protected $lat;
	protected $lng;
	protected $time;
	public function CurlRequest($url,$data=null){
        //第1步:初始化虚拟浏览器
        $ch = curl_init();
        //第2步:设置浏览器
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);//启用安全上传模式
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true );//以text/plain文本流返回
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);//没有ssl认证服务器
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//告诉api地址不要去找ssl证书
        //如果data不为空,我们就用post请求
        if( !empty($data) )
        {
            //post方式curl在php5.6以后会抛出温馨提示,所以我们要@屏蔽温馨提示,否则会影响返回结构
             @curl_setopt($ch, CURLOPT_POST, true); //设置请求方式为post
             @curl_setopt($ch,CURLOPT_POSTFIELDS,$data);//设置数据包
        }
        $result = curl_exec( $ch );
        curl_close($ch);
        return $result;
	}

    //主要用来请求图灵机器人的问题
    public function CurlRequestPostJson( $url,$data )
    {
         //第1步:初始化虚拟浏览器
        $ch = curl_init();
        //第2步:设置浏览器
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);//启用安全上传模式
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER,true );//以text/plain文本流返回
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);//没有ssl认证服务器
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//告诉api地址不要去找ssl证书
        //把数组变成json
        $data = json_encode($data);
        //获取json数据的长度
        $length = strlen($data);
        //post方式curl在php5.6以后会抛出温馨提示,所以我们要@屏蔽温馨提示,否则会影响返回结构
        @curl_setopt($ch, CURLOPT_POST, true); //设置请求方式为post
        @curl_setopt($ch,CURLOPT_POSTFIELDS,$data);//设置数据包

        curl_setopt($ch, CURLOPT_HTTPHEADER,array(
            'Content-type: application/json',
            "Content-length: {$length}")
        );
        $result = curl_exec( $ch );
        curl_close($ch);
        return $result;
    }

    public function GetAccessToken(){
        //连接redis
        $redis = new Redis();
        $redis -> connect('',);
        $redis -> auth('');
        $redis -> select(2); //选择redis的2号数据库来作为access_token的缓存
        if( $redis -> get('access_token') ){
            return $redis -> get('access_token');
        }else{
            $api = WeChatApi::getApiUrl( 'api_access_token' );
            $res = $this -> CurlRequest( $api );
            $json = json_decode($res);
            $access_token = $json -> access_token;  //获取了access_token
            //设置redis缓存到string类型
            $redis -> set('access_token',$access_token);
            //设置缓存3600秒,expire access_token 3600
            $redis -> setTimeout('access_token',3600);
            return $access_token;
        }
    }

/*
    //获取access_token
	public function GetAccessToken(){
        //实例化memcached
        $memcached = new memcached();
        //定义memcached的分布式服务器
        $servers = array(
            ['localhost','11211',100],
        );
        //使用addServers方法来连接服务器
        $memcached -> addServers( $servers );
        if( $memcached -> get('access_token') ){
            //如果缓存中有access_token直接返回
            return  $memcached -> get('access_token');
        }else{
            $api = WeChatApi::getApiUrl( 'api_access_token' );
            $res = $this -> CurlRequest( $api );
            $json = json_decode($res);
            $access_token = $json -> access_token;  //获取了access_token
            $memcached -> set('access_token',$access_token,3600);
            return $access_token;
        }

	}

*/
	//自动回复(此方法必须覆盖)
	public function responseMsg(){
        //修改此代码兼容php5.3和php5.5,php5.6,php7.0等以上版本
		$dataFromClient = isset( $GLOBALS["HTTP_RAW_POST_DATA"] ) ? $GLOBALS["HTTP_RAW_POST_DATA"] : file_get_contents("php://input");

		if (!empty($dataFromClient)){
			$postObj = simplexml_load_string($dataFromClient, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this -> fromUsername = $postObj->FromUserName;
            $this -> toUsername = $postObj->ToUserName;
            $this -> keyword = trim($postObj->Content);
            $this -> sendType = trim($postObj->MsgType);
            $this -> Event = trim($postObj->MsgType)=='event' ? $postObj->Event : '';

            $this -> Recognition = trim($postObj->MsgType)=='voice' ? $postObj->Recognition : '语音内容无法识别';
            $this -> EventKey = $postObj->Event=='CLICK' ? $postObj->EventKey : '';
   			$this -> lat = trim($postObj->MsgType)=='location' ? $postObj->Location_X : '';
     		$this -> lng = trim($postObj->MsgType)=='location' ? $postObj->Location_Y : ''; 
            $this -> time = time();
		}
	}
    //文本回复接口
	protected function reText( $contentStr ){
		$resultStr = sprintf(WeChatApi::getMsgTpl('text'), $this->fromUsername, $this->toUsername, $this->time, 'text', $contentStr);
		echo $resultStr;	
	}
    //图片回复接口
	protected function reImage( $MediaId ){
		$resultStr = sprintf(WeChatApi::getMsgTpl('image'), $this->fromUsername, $this->toUsername, $this->time, 'image', $MediaId );
		echo $resultStr;
	}
    //音乐回复接口
	protected function reMusic( $title,$desc,$url,$hqurl ){
		$resultStr = sprintf(WeChatApi::getMsgTpl('music'), $this->fromUsername, $this->toUsername, $this->time, 'music', $title, $desc, $url, $hqurl);
        //如果出现错误,我们在对应的回复接口中把resultStr放到错误文件日志中
        WeChatApi::debugTrace('error.logs',$resultStr);
        echo $resultStr;
	}
    //视频回复接口
    protected function reVideo($MediaId,$title,$desc){
        $resultStr = sprintf(WeChatApi::getMsgTpl('video'), $this->fromUsername, $this->toUsername, $this->time, 'video', $MediaId,$title,$desc);
        echo $resultStr;      
    }

    //items是一个数组,其下标如下:
    //Title:表示图文消息的标题
    //Desc:描述
    //PicUrl:图片的地址
    //Url:文章的详细地址
	protected function reNews($items){
		$count = count( $items );
		$item = $this -> createNewsItems($items);
		$resultStr = sprintf(WeChatApi::getMsgTpl('news'), $this->fromUsername, $this->toUsername, $this->time, 'news', $count,$item);
        WeChatApi::debugTrace('news.logs',$resultStr);
        echo $resultStr;
	}
	private function createNewsItems($items){
		foreach ($items as $data ) {
			$item .= "<item>
			<Title><![CDATA[{$data['Title']}]]></Title>
			<Description><![CDATA[{$data['Desc']}]]></Description>
			<PicUrl><![CDATA[{$data['PicUrl']}]]></PicUrl>
			<Url><![CDATA[{$data['Url']}]]></Url>
			</item>";
		}
		return $item;
	}
	protected function reSubscribe( $contentStr ){
		$this -> reText( $contentStr );
	}
    private function checkSignature(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
    }
    protected function CustomerReText( $Text ){
    	$access_token = $this -> GetAccessToken();
    	$fromUsername = $this -> fromUsername;
    	$url = WeChatApi::getApiUrl('api_customer_send');
    	$url .= $access_token;
    	$content  = urlencode($Text);
        $data = array(
                "touser" => "{$fromUsername}" ,
                "msgtype"=>"text",
                "text" => array(
                    "content"=> $content,
                ),
        );
        $data = json_encode($data);
        $data = urldecode($data);
        $this -> CurlRequest( $url , $data );
        exit();
    }
    protected function CustomerReImgText( $ImgText ){
     	$access_token = $this -> GetAccessToken();
    	$fromUsername = $this -> fromUsername;
    	$url = WeChatApi::getApiUrl('api_customer_send');
    	$url .= $access_token; 
    	$set = array();
        foreach ($ImgText as $rs){
            $content = null;
            $content = array(
                //title表示表示图文的标题
                "title"=>urlencode($rs['title']),
                //desc表示图文的描述
                "description"=>urlencode($rs['desc']),
                //url表示文章的内容
                "url"=>$rs['url'],
                //picurl表示图片的连接
                "picurl"=>$rs['picurl'],
            );          
            $set[] = $content;           
        }
        $data = array(
            "touser"=>"{$fromUsername}",
            "msgtype"=>"news",
            "news" => array(
                "articles" => $set,
            ),
        );
        $data = json_encode($data);   
        $data = urldecode($data);    
        $this -> CurlRequest( $url , $data );
        exit();     	
    }
    public function codeTransAccessInfo($code=null){
    	if( isset($code) ){
    		$url = WeChatApi::getApiUrl('api_get_access_info');
    		$url .= $code;
			$str = $this -> CurlRequest( $url );
			$access_info = json_decode($str,true);
			return $access_info;
    	}else{
			exit("Error:must TransCode.");
    	}
    }
    public function SendMass($data){
    	$access_token = $this -> GetAccessToken();
    	$url = WeChatApi::getApiUrl('api_send_mass');
    	$url .= $access_token;
    	return $this -> CurlRequest( $url,$data );
    }
    public function vailAccessInfo($openId,$web_access_token)
    {
		$url = WeChatApi::getApiUrl('web_access_auth');
		$url .= "access_token={$web_access_token}&openid={$openId}";
		$str = $this -> CurlRequest( $url );
		$validInfo = json_decode($str,true);
		return $validInfo;
    }
    public function getUserInfo($web_access_token,$openId){
        $url = WeChatApi::getApiUrl('api_get_userinfo');
        $url .= "access_token={$web_access_token}&openid={$openId}&lang=zh_CN";
        $str = $this->CurlRequest( $url );
        $userInfo = json_decode($str,true);
        return $userInfo;
    }
    public function UploadMedia($media_data){
    	$access_token = $this -> GetAccessToken();
    	$url = WeChatApi::getApiUrl('api_upload_media');
    	$url .= $access_token;
    	$data['media'] = $media_data;
    	return $this -> CurlRequest($url,$data);
    }
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
           echo $echoStr;
           exit;
        }
    }
}