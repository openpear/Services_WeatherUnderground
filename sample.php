<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('../WeatherUnderground.php');
$wg = new Services_WeatherUnderground('Osaka');
print_r($wg->getWeatherData());

/*
foreach($wg->weather as $key => $val){
    echo $key , " = " , $val."<br>";
};
*/

?>
