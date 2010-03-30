<?php
/**
 *  Services_WeatherUnderground 0.2.0
 *
 *  @author	    FreeBSE <freebse@live.jp> <http://panasocli.cc/wordpress>
 *  @package	Services_WeatherUnderground
 *  @version	Services_WeatherUnderground v 0.2.0
 *
 */

require_once '../WeatherUnderground/lib/core.php';

//恐らくディレクトリが変わってしまうのでこの設定だけここに
define('CACHE_BASE_DIR', dirname(__FILE__) . '/WeatherUnderground/' . CACHE_DIR);

class Services_WeatherUnderground extends WeatherUndergroundCore implements WeatherUnderground {

	/**
	 *コンストラクタ
	 *
	 * @param $query string
	 * 
	 */
	public function __construct($query){
		parent::__construct($query);
		$this->cache->cacheRemove();
	}
	
	/**
	 * WUGの生の天気情報を取得する
	 * @return Array
	 */
	public function getRawWeatherData(){
	    if(!$this->weather['station_id']){
		return CITY_NOT_FOUND;
	    }
	    return $this->weather;
	}
	
	/**
	 * わかりやすい形で天気情報を取得する
	 * @return Array
	 */
	public function getWeatherData(){
	    if(!$this->weather['station_id']){
		unset($this->weather);
		return CITY_NOT_FOUND;
	    }

	    if($_COOKIE['weather']){
		return unserialize($_COOKIE['weather']);
	    }

	    $weather = array(
		//街
		'city' => $this->weather['display_location']['city'],
		//国
		'country' => $this->weather['display_location']['state_name'],
		//天気画像
		'image' => $this->weatherIcon(),
		//観測地
		'observation_location' => $this->weather['observation_location']['city'],
		//観測時間
		'observation_time' => date("Y年m月d日 H時i分",strtotime(preg_replace("/Last Updated on |JST/", "", $this->weather['observation_time']))),
		//天気
		//'weather' => $this->weather['weather'],
		//気温
		'temp' => $this->weather['temp_c'] . '℃',
		//湿度
		'humidity' => $this->weather['relative_humidity'],
		//不快指数
		'di' => $this->di(),
		//体感
		'feel_di' => $this->feelDi($di),
		//風向
		'wind_dir' => $this->getWindDir($this->weather['wind_dir']),
		//風速
		'wind_speed' => $this->convertMphToMetor($this->weather['wind_mph']),
		//風力
		'wind_power' => $this->windPower($mph),
		//風力(表現)
		'wind_power_exp' => $this->windPowerExp($this->windPower($this->convertMphToMetor($this->weather['wind_mph']))),
		//海上
		'sea_attention' => $this->seaAttention($wind_power),
		//気圧
		'pressure' => $this->weather['pressure_mb'] . ' hPa',
	    );
	    ob_start();
	    setcookie('weather', serialize($weather), $_SERVER['REQUEST_TIME'] + 1800);
	    ob_end_clean();
	    return $weather;
	}
}
?>