<?php
/*
Plugin Name: CB Shortcodes: Booking statistics
Plugin URI: https://github.com/flotte-berlin/cb-shortcodes
Description: Shortcodes for displaying bookings statistics on a page. 
Remark: the results do not contain personal user-data 
Version: 1.0
Author: gundelfisch
Author URI: https://flotte-berlin.de
License: GPLv2 or later
 */
/** 
 * [cb_bookings_summary]   past bookings summary for all locations (sortable table and chart) 
 * [cb_bookings_months]    past and future bookings summary for all items per month (table and chart) 
 * 
 * sortable table requires Plugin 'Table Sorter'
 * chart by chart.js from cdnjs.cloudflare.com
*/

/****************** bookings statistics per location: ****************/

function cb_bookings_summary_shortcode( $atts ) {

$today = new dateTime(current_time('mysql'));
$yesterday = $today->modify('-1 day');
$yesterday->setTime(23,59,59);
$date_end = $yesterday->format('Y-m-d');
$datum_bis = $yesterday->format('j. n. Y');
	
// data arrays for table and chart:
$rows = array();	
$items_array = array();		
$booked_days_array = array();
$bookable_days_array = array();
$month_days_array = array();

$print = "<p><b>Bestätigte Buchungen pro Standort (nur bis gestern = ".$datum_bis."):</b></p>";
	
// table header and columns ( = array-keys!)
$headers = array('Standort','Rad','seit','Anz. Buch.','&Oslash; Tage Vorlauf','Zeit-raum','Tage buchbar','Tage gebucht','Quote ZR','Quote buchbar','Subs Tage','Subs ZR','Subs buchbar');
$columns = array('location','item','since','bookings','days_pre','days_month','days_bookable','booked_days','rate_month', 'rate_bookable','booked_days_subs','rate_month_subs','rate_bookable_subs');
	
global $wpdb;
$cbTable = $wpdb->prefix . "cb_bookings";
$query = $wpdb->prepare("SELECT * FROM $cbTable WHERE status = 'confirmed' AND date_start <= '%s' ORDER by location_id ASC, item_id ASC", $date_end);
$bookings = $wpdb->get_results($query);
	
if ($bookings) {

	$total_row = array_fill_keys($columns, 0);
	$total_row['location'] = 'Alle Standorte';
	$total_row['item'] = '';
	$total_row['since'] = '';

	foreach ( $bookings as $booking ) 
	{
	if ($booking->location_id != $location or $booking->item_id != $item ) {
		if ($item_row['booked_days'] > 0 ) {
			array_push($rows, $item_row);				
		}
		
		$location = $booking->location_id;
		$loc_name = get_the_title ( $booking->location_id );	
		$item = $booking->item_id;
		$item_name = get_the_title ( $booking->item_id );
		$item_row = array_fill_keys($columns, 0);
		$item_row['location'] = $loc_name;
		$item_row['item'] = $item_name;
		
		$cbTable = $wpdb->prefix . "cb_timeframes";
		$query2 = $wpdb->prepare("SELECT * FROM $cbTable WHERE location_id = $location AND item_id = $item ORDER BY date_start ASC", RID);
		$timeframes = $wpdb->get_results($query2);	
		$first_book_start = '';
		
		foreach ( $timeframes as $timeframe ) {
			if ($timeframe->date_start <= $yesterday->format('Y-m-d')){
				$timeframe_start = DateTime::createFromFormat('Y-m-d',$timeframe->date_start);
				$timeframe_end = DateTime::createFromFormat('Y-m-d',$timeframe->date_end);
			    $timeframe_start->setTime(0, 0, 0);
				$timeframe_end->setTime(23, 59, 59);				
				if ($first_book_start == '') {$first_book_start = $timeframe_start; }	
				$item_row['days_month'] += date_diff(min($timeframe_start,$yesterday), min($timeframe_end, $yesterday))->format('%a') + 1; 					
			}
		}
		$item_row['since'] = $first_book_start->format('d.m.Y');
		if ($item_row['days_month'] == 0 ) {  // timeframe is missing 
			$date_start = DateTime::createFromFormat('Y-m-d', $booking->date_start);
			$date_start->setTime(0, 0, 0);
			$item_row['days_month'] = date_diff($date_start,$yesterday)->format('%a') + 1;
		}
				
		$cbClosedDays = "commons-booking_location_closeddays";	 
		
		$closedDays = get_post_meta($location, $cbClosedDays, TRUE); 
		$item_row['days_bookable'] = $item_row['days_month'];
		if ($closedDays) {
			$item_row['days_bookable'] = $item_row['days_month'] - round($item_row['days_month'] * count($closedDays) / 7); 
		}
			
		$total_row['days_month'] += $item_row['days_month'];
		$total_row['days_bookable'] += $item_row['days_bookable'];
		
	} // end of new item
		
	$item_row['bookings']++;
	$total_row['bookings']++;	
		
	// booked days:
	$date_start = DateTime::createFromFormat('Y-m-d', $booking->date_start);
	if ($booking->date_start == $booking->date_end){
		$days = 1;		
	} else {
		$date_end = DateTime::createFromFormat('Y-m-d', $booking->date_end);
		$date_end->setTime(23, 59, 59);
		$days = date_diff($date_start, min($yesterday,$date_end))->format('%a') + 1;
	}	
	$item_row['booked_days'] += $days;
	$total_row['booked_days'] += $days;
	
	// days in advance:	
	$booking_time = DateTime::createFromFormat('Y-m-d H:i:s', $booking->booking_time);
	$date_start->setTime(0, 0, 0);
	$booking_time->setTime(0, 0, 0);
	$pre_days = date_diff($booking_time, $date_start)->format('%a');
	$item_row['days_pre'] += $pre_days;		
	$total_row['days_pre'] += $pre_days;
		
	// bookings of subscribers:		
	$user = get_userdata ( $booking->user_id ); 		
	if ( in_array( 'subscriber', (array) $user->roles ) ) {
		$item_row['booked_days_subs'] += $days;			
		$total_row['booked_days_subs'] += $days;
	}
	} // end of bookings

	array_push($rows, $item_row);		
	array_push($rows, $total_row);	
	
	// prepare and print table rows
	// 
	$trenner = "</td><td>";
	$trenner2 = "</th><th>";
	$print .= '<table class="cb_statistics tablesorter">';
	$print .= '<colgroup span="10"></colgroup><colgroup span="3" class="bg"></colgroup>';	
	$header_columns = implode($trenner2, $headers);	
	$header_columns = str_replace ("<th>seit","<th class='dateFormat-ddmmyyyy'>seit",$header_columns);
	$print .= "<thead><tr><th>".$header_columns."</th></tr></thead>";	
	
	foreach($rows as $row) {
		
		$row['days_pre'] = round($row['days_pre'] / $row['bookings']); 
		$row['rate_month'] = round(100 * $row['booked_days'] / $row['days_month'])."%";
		$row['rate_bookable'] = round(100 * $row['booked_days'] / $row['days_bookable'])."%";
		$row['rate_month_subs'] = round(100 * $row['booked_days_subs'] / $row['days_month'])."%";
		$row['rate_bookable_subs'] = round(100 * $row['booked_days_subs'] / $row['days_bookable'])."%";

		if ($row['item'] != '') { // item row	
			$print .= "<tr><td>".implode($trenner, $row)."</td></tr>";				
			// fill arrays for chart:
			$since = substr($row['since'],0,6);
			array_push($items_array, $row['location'].": ".$row['item']." (ab ".$since.")");
			array_push($booked_days_array, $row['booked_days']);
			array_push($bookable_days_array, $row['days_bookable']);
			array_push($month_days_array, $row['days_month']);	
		} else {                                // last row
			$print .= "</tbody><tfoot>";			
			$print .= "<tr><th>".implode($trenner2, $row)."</th></tr>";
			$print .= "</tfoot>";	
		}
	}		// end of rows
	$print .= "</table>";
	
	// data for chart:	
	$height = '500';
	$dataLabels = implode ("','", $items_array);
	$chartType = 'horizontalBar';
	$dataSets = array();
	$dataSet = array();
	
	$dataSet['type'] = $chartType;
			
	$dataSet['color'] = 'rgb(238,127,0)';
	$dataSet['label'] = 'gebuchte Tage';	
	$dataSet['data'] = implode (',',$booked_days_array);
	array_push($dataSets, $dataSet);	
	
	$dataSet['color'] = 'rgb(127,198,0)';	
	$dataSet['label'] = 'buchbare Tage';	
	$dataSet['data'] = implode (',',$bookable_days_array);
	array_push($dataSets, $dataSet);
	
	$dataSet['color'] = 'rgb(0,75,124)';	
	$dataSet['label'] = 'Zeitraum (Kalendertage)';	
	$dataSet['data'] = implode (',',$month_days_array);
	array_push($dataSets, $dataSet);		

	 // plus sort dataSets by item or location or date-since?
	
	$print .= cb_bookings_chart ($height, $chartType, $dataLabels, $dataSets );
}

return $print;
}

