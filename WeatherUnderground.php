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
		$this->weather = $this->getWG($query);
	}
	
	/**
	 * APIを叩くところ
	 * @param $query string 今のところは
	 * Unserializedの結果を返す
	 * @return array
	 * TODO まだまだリファクタ
	 */
	private final function getWG($query){
		require_once 'HTTP/Client.php';
                require_once 'XML/Unserializer.php';
                $xml = new XML_Unserializer();
                $xml->setOption('parseAttributes',true);
		$client = new HTTP_Client();
//                echo $this->makeUrl($query);
		$client->get($this->makeUrl($query));
                $response = $client->currentResponse();
                $response['body'] = mb_convert_encoding($response['body'], 'UTF-8', 'auto');
                $xml->unserialize($response['body']);
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
	 * 温度の取得 摂氏と華氏
	 * @return string
	 */
	public function getTemperature(){
		return $this->weather['temperature_string'];
	}
	
	/**
	 * 気圧の取得
	 * @return int
	 */
	public function getPressure(){
		return $this->weather['pressure_mb'];
	} 
	
	/**
	 * 風向の取得
	 * @return string
	 */
	public function getWindDir(){
		return $this->weather['wind_dir'];
	}
	
	/**
	 * 風速の取得
	 * @return int
	 */
	public function getWindMph(){
		return $this->weather['wind_mph'];
	}
	
	/**
	 * 現在の風の全情報を返す
	 * @return string
	 */
	public function getWindString(){
		return $this->weather['wind_string'];
	}
}
?>