## 获取全局接口凭证access_token
### 什么是access_token?
>access_token是公众号的全局唯一接口调用凭据，公众号调用各接口时都需使用access_token。开发者需要进行妥善保存。access_token的存储至少要保留512个字符空间。access_token的有效期目前为2个小时，需定时刷新，重复获取将导致上次获取的access_token失效。

>获取接口：https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET

#### 最简单获取access_token方法
```php
 public function GetAccessToken(){
            $api = WeChatApi::getApiUrl( 'api_access_token' );
            $res = $this -> CurlRequest( $api );
            $json = json_decode($res);
            $access_token = $json -> access_token;  //获取了access_token
            return $access_token;
        }
```

#### 使用redis存储access_token
```php
    public function GetAccessToken(){
        //连接redis
        $redis = new Redis();
        $redis -> connect('localhost',6379);
        $redis -> auth('php29gogo');
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
```

#### 使用memcached存储access_token
```php
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
```