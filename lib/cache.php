<?php
/**
 *  Services_WeatherUnderground 0.2.0
 *
 *  @author	    FreeBSE <freebse@live.jp> <http://panasocli.cc/wordpress>
 *  @package	Services_WeatherUnderground
 *  @version	Services_WeatherUnderground v 0.2.0
 *
 */

class WeatherUndergroundCache {

	public $cache = null;

	public function  __construct($cache_options) {
	    require_once('Cache/Lite.php');
	    $this->cache = new Cache_Lite($cache_options);
	}

	/**
	 *
	 * 指定されたキャッシュがあるかチェックする
	 *
	 * @param Object $Cache_Lite
	 * @param String $id
	 * @return 成功:キャッシュデータ 失敗:FALSE
	 */
	public function cacheCheck($cache, $id){
	    if(!is_dir('tmp')) mkdir('tmp');
	    if (!$cache->get($id)) {
		return false;
	    }
	    return $cache->get($id);
	}

	/**
	 *
	 * キャッシュを取得する
	 *
	 * @param String $id
	 * @return 成功:キャッシュデータ 失敗:FALSE
	 */
	public function cacheGet($id){
	    if($this->cacheCheck($this->cache, $id)){
		return false;
	    }
	    return $this->cache->get($id);
	}

	/**
	 *
	 * キャッシュを作る
	 *
	 * @param 天気データ $data
	 * @param String $id
	 * @return 失敗:FALSE
	 */
	public function cacheSet($data, $id){
	    if(!is_dir('tmp')) mkdir('tmp');
	    if(strpos(PHP_OS, 'WIN') !== 0) chmod('tmp', 0777);
	    if(!$this->cacheCheck($this->cache, $id)){
		$r = $this->cache->save($data, $id);
		if($r === false){
			return $r;
		}
	    }
	    unset($this->cache);
	    return true;
	}

	/**
	 * キャッシュを削除する
	 */
	public function cacheRemove(){
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
}
?>
