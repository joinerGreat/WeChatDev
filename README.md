# WeChatDev
>微信公众号从0开发

#### 大家都知道微信张小龙，对于张伯龙却知之甚少，而且做php这一行，大家对于github上[overtrue安正超](https://github.com/overtrue)公布的源代码也是喜爱非常，但是我通过张伯龙API来开发微信公众号，是因为有他不可忽略的优点的

>张伯龙作为张小龙的微信合作伙伴，是微信技术方面的绝对专家，比喻一下，微信的成功，得益于张小龙的营销推广，那么张伯龙就是微信的技术支持

>[张伯龙微信API](http://zblwxapi.duapp.com)已经于2014年停止更新，这更说明了其代码内核已经接近完美，不再进行维护。但是微信发展迅速，其中一些接口已经相对落后，因此我自己根据微信官方文档也进行了修改,我在项目中已经上传其API的原版，请自行下载开发体验，对于微信开发，我会逐渐完善。

#### 这里强调一下curl的面向对象的分装
>curl默认是使用get方式请求，以文本流的方式返回数据结果,封装如下：
```php
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
```

#### 但是在实际案例中，我们经常需要post请求数据，并且返回json数据格式，这时的封装如下：
```php
    public function CurlRequestPostJson( $url,$data )
    {
         //第1步:初始化虚拟浏览器
        $ch = curl_init();
        //第2步:设置浏览器
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);//启用安全上传模式
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true );//以text/plain文本流返回
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
```

