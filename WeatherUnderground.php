<?php
define('WG_API_GEO', 'http://api.wunderground.com/auto/wui/geo/GeoLookupXML/index.xml');
define('WG_API_AP', 'http://api.wunderground.com/auto/wui/geo/WXCurrentObXML/index.xml');

class Services_WeatherUnderground{

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
	
	public function getWeatherData(){
	    return $this->weather;
	}
}
?>