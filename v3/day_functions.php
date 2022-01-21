<?
error_reporting(E_ALL);
ini_set('display_errors', '1');

function get_holiday ($checkdate) {
  $year = date('Y', $checkdate);
  $month = date('n', $checkdate);
  $day = date('j', $checkdate);
  
  //Midsommar
  if ($month === 6 && $day >= 19 && $day <= 25 && date('N', $checkdate) === 5) {
    return array('helgdag', 'Midsommarafton', true, false);
  }
  if ($month === 6 && $day >= 20 && $day <= 26 && date('N', $checkdate) === 6) {
    return array('helgdag', 'Midsommardagen', true, true);
  }
  
  //Påsk
  if (date('Ymd', strtotime('-3 day', easter($year))) === date('Ymd', $checkdate)) {
    return array('helgdagsafton', 'Skärtorsdagen', false, false); 
  }
  
  if (date('Ymd', strtotime('-2 day', easter($year))) === date('Ymd', $checkdate)) {
    return array('helgdag', 'Långfredagen', true, true);
  }
  
  if (date('Ymd', strtotime('-1 day', easter($year))) === date('Ymd', $checkdate)) {
    return array('helgdag', 'Påskafton', true, false); 
  }
  
  if (date('Ymd', easter($year)) === date('Ymd', $checkdate)) {
    return array('helgdag', 'Påskdagen', true, true); 
  }
  
  if (date('Ymd', strtotime('+1 day', easter($year))) === date('Ymd', $checkdate)) {
    return array('helgdag', 'Annandag påsk', true, true); 
  }
  
  if (date('Ymd', strtotime('+39 day', easter($year))) === date('Ymd', $checkdate)) {
    if ($month == 4 && $day === 30) {
      return array('helgdag', 'Kristi himmelsfärdsdag, Valborgsmässoafton', true, true);
    } elseif ($month === 5 && $day === 1) {
      return array('helgdag', 'Kristi himmelsfärdsdag, Första Maj', true, true);
    } else {
      return array('helgdag', 'Kristi himmelsfärdsdag', true, true);    
    }
  }
  
  if (date('Ymd',strtotime('+48 day',easter($year))) === date('Ymd', $checkdate)) {
    return array('helgdagsafton', 'Pingstafton', true, false); 
  }
  
  if (date('Ymd',strtotime('+49 day',easter($year))) === date('Ymd', $checkdate)) {
    return array('helgdag', 'Pingstdagen', true, true); 
  }
  
  if (date('Ymd',strtotime('+50 day',easter($year))) === date('Ymd', $checkdate)) {
    if ($year < 2005) {
      return array('helgdag', 'Annandag pingst', true, true); 
    } else {
      return array('helgdag', 'Annandag pingst', false, false); 
    }
  }
  
  //Allhelgona
  if ($checkdate >= strtotime($year . '-10-30') && $checkdate <= strtotime($year . '-11-05') && date('N', $checkdate) === 5) {
    return array('helgdagsafton', 'Allhelgonaafton', false, false);    
  }
  
  if ($checkdate >= strtotime($year . '-10-31') && $checkdate <= strtotime($year . '-11-06') && date('N', $checkdate) === 6) {
    return array('helgdag', 'Alla helgons dag', true, true);    
  } 

  //Fixed dates Returnerar typ, dag och om den är abetsfri eller inte OCH om det är röd dag
  $fixed['01']['01'] = array('helgdag', 'Nyårsdagen', true, true);
  $fixed['01']['05'] = array('helgdagsafton', 'Trettondagsafton', false, false);
  $fixed['01']['06'] = array('helgdag', 'Trettondedag jul', true, true);
  $fixed['04']['30'] = array('helgdagsafton', 'Valborgsmässoafton', false, false);
  $fixed['05']['01'] = array('helgdag', 'Första Maj', true, true);
  $fixed['06']['06'] = array('helgdag', 'Sveriges nationaldag', true, true);
  $fixed['12']['24'] = array('helgdag', 'Julafton', true, false);
  $fixed['12']['25'] = array('helgdag', 'Juldagen', true, true);
  $fixed['12']['26'] = array('helgdag', 'Annandag jul', true, true);
  $fixed['12']['31'] = array('helgdag', 'Nyårsafton', true, false);

  //Ovveride for nationaldagen
  if ($year < 2005) {
    $fixed['06']['06'] = array('helgdag', 'Sveriges nationaldag', false, false);
  }
  
  if ($year < 1983) {
    $fixed['06']['06'] = array('helgdag', 'Svenska flaggans dag', false, false);
  }

  if (isset($fixed[$month][$day])) {
    return $fixed[$month][$day];
  }
} //Slut get_holiday

//Konvertering till riktiga påskdatum även innan 1970
function easter($easteryear) {
  $base = strtotime($easteryear . '-03-21');
  $days = easter_days($easteryear);
  $easter = strtotime('+' . $days . ' day', $base);
  return $easter;
}

//Veckodagar (returnerar veckodag, om dagen är arbetsfri och om det är en röd dag)
function get_weekday($checkdate) {
  switch (date('N', $checkdate)) {
    case 1:
      return array('Måndag', false, false); 
      break;
    case 2:
      return array('Tisdag', false, false);
      break;
    case 3:
      return array('Onsdag', false, false);
      break;
    case 4:
      return array('Torsdag', false, false);
      break;
    case 5:
      return array('Fredag', false, false);
      break;
    case 6:
      return array('Lördag', true, false);
      break;
    case 7:
      return array('Söndag', true, true);
      break;
  }
}
//Slut veckodagar

function is_workfree($checkdate, $offset) {
  $weekday = get_weekday(strtotime($offset, $checkdate));
  $holiday = get_holiday(strtotime($offset, $checkdate));
  
  if ($weekday[1] === true || $holiday[2] === true) {
    return true;
  }
}
?>