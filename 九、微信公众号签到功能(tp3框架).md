## 微信公众号签到功能结合tp3框架，后台控制
>微信中需要开发全新的功能，必然用到接口请求，在这里我们先要在wechatApi文件中配置接口url:(请配置成自己后台接口地址)
```php
//微信公众号的后台配置
			'api_wxoa_config'=>"https://www.xxx.com/admin/index.php/模型/控制器/方法",
			//监听微信公众号的用户
			'api_wxoa_user'=>"https://www.xxx.com/admin/index.php/模型/控制器/方法?openid=",
			//录入操作时间
			'api_wxoa_addtime'=>"https://www.xxx.com/admin/index.php/模型/控制器/方法?openid=",
			//将值置为1的操作
			'api_wxoa_valone'=>"https://www.xxx.com/admin/index.php/模型/控制器/方法?openid=",
```
>熟悉thinkphp的人，一看就能懂，如果使用的是laravel框架和CII框架或者tp5以上的同学，请填写自己的正确路由地址;
#### tp后台方法代码如下
```php
 //微信公众号的接口
		public  function wxConfig(){
			$data = M("wxoa_config")->select();
			$content =[];
			foreach ($data as $key => $value) {
					$content[]=$value['keyword'];

			}
			foreach ($data as $key => $value) {
					if (!empty($value['day'])) {
						$content['day']=$value['day'];
					}
			}
			$json = json_encode($content);
			echo $json;
		}

		//微信用户的监听
		public  function wxUser(){
				$map["openid"] = I("get.openid");
            	$user = M("wxoa_user")->where($map)->find();
            	$json = json_encode($user);
				echo $json;
		}


		//录入投票时间
		public  function wxAddTime(){
				$map["openid"] = I("get.openid");
				$fieldname = I("get.fieldname");
				$time_name = I("get.time_name");
            	$user = M("wxoa_user")->where($map)->find();
            	$data["id"] = $user["id"];
	            $data[$fieldname] = $user[$fieldname]+1;
	            $data[$time_name] = time();
	            //进行裁剪图片路径的入库
	            M("wxoa_user")->save($data);
		}

		//将值置为1的操作
		public function valToOne(){
				$map["openid"] = I("get.openid");
            	$user = M("wxoa_user")->where($map)->find();
            	$data["id"] = $user["id"];
	            $data[I("get.fieldname")] = I("get.changval");
	            //进行裁剪图片路径的入库
	            M("wxoa_user")->save($data);
		}
```

#### wechat文件中自己封装请求方法代码如下
```php
//微信公众号的配置,自己进行添加TP后台
    public function wxConfig(){
        $url = WeChatApi::getApiUrl('api_wxoa_config');
        $str = $this->CurlRequest($url);
        $wxconfig = json_decode($str,true);
        return $wxconfig;
    }


    //微信公众号的用户监听
    public function wxUser($openid){
        $url = WeChatApi::getApiUrl('api_wxoa_user');
        $url .= $openid;
        $str = $this->CurlRequest($url);
        $user = json_decode($str,true);
        return $user;
    }

    //微信公众号的操作时间的录入
    public function valAddOne($openid,$name,$name_time){
        $url = WeChatApi::getApiUrl('api_wxoa_addtime');
        $url .= $openid."&fieldname=".$name."&time_name=".$name_time;
        $this->CurlRequest($url);
    }

    //将值置为1的操作
    public function valToOne($openid,$name,$changval){
        $url = WeChatApi::getApiUrl('api_wxoa_valone');
        $url .= $openid."&fieldname=".$name."&changval=".$changval;
        $this->CurlRequest($url);
    }
```

### 在开发文件中api.php中我实现签到方法如下：
###在写签到方法的时候需要列出签到的各种限制条件：
#### 只有同一openid的人在每天回复签到时+1
#### 每天只能签到一次，如果已经签到，则提示已签到，请明天再来
#### 必须连续签到，如果非连续，断开一次就从一开始
#### 判断用户是否连续签到，如果不是，将其值置一，并给出提示。
#### 如果用户完成了连续签到，则发送给用户提示和抽奖的链接，并重置签到值为0
>根据以上的条件依次实现每个条件，就算是完成了签到功能了。
```php
//每次微信服务器访问的时候，获取自身服务器中的配置
		$wxdata = $this->wxConfig();
		$user = $this->getUserInfo();

		/** ******************************************************************************
		 * 微信公众号签到功能的实现
		 *
		 * @author:江亮 (jiangliangscau@163.com)
		 * @time：2018-07-25
		 * @modify 2018-07-25
		 * @param string 参数  [参数介绍1]
		 * @param string 参数  [参数介绍2]
		 * @return json
		 *******************************************************************************/
		//1、只有同一openid的人在每天回复签到时+1
		//2、必须连续签到，如果非连续，断开一次就从一开始
		if ($this->keyword == "{$wxdata[2]}") {
			$wxoaUser = $this->wxUser($user['openid']);
			//如果用户第一次签到，则进行时间录入
			if ($wxoaUser['sign']==0) {
				$this->valAddOne($user['openid'],sign,sign_time);
				$this->reText("您好,您已于".date("Y-m-d H:i:s",time())."签到成功,您已经连续签到".($wxoaUser['sign']+1)."天！连续签到".$wxdata['day']."天可以领取奖励哦!" );
				exit();
			}
		    //每天只能签到一次，如果已经签到，则提示已签到，请明天再来
		    if (date('Ymd',$wxoaUser['sign_time'])== date('Ymd',time())) {
		    	$this->reText("您今天已签到，请明日再来!" );
		    	exit();
		    }else{
		    	//判断用户是否连续签到，如果不是，将其值置一，并给出提示。
		    	if((date('Ymd',$wxoaUser['sign_time'])+1)!=date('Ymd',time())){
		    		$this->reText("您已断签，签到将从新开始哦!" );
		    		//将其值重置为1，调用方法
		    		$this->valToOne($user['openid'],sign,1);
		    		exit();
		    	}else{
					//用户点击了签到，并且今日还未签到，先给数据库中的签到加+1，并记录签到时间，再将值进行判断
					$this->valAddOne($user['openid'],sign,sign_time);
					if ($wxoaUser['sign']+1 >= $wxdata['day']) {
						//并发送给用户提示和抽奖的链接
						usleep(100);
						$this->reImage("V3_CvtvJuy3Yoqcm-P-ySthwYmslBpx2AuMThiKlJnP2Kpu_RfhQ72C6U1ZnwgC0");
						$this->CustomerReText( "您好，签到成功！\n您已经连续签到".$wxdata['day']."天，达成签到达人目标，扫描下方(测试)二维码，领取福利!" );
						//将其值置为零
						$this->valToOne($user['openid'],sign,0);
						exit();

					}else{
						$this->reText("您好,您已于".date("Y-m-d H:i:s",time())."签到成功,您已经连续签到".($wxoaUser['sign']+1)."天！连续签到".$wxdata['day']."天可以领取奖励哦!" );
							exit();
					}
		    	}
		    }

		}

```