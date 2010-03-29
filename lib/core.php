<?php
/**
 *  Services_WeatherUnderground 0.2.0
 *
 *  @author	    FreeBSE <freebse@live.jp> <http://panasocli.cc/wordpress>
 *  @package	Services_WeatherUnderground
 *  @version	Services_WeatherUnderground v 0.2.0
 *
 */

require_once '../WeatherUnderground/lib/interface.php';
require_once '../WeatherUnderground/lib/settings.php';
require_once '../WeatherUnderground/lib/error.php';
require_once '../WeatherUnderground/lib/cache.php';

abstract class WeatherUndergroundCore {

	protected $cache = null;
	public $weather = null;

	private $cache_options = array(
		'cacheDir' => CACHE_DIR,
		'lifeTime' => LIFE_TIME
	);

	/**
	 * コンストラクタで天気を一気に取得ぅぅぅ！
	 *
	 * @param string $query
	 */
	protected function __construct($query){
	    $this->cache = new WeatherUndergroundCache($this->cache_options);
	    $this->weather = $this->toArray($this->getWeather($query));
	}

	/**
	 * WUGのAPIを叩く
	 * @param $query string
	 * @return XML
	 */
	protected function getWeather($query){
	    if($this->cache->cacheGet($query)){
		return $this->cache->cacheGet($query);
	    }
	    require_once 'HTTP/Client.php';
	    $client = new HTTP_Client();
	    $client->get($this->makeUrl($query));
	    $response = $client->currentResponse();
	    $this->cache->cacheSet(mb_convert_encoding($response['body'], 'UTF-8', 'auto'), $id);
	    $data = $response['body'];
	    unset($query);
	    unset($response);
	    return $data;
	}

	/**
	 * XMLを配列に変換する
	 *
	 * @param XML $data
	 * @return Array
	 */
	final protected function toArray($data){
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
	protected function makeUrl($query){
		return sprintf('%s?query=%s', WG_API_AP, $query);
	}

	/**
	 * 天気アイコンをキャッシュしてから返す
	 *
	 * @return string
	 */
	protected function weatherIcon(){
	    //パーツでの利用を前提とした天気アイコンキャッシュ
	    if(!is_file('weather_img/' . $this->weather['icon'] . $this->weather['icon_url_name'])){
		return $this->weather['icon_url_base'] . $this->weather['icon'] . $this->weather['icon_url_name'];
	    }
	    if(!is_dir('weather_img') && is_writable('weather_img')) mkdir('weather_img');
	    if(!is_dir('weather_img')) return $this->weather['icon_url_base'] . $this->weather['icon'] . $this->weather['icon_url_name'];
	    file_put_contents('weather_img/' . $this->weather['icon'] . $this->weather['icon_url_name'], file_get_contents($this->weather['icon_url_base'] . $this->weather['icon'] . $this->weather['icon_url_name']));
	    return is_file('weather_img/' . $this->weather['icon'] . $this->weather['icon_url_name']) ? 'weather_img/' . $this->weather['icon'] . $this->weather['icon_url_name'] : $this->weather['icon_url_base'] . $this->weather['icon'] . $this->weather['icon_url_name'] ;
	}

	/**
	 * 風向を日本語に変換
	 *
	 * @param String $winddir
	 * @return String
	 */
	protected function getWindDir($winddir){
	    switch($winddir){
		case 'NNW':return '北北西';
		case 'NW':return '北西';
		case 'WNW':return '西北西';
		case 'W':return '西';
		case 'West':return '西';
		case 'N':return '北';
		case 'North':return '北';
		case 'E':return '東';
		case 'East':return '東';
		case 'NE':return '北東';
		case 'NNE':return '北北東';
		case 'ENE':return '東北東';
		case 'S':return '南';
		case 'South':return '南';
		case 'SE':return '南東';
		case 'SSE':return '南南東';
		case 'ESE':return '東南東';
		case 'WSW':return '西南西';
		case 'SSW':return '南南西';
		case 'SW':return '南西';
		case 'Variable':return '変則';
	    }
	}

	/**
	 * 風速変換を行います
	 *
	 * @param int $mph Mile Per Hour
	 * @return int Metor
	 */
	protected function convertMphToMetor($mph){
	    $mph = substr(sprintf('%01.2f',$mph * MPH_MS), 0, 4);
	    return $mph == 0 ? '静穏' : $mph . ' m/s';
	}

	/**
	 * 不快指数
	 * @return int
	 */
	protected function di(){
	    //不快指数
	    $h = preg_replace('/%| /', '', $this->weather['relative_humidity']);
	    $di = 0.81 * $this->weather['temp_c'] + 0.01 * $h * (0.99 * $this->weather['temp_c'] - 14.3 + 46.3);
	    return substr(sprintf('%01.2f',$di), 0, 4);
	}

	/**
	 * 不快指数(体感)
	 * @param int $di 不快指数値
	 * @return string
	 */
	protected function feelDi($di){
	    if($di < 55) return '寒い';
	    if($di >= 55 && $di < 60) return '肌寒い';
	    if($di >= 60 && $di < 65) return '無感';
	    if($di >= 65 && $di < 70) return '快適';
	    if($di >= 70 && $di < 75) return '暑くない';
	    if($di >= 75 && $di < 80) return 'やや暑い';
	    if($di >= 80 && $di < 85) return '汗が出る';
	    if($di >= 85) return '暑すぎる';
	}

	/**
	 * 風力変換
	 * @param int $mph 風速
	 */
	protected function windPower($mph)
	{
	    if($mph >= 0 && $mph < 1) return '静穏';
	    if($mph >= 1 && $mph < 2) return 1;
	    if($mph >= 2 && $mph <= 3) return 2;
	    if($mph > 3 && $mph <= 5) return 3;
	    if($mph > 5 && $mph <= 7) return 4;
	    if($mph > 7 && $mph <= 10) return 5;
	    if($mph > 10 && $mph <= 13) return 6;
	    if($mph > 13 && $mph <= 17) return 7;
	    if($mph > 17 && $mph <= 20) return 8;
	    if($mph > 20 && $mph <= 24) return 9;
	    if($mph > 24 && $mph <= 28) return 10;
	    if($mph > 28 && $mph <= 32) return 11;
	    if($mph > 32) return 12;
	}

	/**
	 * 風力ランク変換
	 * @param int $wind_power 風力
	 */
	protected function windPowerExp($wind_power){
	    switch($wind_power){
		case 0:return '静穏';
		case 1:return '至軽風';
		case 2:return '軽風';
		case 3:return '軟風';
		case 4:return '和風';
		case 5:return '疾風';
		case 6:return '雄風';
		case 7:return '強風';
		case 8:return '疾強風';
		case 9:return '大強風';
		case 10:return '全強風';
		case 11:return '暴風';
		case 12:return '台風';
		default:return 'N/A';
	    }
	    return $wind_power_exp;
	}

	/**
	 * 海上警報
	 * @param int $wind_power
	 * @return string
	 */
	protected function seaAttention($wind_power){
	    if($wind_power < 7) return 'None';
	    if($wind_power === 7) return '海上風警報';
	    if($wind_power >= 8 && $wind_power <= 9) return '海上強風警報';
	    if($wind_power >= 10 && $wind_power <= 11) return '海上暴風警報';
	    if($wind_power >= 12) return '海上台風警報';
	}
}
?>