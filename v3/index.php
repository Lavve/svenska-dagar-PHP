<?
error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('Europe/Stockholm');
require('day_functions.php');
require('namedays.php');

//Set up defaults
$api_version = '3.1.1';
$startyear = date('Y');
$endyear = date('Y');
$startmonth = '01';
$endmonth = '12';
$startday = '01';
$endday = '';
$params = '';

if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
  $uri_and_params = explode('?', $_SERVER['REQUEST_URI']);
  $uri = $uri_and_params[0];
  $params = $uri_and_params[1];

  $params = preg_replace('/[&]?(statistik)[&]?/', '', $params);
} else {
  $uri = $_SERVER['REQUEST_URI'];
}
$request_parts = explode('/', $params);

//Check year
if (!isset($request_parts[0]) || is_null($request_parts[0]) || $request_parts[0] === '') {
  $startyear=date('Y');
  $startmonth=date('m');
  $startday=date('d');
  $endyear=date('Y');
  $endmonth=date('m');
  $endday=date('d');
} elseif (is_numeric($request_parts[0]) && $request_parts[0] > 1901 && $request_parts[0] < 3000) {
  $startyear = $request_parts[0];
  $endyear = $startyear;
} else {
  return_error('Felaktigt årtal');
}

//Check month
if (!isset($request_parts[1]) || is_null($request_parts[1]) || $request_parts[1] === '') {
  //Use defaults
} elseif (is_numeric($request_parts[1]) && $request_parts[1] > 0 && $request_parts[1] < 13) {
  $startmonth = $request_parts[1];
  $endmonth = $request_parts[1];
} else {
  return_error('Felaktig månad');
}

//check day
if ($endday !== '') {
  //Use set values
} elseif (!isset($request_parts[2]) || is_null($request_parts[2]) || $request_parts[2] === '') {
  $startday = '01';
  $endday = cal_days_in_month(CAL_GREGORIAN, $startmonth, $startyear);
} elseif (is_numeric($request_parts[2]) && $request_parts[2] > 0 && $request_parts[2] <= cal_days_in_month(CAL_GREGORIAN, $startmonth, $startyear)) {
  $startday = $request_parts[2];
  $endday = $request_parts[2];
} else {
  return_error('Felaktig dag '.$request_parts[2]);
}

//Format the dates
$startunixdate = mktime(0, 0, 0, $startmonth, $startday, $startyear);
$endunixdate = mktime(0, 0, 0, $endmonth, $endday, $endyear);

$output['_timestamp'] = time();
$output['api version'] = 'v' . $api_version;
$output['uri'] = $_SERVER['REQUEST_URI'];
$output['startdatum'] = date('Y-m-d', $startunixdate);
$output['slutdatum'] = date('Y-m-d', $endunixdate);
$output['skottår'] = date('L', $startunixdate) ? true : false;

//Time to loop it!
$number_of_days = 0;
$number_of_workfree = 0;
$number_of_work = 0;
$number_of_reddays = 0;
$number_of_weekdays = [];
$squeeze_days = array();
$squeeze_days['totalt antal'] = 0;

$loopdate = $startunixdate;

while ($loopdate <= $endunixdate) {
  $output['dagar'][$number_of_days]['datum'] = date('Y-m-d', $loopdate);
  
  //check weekday
  list($weekday, $workfree, $redday) = get_weekday($loopdate);
  $output['dagar'][$number_of_days]['veckodag'] = $weekday;
  $output['dagar'][$number_of_days]['arbetsfri dag'] = $workfree;
  $output['dagar'][$number_of_days]['röd dag'] = $redday;
  
  //Check if day is holiday
  if ($type_and_day = get_holiday($loopdate)){
    list($type, $day, $workfree_holiday, $redday_holiday) = $type_and_day;
    $output['dagar'][$number_of_days][$type] = $day;
    
    if ($workfree || $workfree_holiday){
      $workfree = true;  
    }
    
    if ($redday || $redday_holiday){
      $redday = true;
    }
    
    $output['dagar'][$number_of_days]['arbetsfri dag'] = $workfree;
    $output['dagar'][$number_of_days]['röd dag'] = $redday;
  }

  if ($redday) {
    $number_of_reddays++;
  }
  
  if ($workfree) {
    $number_of_workfree++;
  } else {
    $number_of_work++;
    $next_day = get_holiday(strtotime('+1 day',$loopdate));

    if ($next_day[0] === 'helgdag'){
      $output['dagar'][$number_of_days]['dag före arbetsfri helgdag'] = true;
    }
    
    if (is_workfree($loopdate,'+1 day') && is_workfree($loopdate,'-1 day')){
      $output['dagar'][$number_of_days]['klämdag'] = true;
      $squeeze_days['totalt antal']++;
      $squeeze_days[] = date('Y-m-d', $loopdate);
    }
  }
  
  //Get namedays
  if ($names = get_nameday($loopdate)){
    $output['dagar'][$number_of_days]['namnsdag'] = $names;
  }

  if (array_key_exists($weekday, $number_of_weekdays)) {
    $number_of_weekdays[$weekday]++;
  } else {
    $number_of_weekdays[$weekday] = 1;
  }
  
  //Keep this last in the loop please!
  $loopdate = strtotime('+1 day', $loopdate);
  $number_of_days++;
}

function count_names ($dagar) {
  $number_of_names = 0;
  foreach($dagar as $day) {
    if (array_key_exists('namnsdag', $day)) {
      $number_of_names += count($day['namnsdag']);
    }
  }

  return $number_of_names;
}

$output['contributors']['developers'] = [
  'dryg.net' => 'https://dryg.net/',
  'Lavve' => 'https://github.com/Lavve/'
];
$output['contributors']['other'] = [
  'Dagens namnsdag' => 'https://dagensnamnsdag.com/'
];

//Store statistics
if (isset($_GET['statistik'])){
  $output['statistik']['antal dagar'] = $number_of_days;
  $output['statistik']['arbetsfria dagar'] = $number_of_workfree;
  $output['statistik']['arbetsdagar'] = $number_of_work;
  $output['statistik']['röda dagar'] = $number_of_reddays;
  $output['statistik']['antal veckodagar'] = $number_of_weekdays;
  $output['statistik']['klämdagar'] = $squeeze_days;
  $output['statistik']['antal namnsdagar'] = count_names($output['dagar']);
}

//Push it out
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
$json = json_encode($output);
$jsonp_callback = isset($_GET['callback']) ? $_GET['callback'] : null;
print $jsonp_callback ? '$jsonp_callback($json)' : $json;

function return_error($msg){
  header('Status: 400 Bad Request', true, 400);
  exit($msg);
}
?>