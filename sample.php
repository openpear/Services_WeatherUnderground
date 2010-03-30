<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('../WeatherUnderground.php');
$wg = new Services_WeatherUnderground('Osaka');
if($wg->getWeatherData() === CITY_NOT_FOUND){
    echo sprintf('指定された観測地は見つかりませんでした「Error Code 0x%02X」', $wg->getWeatherData());
}else{
    print_r($wg->getWeatherData());
}

$weather = $wg->getWeatherData();
?>
<table border="1">
    <tr>
	<td>
	    国
	</td>
	<td>
	    <?php echo $weather['country'] ?>
	</td>
    </tr>
    <tr>
	<td>
	    街
	</td>
	<td>
	    <?php echo $weather['city'] ?>
	</td>
    </tr>
    <tr>
	<td>
	    観測地
	</td>
	<td>
	    <?php echo $weather['observation_location'] ?>
	</td>
    </tr>
    <tr>
	<td>
	    観測時間
	</td>
	<td>
	    <?php echo $weather['observation_time'] ?>
	</td>
    </tr>
    <tr>
	<td>
	    天気
	</td>
	<td>
	    <img src="<?php echo $weather['image'] ?>">
	</td>
    </tr>
    <tr>
	<td>
	    気温
	</td>
	<td>
	    <?php echo $weather['temp'] ?>
	</td>
    </tr>
    <tr>
	<td>
	    湿度
	</td>
	<td>
	    <?php echo $weather['humidity'] ?>
	</td>
    </tr>
    <tr>
	<td>
	    不快指数
	</td>
	<td>
	    <?php echo $weather['di'] ?>
	</td>
    </tr>
<tr>
	<td>
	    不快指数(体感)
	</td>
	<td>
	    <?php echo $weather['feel_di'] ?>
	</td>
    </tr>
    <tr>
	<td>
	    風速
	</td>
	<td>
	    <?php echo $weather['wind_speed'] ?>
	</td>
    </tr>
    <tr>
	<td>
	    風力
	</td>
	<td>
	    <?php echo $weather['wind_power'] ?>
	</td>
    </tr>
    <tr>
	<td>
	    風力ランク
	</td>
	<td>
	    <?php echo $weather['wind_power_exp'] ?>
	</td>
    </tr>
    <tr>
	<td>
	    海上情報
	</td>
	<td>
	    <?php echo $weather['sea_attention'] ?>
	</td>
    </tr>
    <tr>
	<td>
	    気圧
	</td>
	<td>
	    <?php echo $weather['pressure'] ?>
	</td>
    </tr>
</table>
<?php

$wg = new Services_WeatherUnderground('Test');
if($wg->getWeatherData() === CITY_NOT_FOUND){
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
