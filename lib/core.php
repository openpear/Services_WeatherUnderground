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
	    $body = mb_convert_encoding($response['body'], 'UTF-8', 'auto');
	    $this->cache->cacheSet($body, $id);
	    unset($query);
	    unset($response);
	    return $body;
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
	    $img_url = $this->weather['icon_url_base'] . $this->weather['icon'] . $this->weather['icon_url_name'];
	    $icon = $this->weather['icon'] . $this->weather['icon_url_name'];
	    //パーツでの利用を前提とした天気アイコンキャッシュ
	    if(!is_dir('weather_img') && is_writable('weather_img')) mkdir('weather_img');
	    if(!is_dir('weather_img')) return $img_url;
//	    $img = imagecreatefromgif($img_url);
//	    imagegif($img, 'weather_img/' . $icon);
//	    imagedestroy($img);
	    file_put_contents('weather_img/' . $icon, file_get_contents($img_url));
	    return is_file('weather_img/' . $icon) ? 'weather_img/' . $icon : $this->weather['icon_url_base'] . $this->weather['icon'] . $this->weather['icon_url_name'] ;
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
	    return substr(sprintf('%01.2f',$mph * MPH_MS), 0, 4);
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
	    if($mph >= 0 && $mph < 1) $wind_power = '静穏';
	    if($mph >= 1 && $mph < 2) $wind_power = 1;
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
	 * 風力表現変換
	 * @param int $wind_power 風力
	 */
	protected function windPowerExp($wind_power){
	    switch($wind_power){
		case 0:$wind_power_exp = '静穏';break;
		case 1:$wind_power_exp = '至軽風';break;
		case 2:$wind_power_exp = '軽風';break;
		case 3:$wind_power_exp = '軟風';break;
		case 4:$wind_power_exp = '和風';break;
		case 5:$wind_power_exp = '疾風';break;
		case 6:$wind_power_exp = '雄風';break;
		case 7:$wind_power_exp = '強風';break;
		case 8:$wind_power_exp = '疾強風';break;
		case 9:$wind_power_exp = '大強風';break;
		case 10:$wind_power_exp = '全強風';break;
		case 11:$wind_power_exp = '暴風';break;
		case 12:$wind_power_exp = '台風';break;
		default:$wind_power_exp = 'N/A';break;
	    }
	    return $wind_power_exp;
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
}
?>