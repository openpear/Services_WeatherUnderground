<?php
/**
 *  Services_WeatherUnderground 0.2.0
 *
 *  @author	    FreeBSE <freebse@live.jp> <http://panasocli.cc/wordpress>
 *  @package	Services_WeatherUnderground
 *  @version	Services_WeatherUnderground v 0.2.0 2009/02/04
 *
 */

//API URL
define('WG_API_AP', 'http://api.wunderground.com/auto/wui/geo/WXCurrentObXML/index.xml');
define('MPH_MS', 0.44704);

//エラーコード
define('CITY_NOT_FOUND', 0x01);

interface WeatherUnderground{
    public function getWeatherData();
    public function getRawWeatherData();
}

class Services_WeatherUnderground implements WeatherUnderground {

	public $weather = null;

	/**
	 *コンストラクタ
	 *
	 * @param $query string
	 * 今のところはプロパティに代入
	 */
	public function __construct($query){
		$data = $this->getWeather($query);
		$this->weather = $this->toArray($data);
	}
	
	/**
	 * APIを叩く
	 * @param $query string 今のところは
	 * @return XML
	 */
	private final function getWeather($query){
		require_once('Cache/Lite.php');
		if(!is_dir('tmp')) mkdir('tmp');
		$options = array(
		    'cacheDir' => 'tmp/',
		    'lifeTime' => 3600
		);
		$id = sprintf('%s_%s', $query, date('H', time() - 3600));
		$Cache_Lite = new Cache_Lite($options);
		//もしキャッシュがあるんなら
		if ($Cache_Lite->get($id)) {
		    return $Cache_Lite->get($id);
		}else{
		    require_once 'HTTP/Client.php';
		    $client = new HTTP_Client();
		    $client->get($this->makeUrl($query));
		    $response = $client->currentResponse();
		    $response['body'] = mb_convert_encoding($response['body'], 'UTF-8', 'auto');
		    $r = $Cache_Lite->save($response['body'], $id);
		    return $response['body'];
		}
	}

	/**
	 * XMLを配列に変換する
	 *
	 * @param XML $data
	 * @return Array
	 */
	private final function toArray($data){
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
	private final function makeUrl($query){
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
		case 'NNW':
		    $wind_dir = '北北西';
		    break;
		case 'NW':
		    $wind_dir = '北西';
		    break;
		case 'WNW':
		    $wind_dir = '西北西';
		    break;
		case 'W':
		    $wind_dir = '西';
		    break;
		case 'West':
		    $wind_dir = '西';
		    break;
		case 'N':
		    $wind_dir = '北';
		    break;
		case 'North':
		    $wind_dir = '北';
		    break;
		case 'E':
		    $wind_dir = '東';
		    break;
		case 'East':
		    $wind_dir = '東';
		    break;
		case 'NE':
		    $wind_dir = '北東';
		    break;
		case 'NNE':
		    $wind_dir = '北北東';
		    break;
		case 'ENE':
		    $wind_dir = '東北東';
		    break;
		case 'S':
		    $wind_dir = '南';
		    break;
		case 'South':
		    $wind_dir = '南';
		    break;
		case 'SE':
		    $wind_dir = '南東';
		    break;
		case 'SSE':
		    $wind_dir = '南南東';
		    break;
		case 'ESE':
		    $wind_dir = '東南東';
		    break;
		case 'WSW':
		    $wind_dir = '西南西';
		    break;
		case 'SSW':
		    $wind_dir = '南南西';
		    break;
		case 'SW':
		    $wind_dir = '南西';
		    break;
		case 'Variable':
		    $wind_dir = 'N/A';
		    break;
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
	    $mph = (int) round($mph * MPH_MS, 2);
	    return $mph;
	}

	/**
	 * わかりやすい形で天気情報を取得する
	 * @return Array
	 */
	public function getWeatherData(){
	    if(!$this->weather['station_id']){
		return CITY_NOT_FOUND;
	    }

	    //風向情報の取得
	    $wind_dir = $this->getWindDir($this->weather['wind_dir']);

	    //風速変換
	    $mph = $this->convertMphToMetor($this->weather['wind_mph']);

	    $weather = array(
		//街
		'city' => $this->weather['display_location']['city'],
		//国
		'country' => $this->weather['display_location']['state_name'],
		//天気画像
		'image' => $this->weather['icon_url_base'] . $this->weather['icon'] . $this->weather['icon_url_name'],
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
		//風向
		'wind_dir' => $wind_dir,
		//風速
		'wind_speed' => $mph . 'm/s',
		//気圧
		'pressure' => $this->weather['pressure_mb'] . ' hPa',
		    );
	    return $weather;
	}
}
?>