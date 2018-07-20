## LBS地理位置服务开发
### 什么是LBS？
>基于位置的服务，是指通过电信移动运营商的无线电通讯网络或外部定位方式，获取移动终端用户的位置信息，在GIS平台的支持下，为用户提供相应服务的一种增值业务
```php
$this->lat //表示维度
$this->lng //表示经度
```

### 使用百度的逆地理编码接口，请先注册，获取ak
>[百度地图全球逆地理编码接口](http://lbsyun.baidu.com/index.php?title=webapi/guide/webservice-geocoding-abroad)如下：
>http://api.map.baidu.com/geocoder/v2/?callback=renderReverse&location=35.658651,139.745415&output=json&pois=1&ak=您的ak //GET请求
>其实使用curl请求上面的网址后，又是一个坑，会如下数据
```json
renderReverse&&renderReverse({"status":0,"result":{"location":{"lng":113.32739999999997,"lat":23.118311010889089},"formatted_address":"广东省广州市天河区临江大道","business":"岭南,珠江新城,跑马场","addressComponent":{"country":"中国","country_code":0,"country_code_iso":"CHN","country_code_iso2":"CN","province":"广东省","city":"广州市","city_level":2,"district":"天河区","town":"","adcode":"440106","street":"临江大道","street_number":"","direction":"","distance":""},"pois":[{"addr":"珠江新城花城广场南侧","cp":" ","direction":"南","distance":"152","name":"海心沙亚运公园-西门","poiType":"出入口","point":{"x":113.32778869654283,"y":23.119524957099445},"tag":"出入口;门","tel":"","uid":"d0691d385cf02939efe4a564","zip":"","parent_poi":{"name":"海心沙亚运公园","tag":"旅游景点;公园","addr":"珠江新城临江大道","point":{"x":113.33142683385704,"y":23.117514109032066},"direction":"西","distance":"458","uid":"b2809cc01cbcea85593ac820"}}],"roads":[],"poiRegions":[],"sematic_description":"海心沙亚运公园-西门南152米","cityCode":257}})
```
#### 你会发现返回的数据会携带renderReverse&&renderReverse这样的头部，在使用json_decode的时候并不好处理，如何进行去除呢，只要将url中的callback=renderReverse去除即可，所以最终你访问的接口地址应该是：http://api.map.baidu.com/geocoder/v2/?location=35.658651,139.745415&output=json&pois=1&ak=您的ak;
>返回结果即可变成如下：
```json
{"status":0,"result":{"location":{"lng":113.32739999999997,"lat":23.118311010889089},"formatted_address":"广东省广州市天河区临江大道","business":"岭南,珠江新城,跑马场","addressComponent":{"country":"中国","country_code":0,"country_code_iso":"CHN","country_code_iso2":"CN","province":"广东省","city":"广州市","city_level":2,"district":"天河区","town":"","adcode":"440106","street":"临江大道","street_number":"","direction":"","distance":""},"pois":[{"addr":"珠江新城花城广场南侧","cp":" ","direction":"南","distance":"152","name":"海心沙亚运公园-西门","poiType":"出入口","point":{"x":113.32778869654283,"y":23.119524957099445},"tag":"出入口;门","tel":"","uid":"d0691d385cf02939efe4a564","zip":"","parent_poi":{"name":"海心沙亚运公园","tag":"旅游景点;公园","addr":"珠江新城临江大道","point":{"x":113.33142683385704,"y":23.117514109032066},"direction":"西","distance":"458","uid":"b2809cc01cbcea85593ac820"}}],"roads":[],"poiRegions":[],"sematic_description":"海心沙亚运公园-西门南152米","cityCode":257}
```

#### 案例代码如下：(curl需要自己封装，我已经在张伯龙api中封装好了，请自行下载体验)
```php
		if ($this->sendType="location") {
			$lat = $this->lat;
			$lng = $this->lng;
			$lbsUrl = "http://api.map.baidu.com/geocoder/v2/?location=23.118311,113.327400&output=json&pois=1&ak=3k4o9OMEipspDn5S1SPuZ7OafTqatVDg";
			$addr= $this->CurlRequest( $lbsUrl );
			$location = json_decode( $addr,true );//将其转换为数组

			$this->reText( $location['result']['formatted_address']);
			exit();
		}
```
>然后在微信客户端发送一个位置，即可回复地理位置了


#### 通过LBS接口进行展示周边信息，可以修改代码如下
```php
<?php
error_reporting(E_ALL || ~E_NOTICE);
//天助代码
header("HTTP/1.0 200 OK");
define("TOKEN", "MRGCGZ");
include dirname(__FILE__)."/Lib/WeChatApi.class.php";
include dirname(__FILE__)."/Lib/WeChat.class.php";
class WxApi extends Wechat
{
	public function responseMsg(){
		parent::responseMsg();
		//关注回复
		if ($this->sendType == "event" && $this->Event =="subscribe") {
			$this -> reText("嘉利广州公众号欢迎您，机器人小嘉利宝宝已经上线，欢迎语音或者文字调戏她哦！");
			exit();
		}


		if ($this->sendType=="location") {
			$lat = $this->lat;
			$lng = $this->lng;
			$lbsUrl = "http://api.map.baidu.com/geocoder/v2/?location={$lat},{$lng}&output=json&pois=1&ak=3k4o9OMEipspDn5S1SPuZ7OafTqatVDg";
			$addr= $this->CurlRequest( $lbsUrl );
			$location = json_decode( $addr,true );//将其转换为数组

			$content = "您当前位置是：".$location['result']['formatted_address']."，您周边信息如下：\n";
			$points = $location['result']['pois'];

			foreach ($points as $key => $point) {
				$content .= ++$key.'、'.$point['name'].' '.$point['tag'].' '.$point['addr']."\n";
			}

			$this->reText( $content );
			exit();
		}

	}
}
$WxApi = new WxApi();
#注解该代码就开启了自动回复功能
// $WxApi ->valid();
$WxApi -> responseMsg();
```
>客户端发送位置后返回结果如下:
>您当前位置是：广东省广州市天河区临江大道，您周边信息如下：
>1、海心沙亚运公园-西门，出入口;门，珠江新城花城广场南侧
>2、广州农村商业银行(华夏支行)，金融;信用社，临江大道49
>3、广州大剧院，休闲娱乐;剧院，广东省广州市天河区珠江西路1号
>4、海心沙亚运开闭幕式场馆，运动健身;体育场馆，广东省广州市越秀区白云街道晴波路越秀南粤先贤公园东
>5、广东省体育局，政府机构;行政单位，广州市越秀区晴澜路68号
>6、信合大厦写字楼，房地产;写字楼，广州市天河区珠江新城华厦路1号
>7、长江商学院，教育培训;高等院校，华夏路1号信合大厦8层801、803
>8、海心沙亚运公园，旅游景点;公园，珠江新城临江大道
>9、国家税务总局广州市税务局，政府机构;行政单位，广州市天河区华夏路3号
>10、广州大剧院-前厅，休闲娱乐;剧院，广州市天河区珠江新城珠江西路1号广州大剧院


