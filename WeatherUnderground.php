<?php
/**
 *  Services_WeatherUnderground 0.0.1
 *
 *  @author	    FreeBSE <freebse@live.jp> <http://panasocli.cc/wordpress>
 *  @package	Services_WeatherUnderground
 *  @version	Services_WeatherUnderground v 0.0.1 2009/01/06
 *
 */

//API URL
define('WG_API_AP', 'http://api.wunderground.com/auto/wui/geo/WXCurrentObXML/index.xml');

//エラーコード
define('NOT_FOUND', 0x01);

interface WeatherUnderground{
    public function getWeatherData();
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
	 * 天気情報を取得する
	 * @return Array
	 */
	public function getWeatherData(){
	    if(!$this->weather['station_id']){
		return NOT_FOUND;
	    }
	    return $this->weather;
	}
}
?>