add_shortcode( 'cb_bookings_summary', 'cb_bookings_summary_shortcode' );

/****************** bookings statistics per month: ****************/
function cb_bookings_months_shortcode ( $atts ) {
	
$today = new dateTime(current_time('mysql'));
$print = "<p><b>Bestätigte Buchungen pro Monat (Buchungsstand vom ".$today->format('d.m.Y')."):</b></p>";	
	
global $wpdb;	
$cbTable2 = $wpdb->prefix . "cb_timeframes";
$query2 = $wpdb->prepare("SELECT * FROM $cbTable2", RID);
$timeframes = $wpdb->get_results($query2);	
	
$cbTable = $wpdb->prefix . "cb_bookings";
$query = $wpdb->prepare("SELECT * FROM $cbTable WHERE status = 'confirmed' ORDER by date_start ASC LIMIT 1", RID);
$booking1 = $wpdb->get_results($query);	
	
// data arrays for table and chart:
$rows = array();	
$months_array = array();		
$booked_days_array = array();
$bookable_days_array = array();
$month_days_array = array();
$user_array = array();	

// table header and columns ( = array-keys!)
$headers = array('Monat','Räder','Anz. Buch.','&Oslash; Tage Vorlauf','Tage Monat','Tage buchbar','Tage gebucht','Quote Monat','Quote buchbar','Subs Tage','Subs Monat','Subs buchbar','neue User');
$columns = array('month','items','bookings','days_pre','days_month','days_bookable','booked_days','rate_month', 'rate_bookable','booked_days_subs','rate_month_subs','rate_bookable_subs','registered');

if ($booking1) 
{		
	$first_booking = min(array_column($booking1, 'date_start'));
	$first_month = substr($first_booking,0,7);
	$this_month = $first_month;		

	// get registered subscribers before first booking month
	$start_time = $first_month."-01 00:00:00";
	$args = array (
   				 'role'          => 'subscriber',
    			 'date_query'    => array(
       				 array(
          		 		'before'     => $start_time,						
            			'inclusive' => true,
      			  	),
    		 	),
	);
	$user_query = new WP_User_Query( $args );
	
	$total_row = array_fill_keys($columns, 0);
	$total_row['registered'] = $user_query->total_users;
	$total_row['items'] = '';
	
	$query = $wpdb->prepare("SELECT * FROM $cbTable WHERE status = 'confirmed' ORDER by date_start DESC LIMIT 1", RID);
	$booking2 = $wpdb->get_results($query);	
	$last_booking = max(array_column($booking2, 'date_start'));
	$last_month = substr($last_booking,0,7);	
  
	while ($this_month <= $last_month) 
	{

		$item = '';	
		$month_row = array_fill_keys($columns, 0);
		$month_row['month'] = $this_month;
		$total_row['month']++;
		
		$show = 'document.getElementById("'.$this_month.'").className="bg"';			
		$hide = 'document.getElementById("'.$this_month.'").className="bg hidden"';		
				
		$month_first = $this_month."-01";	
		$date_month_first = DateTime::createFromFormat('Y-m-d', $month_first);
		$date_month_first->setTime(0, 0, 0);	
		$month_last = date("Y-m-t", strtotime($month_first));
		$date_month_last = DateTime::createFromFormat('Y-m-d', $month_last);
		$date_month_last->setTime(23, 59, 59);	
		$month_days = date("t", strtotime($month_first));		
	
		$query = $wpdb->prepare("SELECT * FROM $cbTable WHERE status = 'confirmed' AND date_start >= '$month_first' AND date_start <= '$month_last' ORDER BY item_id ASC", RID);
		$bookings = $wpdb->get_results($query);	
		
		foreach ( $bookings as $booking ) 	
		{	
			if ($booking->item_id != $item )
			{	// next item
				if ($item != '') {
					array_push($rows, $item_row);					
				}
		
				$item = $booking->item_id;
				$item_name = get_the_title ( $booking->item_id );				
				$item_row = array_fill_keys($columns, 0);
				$item_row['month'] = $this_month;
				$item_row['items'] = $item_name;
				$item_row['registered'] = '';
				$month_row['items']++;
			
				foreach ( $timeframes as $timeframe ) 
				{
					if ($timeframe->item_id == $item
					and substr($timeframe->date_start,0,7) <= $this_month
					and substr($timeframe->date_end,0,7) >= $this_month)
					{
			  	   	 	$timeframe_start = DateTime::createFromFormat('Y-m-d', $timeframe->date_start);
			  		 	$timeframe_end = DateTime::createFromFormat('Y-m-d', $timeframe->date_end);
						$timeframe_start->setTime(0, 0, 0);		
						$timeframe_end->setTime(23, 59, 59);		
			      		$item_row['days_month'] += date_diff(max($timeframe_start,$date_month_first), min($timeframe_end, $date_month_last))->format('%a') + 1 ;	
					}	
				}
				
				if ($item_row['days_month'] == 0 or $item_row['days_month'] > $month_days ) {  // timeframe is missing or double
					$item_row['days_month'] = $month_days; 
				}
				
				$cbClosedDays = "commons-booking_location_closeddays";
				$location = $booking->location_id;  // location of first booking / month
				
				$closedDays = get_post_meta($location, $cbClosedDays, TRUE); // location of first booking / month
				if ($closedDays) {					
					$item_row['days_bookable'] = $item_row['days_month'] - round($item_row['days_month'] * count($closedDays) / 7, 0);
				} else {
					$item_row['days_bookable'] = $item_row['days_month'];
				}
				
				$month_row['days_month'] += $item_row['days_month'];
				$total_row['days_month'] += $item_row['days_month'];
				$month_row['days_bookable'] += $item_row['days_bookable'];
				$total_row['days_bookable'] += $item_row['days_bookable'];
			} // end of item
		
		$item_row['bookings']++;
		$month_row['bookings']++;
		$total_row['bookings']++;
	
		// booked days:
		$date_start = DateTime::createFromFormat('Y-m-d', $booking->date_start);	
		if ($booking->date_start == $booking->date_end){
			$days = 1;		
		} else {
			$date_end = DateTime::createFromFormat('Y-m-d', $booking->date_end);
			$days = date_diff($date_start, $date_end)->format('%a') + 1;
		}
		$item_row['booked_days'] += $days;
		$month_row['booked_days'] += $days;
		$total_row['booked_days'] += $days;
			
		// days in advance:	
		$booking_time = DateTime::createFromFormat('Y-m-d H:i:s', $booking->booking_time);
		$date_start->setTime(0, 0, 0);
		$booking_time->setTime(0, 0, 0);
		$pre_days = date_diff($booking_time, $date_start)->format('%a');
		$item_row['days_pre'] += $pre_days;
		$month_row['days_pre'] += $pre_days;
		$total_row['days_pre'] += $pre_days;
		
	// bookings of subscribers:		
		$user = get_userdata ( $booking->user_id ); 		
		if ( in_array( 'subscriber', (array) $user->roles ) ) {			
			$item_row['booked_days_subs'] += $days;	
			$month_row['booked_days_subs'] += $days;
			$total_row['booked_days_subs'] += $days;
		}
		
		} // end of bookings of this month
		array_push($rows, $item_row);			
		
		// get registered Subscriber
		$start_time = $month_first." 00:00:00";
		$end_time = $month_last." 23:59:59";
		$args = array (
   				 'role'          => 'subscriber',
    			 'date_query'    => array(
       				 array(
          		 		'after'     => $start_time,
						'before'    => $end_time,
            			'inclusive' => true,
      			  	),
    		 	),
		);
		$user_query = new WP_User_Query( $args );
		$month_row['registered'] = $user_query->total_users;
		$total_row['registered'] += $month_row['registered'];
		$month_row['month'] = "<a href='#a".$this_month."' onclick='".$show."' ondblclick='".$hide."'>".$this_month."</a>";
		array_push($rows, $month_row);			
		
				
		// fill arrays for chart:
		array_push($months_array, date('M y', strtotime($this_month)));
		array_push($booked_days_array, $month_row['booked_days']);
		array_push($bookable_days_array, $month_row['days_bookable']);
		array_push($month_days_array, $month_row['days_month']);
		array_push($user_array, $total_row['registered']);
		
		// proceed to next month:
		$next_month = $date_month_first->modify('+1 month');
		$this_month = $next_month->format("Y-m");	
		
	} // end of this month
	
	array_push($rows, $total_row);	

	$trenner = "</td><td>";
	$trenner2 = "</th><th>";
	$print .= '<table class="cb_statistics">';	
	$print .= '<colgroup span="9"></colgroup><colgroup span="4" class="bg"></colgroup>';	
	$print .= "<thead><tr><th>".implode($trenner2, $headers)."</th></tr></thead>";
	$this_month = '';
	
	// prepare and print table rows
	foreach($rows as $row) {
		
		$row['days_pre'] = round($row['days_pre'] / $row['bookings']); 
		$row['rate_month'] = round(100 * $row['booked_days'] / $row['days_month'])."%";
		$row['rate_bookable'] = round(100 * $row['booked_days'] / $row['days_bookable'])."%";
		$row['rate_month_subs'] = round(100 * $row['booked_days_subs'] / $row['days_month'])."%";
		$row['rate_bookable_subs'] = round(100 * $row['booked_days_subs'] / $row['days_bookable'])."%";

		if (strpos($row['month'],'href') > 0) { // month row
			$print .= "</tbody><tbody>";		
		} else {$colMonth = $row['month'];}
		
		if (strpos($colMonth,'-') > 0) {  // item or month row
			if ($colMonth != $this_month) { // first item of month
				$this_month = $colMonth;
				$print .= "<tbody id='".$this_month."' class='bg hidden'>";
			}		
			$print .= "<tr><td>".implode($trenner, $row)."</td></tr>";	
		} else {                                // last row
			$print .= "</tbody><tfoot>";			
			$print .= "<tr><th>".implode($trenner2, $row)."</th></tr>";
			$print .= "</tfoot>";	
		}
			
	} // end of rows
	$print .= "</table>";
	
	// data for chart:		
	$height = '400';
	$chartType = 'bar';
	$dataLabels = implode ("','", $months_array);
	$dataSets = array();
	$dataSet = array();
	
	$dataSet['color'] = 'rgb(0,0,0)';
	$dataSet['type'] = 'line';
	$dataSet['label'] = 'Subscriber';
	$dataSet['position'] = 'right'; 
	$dataSet['data'] = implode (',',$user_array);
	array_push($dataSets, $dataSet);	
	
	$dataSet['type'] = $chartType;
	$dataSet['position'] = 'left'; 
	
	$dataSet['color'] = 'rgb(238,127,0)';
	$dataSet['label'] = 'gebuchte Tage';
	$dataSet['data'] = implode (',',$booked_days_array);
	array_push($dataSets, $dataSet);	
	
	$dataSet['color'] = 'rgb(127,198,0)';
	$dataSet['label'] = 'buchbare Tage';	
	$dataSet['data'] = implode (',',$bookable_days_array);
	array_push($dataSets, $dataSet);
	
	$dataSet['color'] = 'rgb(0,75,124)';	
	$dataSet['label'] = 'Zeitraum (Kalendertage)';	
	$dataSet['data'] = implode (',',$month_days_array);
	array_push($dataSets, $dataSet);		

	$print .= cb_bookings_chart ($height, $chartType, $dataLabels, $dataSets );
} // end of bookings
return $print;
}
add_shortcode( 'cb_bookings_months', 'cb_bookings_months_shortcode' );

