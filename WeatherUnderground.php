<?php
define('WG_API_GEO', 'http://api.wunderground.com/auto/wui/geo/GeoLookupXML/index.xml');
define('WG_API_AP', 'http://api.wunderground.com/auto/wui/geo/WXCurrentObXML/index.xml');

class Services_WeatherUnderground{

        public $weather = null;

	public function __construct($query){
		$this->weather = $this->getWG($query);
	}
	
	private final function getWG($query){
		require_once 'HTTP/Client.php';
                require_once 'XML/Unserializer.php';
                $xml = new XML_Unserializer();
                $xml->setOption('parseAttributes',true);
		$client = new HTTP_Client();
                echo $this->makeUrl($query);
		$client->get($this->makeUrl($query));
                $response = $client->currentResponse();
                $response['body'] = mb_convert_encoding($response['body'], 'UTF-8', 'auto');
                $xml->unserialize($response['body']);
                return $xml->getUnserializedData();
	}
	
	private final function makeUrl($query){
		return sprintf('%s?query=%s', WG_API_AP, $query);
	}
}
?>