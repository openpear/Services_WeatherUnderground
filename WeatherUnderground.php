<?php
/**
 *  Services_WeatherUnderground 0.2.0
 *
 *  @author	    FreeBSE <freebse@live.jp> <http://panasocli.cc/wordpress>
 *  @package	Services_WeatherUnderground
 *  @version	Services_WeatherUnderground v 0.2.0
 *
 */

require_once '../WeatherUnderground/lib/settings.php';
require_once '../WeatherUnderground/lib/error.php';
require_once '../WeatherUnderground/lib/interface.php';

//恐らくディレクトリが変わってしまうのでこの設定だけここに
define('CACHE_BASE_DIR', dirname(__FILE__) . '/WeatherUnderground/' . CACHE_DIR);

class Services_WeatherUnderground implements WeatherUnderground {

	public $weather = null;
	
	private $cache_options = array(
		'cacheDir' => CACHE_DIR,
		'lifeTime' => LIFE_TIME
	);

	/**
	 *コンストラクタ
	 *
	 * @param $query string
	 * プロパティに代入
	 */
	public function __construct($query){
		$this->cacheRemove();
		$data = $this->getWeather($query);
		$this->weather = $this->toArray($data);
	}
	
	/**
	 * WUGのAPIを叩く
	 * @param $query string
	 * @return XML
	 */
	private function getWeather($query){
	    $id = $query;
	    if($this->cacheGet($id)){
		return $this->cacheGet($id);
	    }
	    require_once 'HTTP/Client.php';
	    $client = new HTTP_Client();
	    $client->get($this->makeUrl($query));
	    $response = $client->currentResponse();
	    $response['body'] = mb_convert_encoding($response['body'], 'UTF-8', 'auto');
	    $this->cacheSet($response['body'], $id);
	    unset($id);
	    unset($query);
	    return $response['body'];
	}

	/**
	 *
	 * 指定されたキャッシュがあるかチェックする
	 *
	 * @param Object $Cache_Lite
	 * @param String $id
	 * @return 成功:キャッシュデータ 失敗:FALSE
	 */
	private function cacheCheck($Cache_Lite, $id){
	    if(!is_dir('tmp')) mkdir('tmp');
	    if (!$Cache_Lite->get($id)) {
		return false;
	    }
	    return $Cache_Lite->get($id);
	}

	/**
	 *
	 * キャッシュを取得する
	 *
	 * @param String $id
	 * @return 成功:キャッシュデータ 失敗:FALSE
	 */
	private function cacheGet($id){
	    require_once('Cache/Lite.php');
	    $Cache_Lite = new Cache_Lite($this->cache_options);
	    if(!$this->cacheCheck($Cache_Lite, $id)){
		return false;
	    }
	    return $Cache_Lite->get($id);
	}

	/**
	 *
	 * キャッシュを作る
	 *
	 * @param 天気データ $data
	 * @param String $id
	 * @return 失敗:FALSE
	 */
	private function cacheSet($data, $id){
	    if(!is_dir('tmp')) mkdir('tmp');
	    if(strpos(PHP_OS, 'WIN') !== 0) chmod('tmp', 0777);
	    $Cache_Lite = new Cache_Lite($this->cache_options);
	    if(!$this->cacheCheck($Cache_Lite, $id)){
		$r = $Cache_Lite->save($data, $id);
		if($r === false){
			return $r;
		}
	    }
	    unset($Cache_Lite);
	    return true;
	}

	/**
	 * キャッシュを削除する
	 */
	private function cacheRemove(){
	    if(!is_dir(CACHE_BASE_DIR)) return false;
	    $dir = scandir(CACHE_BASE_DIR);
	    foreach($dir as $val){
		if($val !== '.' && $val !== '..' && ((int) (time() - filemtime(CACHE_BASE_DIR . $val)) > LIFE_TIME)){
		    if(file_exists(CACHE_BASE_DIR . $val)){
			unlink(CACHE_BASE_DIR . $val);
		    }
		}
	    }
	    unset($dir);
	}

	/**
	 * XMLを配列に変換する
	 *
	 * @param XML $data
	 * @return Array
	 */
	private function toArray($data){
	    require_once 'XML/Unserializer.php';
	    $xml = new XML_Unserializer();
	    $xml->setOption('parseAttributes',true);
	    $xml->unserialize($data);
	    return $xml->getUnserializedData();
	}
	
