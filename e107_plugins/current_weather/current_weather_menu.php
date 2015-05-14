<?php
/**
 * Current Weather plugin for CMS e107 v2
 *
 * @author OxigenO2 (oxigen.rg@gmail.com)
 * @copyright Copyright (C) 2014 OxigenO2 
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 * @link http://oxigen.mablog.eu/
 */
if (!defined('e107_INIT')) { exit; }

e107::lan('current_weather',false, true);

$weatherCity = e107::getPlugPref('current_weather', 'current_weather_city');
$weatherUnits = e107::getPlugPref('current_weather', 'current_weather_unit');
$weatherScheme = e107::getPlugPref('current_weather', 'current_weather_scheme');
$weatherSchow = e107::getPlugPref('current_weather', 'current_weather_deatils');
$weatherLink = e107::getPlugPref('current_weather', 'current_weather_link');
$weatherDegrees = array(
      'metric' => 'C',
      'imperial' => 'F');
$weatherDegree = $weatherDegrees[$weatherUnits];

$url = "http://api.openweathermap.org/data/2.5/weather?q=$weatherCity&mode=json&units=$weatherUnits";
$options = [
    'http' => [
        'timeout' => 1
    ]
];
$context = stream_context_create($options);
$weatherFile = @file_get_contents($url, false, $context);
if ($weatherFile === false)
{
$text = '
<div class="weather">
  <div class="wc_icons">
    <img src="'.e_PLUGIN_ABS.'current_weather/images/'.$weatherScheme.'/na.png" alt="N/A">        
  </div>
  <div class="wc_nodata">
    <p>'.LAN_WEATHER_MENU_NO_FILE.'</p>
  </div>
</div>';
}
else
{
    htmlspecialchars(substr($weatherFile, 0, 5000), ENT_QUOTES);
}

$weatherData = json_decode($weatherFile, true); 
$weatherIcon = $weatherData['weather'][0]['icon'];
$weatherDescription = $weatherData['weather'][0]['id'];
$weatherTemp = round($weatherData['main']['temp']*10)/10;
$weatherHumidity = $weatherData['main']['humidity'];
$weatherWind = round($weatherData['wind']['speed']*100)/100;
$weatherSunrise = strftime("%H:%M",$weatherData['sys']['sunrise']);   
$weatherSunset = strftime("%H:%M",$weatherData['sys']['sunset']);
$weatherNotfound = $weatherData['cod'];

