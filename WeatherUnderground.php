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

	public $weather = null;
	
	private $cache_options = array(
		'cacheDir' => CACHE_DIR,
		'lifeTime' => LIFE_TIME
	);
	
	/**
	 * WUGのAPIを叩く
	 * @param $query string
	 * @return XML
	 */
	protected function getWeather($query){
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