<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('../WeatherUnderground.php');
$wg = new Services_WeatherUnderground('Osaka');

/*
foreach($wg->weather as $key => $val){
    echo $key , " = " , $val."<br>";
};
*/

echo $wg->getTemperature(),"<br>";
echo $wg->getPressure(),"<br>";
echo $wg->getWindDir(),"<br>";
echo $wg->getWindMph(),"<br>";
echo $wg->getWindstring(),"<br>";
?>
