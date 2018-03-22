<?php
namespace app\admin\controller;
use think\Controller;
class Index extends Controller
{
	//这个是没用的首页，没写rbac之前的
	// public function index()
	// {
	// 	return $this->fetch();
	// }

	//我的桌面，以后继续修改
	public function welcome()
	{
		return $this->fetch();
	}
	//接口，查询天气
	public function findweather()
	{
		$this->weather($_POST['yourcity']);
	}	
	//第一个接口天气
 	public function weather($yourcity) {
		require_once 'curl.func.php';
		$appkey = 'da503d3b89c941af';//你的appkey
		$city = $yourcity;//utf8
		$cityid='111';//任选
		$citycode='101260301';//任选
		$url = "http://api.jisuapi.com/weather/query?appkey=$appkey&city=$city";
		$result = curlOpen($url);
		$jsonarr = json_decode($result, true);
		//exit(var_dump($jsonarr));
		if($jsonarr['status'] != 0)
		{
		    echo $jsonarr['msg'];
		    exit();
		}
		 
		$result = $jsonarr['result'];
		echo $result['city'].' '.$result['cityid'].' '.$result['citycode'].' '.$result['date'].' '.$result['week'].' '.$result['weather'].' '.$result['temp'].'<br>';
		echo $result['temphigh'].' '.$result['templow'].' '.$result['img'].' '.$result['humidity'].' '.$result['pressure'].' '.$result['windspeed'].' '.$result['winddirect'].'<br>';
		echo $result['windpower'].' '.$result['updatetime'].'<br>';
		echo '指数：<br>';
		foreach($result['index'] as $index)
		{
		    echo $index['iname'].' '.$index['ivalue'].' '.$index['detail']. '<br>';
		}
		// echo '空气质量指数：<br>';
		// $aqi = $result['aqi'];
		// echo $aqi['so2'].' '.$aqi['so224'].' '.$aqi['no2'].' '.$aqi['no224'].' '.$aqi['co']. '<br>';
		// echo $aqi['co24'].' '.$aqi['o3'].' '.$aqi['o38'].' '.$aqi['o324'].' '.$aqi['pm10']. '<br>';
		// echo $aqi['pm1024'].' '.$aqi['pm2_5'].' '.$aqi['pm2_524'].' '.$aqi['iso2'].' '.$aqi['ino2']. '<br>';
		// echo $aqi['ico'].' '.$aqi['io3'].' '.$aqi['io38'].' '.$aqi['ipm10'].' '.$aqi['ipm2_5']. '<br>';
		// echo $aqi['aqi'].' '.$aqi['primarypollutant'].' '.$aqi['quality'].' '.$aqi['timepoint']. '<br>';
		// echo $aqi['aqiinfo']['level'].' '.$aqi['aqiinfo']['color'].' '.$aqi['aqiinfo']['affect'].' '.$aqi['aqiinfo']['measure']. '<br>';
		echo '未来几天天气：<br>';
		foreach($result['daily'] as $daily)
		{
		    echo $daily['date'].' '.$daily['week'].' '.$daily['sunrise'].' '.$daily['sunset']. '<br>';
		    echo $daily['night']['weather'].' '.$daily['night']['templow'].' '.$daily['night']['img'].' '.$daily['night']['winddirect'].' '.$daily['night']['windpower']. '<br>';
		    echo $daily['day']['weather'].'  '.$daily['day']['img'].' '.$daily['day']['winddirect'].' '.$daily['day']['windpower']. '<br>';
		}
		// echo '未来几小时天气：<br>';
		// foreach($result['hourly'] as $hourly)
		// {    
		//     echo $hourly['time'].' '.$hourly['weather'].' '.$hourly['temp'].' '.$hourly['img']. '<br>';    
		// }
	}
	//接口，周公解梦
	public function findsleep()
	{
		$this->sleep($_POST['yourcity']);
	}	
	////第二个接口
	public  function sleep($yoursleep) {
		require_once 'curl.func.php';
		 
		$appkey = 'da503d3b89c941af';//你的appkey
		$keyword = $yoursleep;//utf8
		$url = "http://api.jisuapi.com/dream/search?appkey=$appkey&keyword=$keyword";
		$result = curlOpen($url);
		 
		$jsonarr = json_decode($result, true);
		//exit(var_dump($jsonarr));
		 
		if($jsonarr['status'] != 0)
		{
		    echo $jsonarr['msg'];
		    exit();
		}
		 
		$result = $jsonarr['result'];
		foreach($result as $val)
		{
		    echo $val['name'].' '.$val['content'].'<br>';
		}
	}

	//接口，周公解梦
	public function findmail()
	{
		$this->mail($_POST['yourcity']);
	}
	//第三个接口  快递
	public function  mail($yourmail){ 
	 
		require_once 'curl.func.php';
		 
		$appkey = 'da503d3b89c941af';//你的appkey
		$url = "http://api.jisuapi.com/express/query?appkey=$appkey";
		$type = 'sfexpress';
		$number = $yourmail;
		 
		$post = array('type'=>$type, 
		            'number'=>$number
		        );
		$result = curlOpen($url, array('post'=>$post));
		 
		$jsonarr = json_decode($result, true);
		//exit(var_dump($jsonarr));
		 
		if($jsonarr['status'] != 0)
		{
		    echo $jsonarr['msg'];
		    exit();
		}
		 
		$result = $jsonarr['result'];
		if($result['issign'] == 1) echo '已签收'.'<br>';
		else echo '未签收'.'<br>';
		foreach($result['list'] as $val)
		{
		    echo $val['time'].' '.$val['status'].'<br>';
		}


	}

	
}