if ($weatherNotfound=='404')
{
$text = '
<div class="weather">
  <div class="wc_icons">
    <img src="'.e_PLUGIN_ABS.'current_weather/images/'.$weatherScheme.'/na.png" alt="N/A">        
  </div>
  <div class="wc_nodata">
    <p>'.LAN_WEATHER_MENU_NOTFOUND.'</p>
  </div>
</div>';
}
if ($weatherNotfound=='200')
{

$cloudiness = array(
200 => LAN_WEATHER_MENU_200,
201 => LAN_WEATHER_MENU_201,
202 => LAN_WEATHER_MENU_202,
210 => LAN_WEATHER_MENU_210,
211 => LAN_WEATHER_MENU_211,
212 => LAN_WEATHER_MENU_212,
221 => LAN_WEATHER_MENU_221,
230 => LAN_WEATHER_MENU_230,
231 => LAN_WEATHER_MENU_231,
232 => LAN_WEATHER_MENU_232,
300 => LAN_WEATHER_MENU_300,
301 => LAN_WEATHER_MENU_301,
302 => LAN_WEATHER_MENU_302,
310 => LAN_WEATHER_MENU_310,
311 => LAN_WEATHER_MENU_311,
312 => LAN_WEATHER_MENU_312,
321 => LAN_WEATHER_MENU_321,
500 => LAN_WEATHER_MENU_500,
501 => LAN_WEATHER_MENU_501,
502 => LAN_WEATHER_MENU_502,
503 => LAN_WEATHER_MENU_503,
504 => LAN_WEATHER_MENU_504,
511 => LAN_WEATHER_MENU_511,
520 => LAN_WEATHER_MENU_520,
521 => LAN_WEATHER_MENU_521,
522 => LAN_WEATHER_MENU_522,
600 => LAN_WEATHER_MENU_600,
601 => LAN_WEATHER_MENU_601,
602 => LAN_WEATHER_MENU_602,
611 => LAN_WEATHER_MENU_611,
621 => LAN_WEATHER_MENU_621,
701 => LAN_WEATHER_MENU_701,
711 => LAN_WEATHER_MENU_711,
721 => LAN_WEATHER_MENU_721,
731 => LAN_WEATHER_MENU_731,
741 => LAN_WEATHER_MENU_741,
800 => LAN_WEATHER_MENU_800,
801 => LAN_WEATHER_MENU_801,
802 => LAN_WEATHER_MENU_802,
803 => LAN_WEATHER_MENU_803,
804 => LAN_WEATHER_MENU_804,
900 => LAN_WEATHER_MENU_900,
901 => LAN_WEATHER_MENU_901,
902 => LAN_WEATHER_MENU_902,
903 => LAN_WEATHER_MENU_903,
904 => LAN_WEATHER_MENU_904,
905 => LAN_WEATHER_MENU_905,
906 => LAN_WEATHER_MENU_906,
950 => LAN_WEATHER_MENU_950,
951 => LAN_WEATHER_MENU_951,
952 => LAN_WEATHER_MENU_952,
953 => LAN_WEATHER_MENU_953, 
954 => LAN_WEATHER_MENU_954,
955 => LAN_WEATHER_MENU_955,
956 => LAN_WEATHER_MENU_956,
957 => LAN_WEATHER_MENU_957,
958 => LAN_WEATHER_MENU_958, 
959 => LAN_WEATHER_MENU_959,
960 => LAN_WEATHER_MENU_960, 
961 => LAN_WEATHER_MENU_961,
962 => LAN_WEATHER_MENU_962,
);





if ($weatherFile)
{

$text =
$tp->parseTemplate($template['cw_head'], false, $sc);

$text .='
<div class="weather">
  <div class="wc_head">
    <h4>'.$weatherCity.'&nbsp;'.$weatherTemp.'&deg;'.$weatherDegree.'</h4>
    <p>'.$cloudiness[$weatherDescription].'</p>
  </div>
  <div class="wc_icons">
  <img src="'.e_PLUGIN_ABS.'current_weather/images/'.$weatherScheme.'/'.$weatherIcon.'.png" alt="'.$weatherIcon.'">        
  </div>';
if ($weatherSchow)
{  
$text .= '
  <div class="wc_detail">
    <b>'.LAN_WEATHER_MENU_DETAILS.'&#58;</b><br />
    '.LAN_WEATHER_MENU_WIND.'&#58;&nbsp;'.$weatherWind.'&nbsp;m/s<br />
    '.LAN_WEATHER_MENU_HUMIDITY.'&#58;&nbsp;'.$weatherHumidity.'%<br />
    '.LAN_WEATHER_MENU_SUNRISE.'&#58;&nbsp;'.$weatherSunrise.'<br />
    '.LAN_WEATHER_MENU_SUNSET.'&#58;&nbsp;'.$weatherSunset.'<br />
    </div>';
}

if ($weatherLink)
{  
$text .= '
  <div class="wc_detail">
    <a href="http://openweathermap.org/" target="_blank"><span class="hidden-sm">'.LAN_WEATHER_MENU_LINK.'</span></a>
  </div>';
}
$text .= '
</div>';
}
else
{
$text = '
<div class="weather">
  <div class="wc_icons">
    <img src="'.e_PLUGIN_ABS.'current_weather/images/'.$weatherScheme.'/na.png" alt="N/A">        
  </div>
  <div class="wc_nodata">
    <p>'.LAN_WEATHER_MENU_NA.'</p>
  </div>
</div>';
}

}
$ns->tablerender(LAN_WEATHER_MENU_TITLE, $text, 'current_weather');
?>