/****************** Chart: ****************/

function cb_bookings_chart ($height, $chartType, $dataLabels, $dataSets ) {
	    
$print = '<div style="width:100%;height:'.$height.'px;"><canvas id ="myChart"></canvas></div> ';
$print .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js"></script>';

$print .= "<script> var ctx = document.getElementById('myChart').getContext('2d'); ";	
$print .= "var chart = new Chart(ctx, {type: '".$chartType."',
    	data: {
     	labels: [ '".$dataLabels."'],
        datasets: [";
	
foreach ($dataSets as $dataSet) {
	$color = $dataSet['color'];
	$type = $dataSet['type'];
	$position = $dataSet['position'];
	$lable = $dataSet['label'];
	$data = $dataSet['data'];
	$print .= "
		{  type: '".$type."',
           label: '".$lable."',
		   backgroundColor: '".$color."',
           borderColor: '".$color."',
		   fill: false,
		   data: [ ".$data." ]";
	if ($chartType == 'bar') {
		$print .= ",   yAxisID: '".$position."'";		
	}	
	else if ($chartType == 'horizontalBar') {
		$print .= ",   xAxisID: '".$position."'";		
	}	
	$print .= " },";
} // end of dataSets	
	
	$print .= " ] }, options: { ";
	if ($chartType == 'bar') {
		$print .= "scales: {
			xAxes: [{stacked: true}],
			yAxes: [
			{id: 'left', type: 'linear', position: 'left', 
					ticks: { min: 0} }, 
			{id: 'right', type: 'linear', position: 'right',
					ticks: { min: 0 }, gridLines: {drawOnChartArea:0} }
			]				}";
	} else if ($chartType == 'horizontalBar') {
		$print .= "scales: {
			yAxes: [{stacked: true}],
			xAxes: [ 
			{id: 'bottom', type: 'linear', position: 'bottom', 
					ticks: { min: 0} }, 
			{id: 'top', type: 'linear', position: 'top',
					ticks: { min: 0 }, gridLines: {drawOnChartArea:0} }
				]	}";
	}
	$print .= " }});";
	$print .= "</script>";
return $print;
}

add_shortcode( 'cb_bookings_category', 'cb_bookings_category_shortcode' );

?>