	/**
	 * WeatherUndergroundb API REST URLの生成
	 * @return string
	 */
	private function makeUrl($query){
		return sprintf('%s?query=%s', WG_API_AP, $query);
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
	 * 風向を日本語に変換
	 *
	 * @param String $winddir
	 * @return String
	 */
	protected function getWindDir($winddir){
	    switch($winddir){
		case 'NNW':$wind_dir = '北北西';break;
		case 'NW':$wind_dir = '北西';break;
		case 'WNW':$wind_dir = '西北西';break;
		case 'W':$wind_dir = '西';break;
		case 'West':$wind_dir = '西';break;
		case 'N':$wind_dir = '北';break;
		case 'North':$wind_dir = '北';break;
		case 'E':$wind_dir = '東';break;
		case 'East':$wind_dir = '東';break;
		case 'NE':$wind_dir = '北東';break;
		case 'NNE':$wind_dir = '北北東';break;
		case 'ENE':$wind_dir = '東北東';break;
		case 'S':$wind_dir = '南';break;
		case 'South':$wind_dir = '南';break;
		case 'SE':$wind_dir = '南東';break;
		case 'SSE':$wind_dir = '南南東';break;
		case 'ESE':$wind_dir = '東南東';break;
		case 'WSW':$wind_dir = '西南西';break;
		case 'SSW':$wind_dir = '南南西';break;
		case 'SW':$wind_dir = '南西';break;
		case 'Variable':$wind_dir = '静穏';break;
	    }
	    return $wind_dir;
	}

	/**
	 * 風速変換を行います
	 *
	 * @param int $mph Mile Per Hour
	 * @return int Metor
	 */
	protected function convertMphToMetor($mph){
	    $mph = round($mph * MPH_MS, 1);
	    return $mph;
	}

	/**
	 * 不快指数
	 * @return int
	 */
	protected function di(){
	    //不快指数
	    $h = preg_replace("/%| /", "", $this->weather['relative_humidity']);
	    $di = 0.81 * $this->weather['temp_c'] + 0.01 * $h * (0.99 * $this->weather['temp_c'] - 14.3 + 46.3);
	    return $di;
	}

	/**
	 * 不快指数(体感)
	 * @param int $di 不快指数値
	 * @return string
	 */
	protected function feelDi($di){
	    if($di < 55) $feel_di = '寒い';
	    if($di >= 55 && $di < 60) $feel_di = '肌寒い';
	    if($di >= 60 && $di < 65) $feel_di = '無感';
	    if($di >= 65 && $di < 70) $feel_di = '快適';
	    if($di >= 70 && $di < 75) $feel_di = '暑くない';
	    if($di >= 75 && $di < 80) $feel_di = 'やや暑い';
	    if($di >= 80 && $di < 85) $feel_di = '汗が出る';
	    if($di >= 85) $feel_di = '暑すぎる';
	    return $feel_di;
	}
	
	/**
	 * 風力変換
	 * @param int $mph 風速
	 */
	protected function windPower($mph)
	{
	    if($mph === 0) $wind_power = '静穏';
	    if($mph === 1) $wind_power = 1;
	    if($mph >= 2 && $mph <= 3) $wind_power = 2;
	    if($mph > 3 && $mph <= 5) $wind_power = 3;
	    if($mph > 5 && $mph <= 7) $wind_power = 4;
	    if($mph > 7 && $mph <= 10) $wind_power = 5;
	    if($mph > 10 && $mph <= 13) $wind_power = 6;
	    if($mph > 13 && $mph <= 17) $wind_power = 7;
	    if($mph > 17 && $mph <= 20) $wind_power = 8;
	    if($mph > 20 && $mph <= 24) $wind_power = 9;
	    if($mph > 24 && $mph <= 28) $wind_power = 10;
	    if($mph > 28 && $mph <= 32) $wind_power = 11;
	    if($mph > 32) $wind_power = 12;
	    return $wind_power;
	}
	
	/**
	 * 海上警報
	 * @param int $wind_power
	 * @return string
	 */
	protected function seaAttention($wind_power){
	    if($wind_power < 7) $sea_attention = 'None';
	    if($wind_power === 7) $sea_attention = '海上風警報';
	    if($wind_power >= 8 && $wind_power <= 9) $sea_attention = '海上強風警報';
	    if($wind_power >= 10 && $wind_power <= 11) $sea_attention = '海上暴風警報';
	    if($wind_power >= 12) $sea_attention = '海上台風警報';
	    return $sea_attention;
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

	    //風向情報の取得
	    $wind_dir = $this->getWindDir($this->weather['wind_dir']);

	    //風速変換
	    $mph = $this->convertMphToMetor($this->weather['wind_mph']);
	    
		//風速変換(無風時)
	    $mph = $mph == 0 ? '静穏' : $mph . ' m/s';
	    
	    //不快指数
	    $di = $this->di();
	    
	    //不快指数(体感)
	    $feel_di = $this->feelDi($di);
	    
	    //風力変換
	    $wind_power = $this->windPower($mph);
	    
	    //海上警報
	    $sea_attention = $this->seaAttention($wind_power);

	    $img_url = $this->weather['icon_url_base'] . $this->weather['icon'] . $this->weather['icon_url_name'];
	    $icon = $this->weather['icon'] . $this->weather['icon_url_name'];
	    //パーツでの利用を前提とした天気アイコンキャッシュ
	    $img = imagecreatefromgif($img_url);
	    if(!is_dir('weather_img') && is_written('weather_img')) mkdir('weather_img');
	    imagegif($img, 'weather_img/' . $icon);
	    imagedestroy($img);
	    $icon = is_file('weather_img/' . $icon) ? 'weather_img/' . $icon : $this->weather['icon_url_base'] . $this->weather['icon'] . $this->weather['icon_url_name'] ;

	    $weather = array(
		//街
		'city' => $this->weather['display_location']['city'],
		//国
		'country' => $this->weather['display_location']['state_name'],
		//天気画像
		'image' => $icon,
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
		'di' => $di,
		//体感
		'feel_di' => $feel_di,
		//風向
		'wind_dir' => $wind_dir,
		//風速
		'wind_speed' => $mph,
		//風力
		'wind_power' => $wind_power,
		//海上
		'sea_attention' => $sea_attention,
		//気圧
		'pressure' => $this->weather['pressure_mb'] . ' hPa',
		    );
	    return $weather;
	}
}
?>