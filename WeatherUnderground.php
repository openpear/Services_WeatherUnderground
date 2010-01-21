<?php
/**
 *  Services_WeatherUnderground 0.1.0
 *
 *  @author	    FreeBSE <freebse@live.jp> <http://panasocli.cc/wordpress>
 *  @package	Services_WeatherUnderground
 *  @version	Services_WeatherUnderground v 0.1.0 2009/01/21
 *
 */

//API URL
define('WG_API_AP', 'http://api.wunderground.com/auto/wui/geo/WXCurrentObXML/index.xml');

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
	 * @param $query string 今のところは
	 * プロパティに代入
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
		require_once 'HTTP/Client.php';
		$client = new HTTP_Client();
		$client->get($this->makeUrl($query));
                $response = $client->currentResponse();
                $response['body'] = mb_convert_encoding($response['body'], 'UTF-8', 'auto');
                return $response['body'];
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
	 * わかりやすい形で天気情報を取得する
	 * @return Array
	 */
	public function getWeatherData(){
	    if(!$this->weather['station_id']){
		return CITY_NOT_FOUND;
	    }
	    switch($this->weather['wind_dir']){
		case 'NNW':
		    $win_dir = '北北西';
		    break;
		case 'NW':
		    $win_dir = '北西';
		    break;
		case 'WNW':
		    $win_dir = '西北西';
		    break;
		case 'N':
		    $win_dir = '北';
		    break;
		case 'E':
		    $win_dir = '東';
		    break;
		case 'NE':
		    $win_dir = '北東';
		    break;
		case 'NNE':
		    $win_dir = '北北東';
		    break;
		case 'ENE':
		    $win_dir = '東北東';
		    break;
		case 'S':
		    $win_dir = '南';
		    break;
		case 'SE':
		    $win_dir = '南東';
		    break;
		case 'SSE':
		    $win_dir = '南南東';
		    break;
		case 'ESE':
		    $win_dir = '東南東';
		    break;
		case 'WSW':
		    $win_dir = '西南西';
		    break;
		case 'SSW':
		    $win_dir = '南南西';
		    break;
		case 'SW':
		    $win_dir = '南西';
		    break;
	    }

	    //風速変換
	    $mph = $this->weather['wind_mph'] / 2 *1000 / 60 / 60;
	    $mph = (int) $mph;

	    $weather = array(
		//街
		'city' => $this->weather['display_location']['city'],
		//国
		'country' => $this->weather['display_location']['state_name'],
		//天気画像
		'image' => $this->weather['icon_url_base'] . $this->weather['icon'] . $this->weather['icon_url_name'],
		//観測地
		'observation_location' => $this->weather['observation_location']['city'],
		//天気
		//'weather' => $this->weather['weather'],
		//気温
		'temp' => $this->weather['temp_c'] . '℃',
		//湿度
		'humidity' => $this->weather['relative_humidity'],
		//風向
		'wind_dir' => $win_dir,
		//風速
		'wind_speed' => $mph . 'm/s',
		//気圧
		'pressure' => $this->weather['pressure_mb'] . ' hPa',
		    );
	    return $weather;
	}
}
?>