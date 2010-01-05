<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('../WeatherUnderground.php');
$wg = new Services_WeatherUnderground('Osaka');
if($wg->getWeatherData() === 0x01){
    echo sprintf('指定された観測地は見つかりませんでした「Error Code 0x%02X」', $wg->getWeatherData());
}else{
    print_r($wg->getWeatherData());
}

/*
foreach($wg->weather as $key => $val){
    echo $key , " = " , $val."<br>";
};
*/

?>
