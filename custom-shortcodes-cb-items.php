<?php
/*
Plugin Name: CB Shortcodes: items teaser and availability 
Plugin URI: https://github.com/flotte-berlin/cb-shortcodes
Description: Shortcodes for displaying items teaser and availability on a page
Remark: the results do not contain personal user-data and can be displayed also on public pages for everyone 
Version: 1.2.1
Author: gundelfisch
Author URI: https://flotte-berlin.de
License: GPLv2 or later
 */
/***
 * [cb_items_teaser]       items teaser (linked thumbnails , sorted by ID asc
 * [cb_items_teaser_cat]   items teaser (sorted and grouped by categories), optional parameter 'cat' (id)
 * 
 * [cb_items_date]         bookable items for 1 date (list), opt. parameter: 
 *				'addDays' (checked day after today), 
 *				'sort' (sort order for items, default DESC)
 * [cb_items_next_date]    next date with bookable items (list), opt. parameter: 
 *				'days' (max. checked days from today, default 30),
 *				 'time' (0-24, time to switch checking from today to tomorrow, default 14) 
 *				 'sort'  (sort order for items, default DESC) 
 * [cb_items_available]    availability of all items for the next 30 days (sortable calendar table), opt. parameter 
 *				'desc' for table description
 *
 * sortable table works with Plugin 'Table Sorter'
 *  
*/
/****************** items teaser (linked thumbnails , sorted by ID asc): ****************/

function cb_items_teaser_shortcode( $atts ) {
$print = '';
$items = get_posts( array(
    'post_type'      => 'cb_items',
	'post_status'    => 'publish',
	'order' 	 => 'ASC',
   	'posts_per_page' => -1
	) );
 
if ( $items) {
   	foreach ( $items as $item ) {
		$item_name =  $item->post_title;			
		$print .= '<figure class="cb-items-teaser wp-caption alignleft"><a href="'.get_permalink($item->ID).'">';
		$print .= get_the_post_thumbnail($item->ID,'thumbnail');
		$print .= '</a><figcaption class="wp-caption-text"><span class="green">'.$item_name.'</span></figcaption></figure>';
	}
}
return $print;
}
add_shortcode( 'cb_items_teaser', 'cb_items_teaser_shortcode' );

/****************** items teaser (sorted and grouped by categories): ****************/

function cb_items_teaser_cat_shortcode( $atts ) {
	
$print = '';	
$cat = isset ( $atts['cat'] ) ?  $atts['cat'] : 0;

$categories = get_categories( array(
		'taxonomy' => 'cb_items_category',
	 	'parent' => $cat,
   	 	'orderby' => 'slug',
   	 	'order'   => 'ASC'
) );
	
foreach ( $categories as $category ) {
         
    if ($category->description != '') {
		$print .= '<div class="items-teaser"><h3>'.$category->description.'</h3>';
	} else { 
		$print .= '<h3>'.$category->name.'</h3>';			 
	}
	
	$items = get_posts( array(
	    'post_type'      => 'cb_items',
		'post_status'    => 'publish',
		'tax_query' => [   [
            'taxonomy' => 'cb_items_category',
            'terms' => $category->term_id,
            'include_children' => false 
        ],],
		'order' 		 => 'ASC',
 	    'posts_per_page' => -1
	) );
 
	if ( $items) {
   	foreach ( $items as $item ) {
		$item_name =  $item->post_title;			
		$print .= '<figure class="cb-items-teaser wp-caption alignleft"><a href="'.get_permalink($item->ID).'">';
		$print .= get_the_post_thumbnail($item->ID,'thumbnail');
		$print .= '</a><figcaption class="wp-caption-text"><span class="green">'.$item_name.'</span></figcaption></figure>';
		}
	}
	$print .= '</div>';

 }
return $print;
}
add_shortcode( 'cb_items_teaser_cat', 'cb_items_teaser_cat_shortcode' );

/****************** fix and variable holidays (Germany) ****************/

function get_holidays ( $year, $format ) {	

//$holidays = array ('1.1.','1.5.','3.10.','25.12.','26.12.');
	$holidays = array ();
	$newYear = new DateTime($year.'-01-01');
    	array_push($holidays, $newYear->format($format));
    	$laborDay = new DateTime($year.'-05-01');
    	array_push($holidays, $laborDay->format($format));
    	$unityDay = new DateTime($year.'-10-03');
   	array_push($holidays, $unityDay->format($format));
    	$xmas1 = new DateTime($year.'-12-25');
    	array_push($holidays, $xmas1->format($format));
    	$xmas2 = new DateTime($year.'-12-26');
    	array_push($holidays, $xmas2->format($format));
	$easterDate = new DateTime(date('Y-m-d',easter_date($year))); //Saturday
	$goodFriday = $easterDate->modify('-1 day');
	array_push($holidays, $goodFriday->format($format));
	$easterSunday = $easterDate->modify('+2 days');
	array_push($holidays, $easterSunday->format($format));
	$easterMonday = $easterDate->modify('+1 day');
	$ascensionDay = $easterDate->modify('+38 days');
	array_push($holidays, $ascensionDay->format($format));
	$pentecostSunday = $ascensionDay->modify('+10 days');
	array_push($holidays, $pentecostSunday->format($format));
	$pentecostMonday = $pentecostSunday->modify('+1 day');
	array_push($holidays, $pentecostMonday->format($format));
	//echo implode (',',$holidays);
	return $holidays;
}

/****************** bookable items for 1 date (list) ****************/

function cb_items_date_shortcode( $atts ) {	
$add  = isset ( $atts['addDays'] ) ?  $atts['addDays'] : 1;
$sort = isset ( $atts['sort'] ) ?  $atts['sort'] : 'DESC';

$today = new dateTime(current_time('mysql'));
$nextday = $today->modify('+'.$add.' days');
$date = $nextday->format("Y-m-d");	
$weekday = $nextday->format("N");
$year = $nextday->format("Y");
$format = 'j.n.';
$dateDM = $nextday->format($format);
$holidays = get_holidays ($year, $format);
//echo implode (',',$holidays);

$bookable_items = 0;
$print = '';
	
global $wpdb;
$cbTimeframes = $wpdb->prefix . "cb_timeframes";
$cbBookings = $wpdb->prefix . "cb_bookings";
$cbClosedDays = "commons-booking_location_closeddays";
$cbCity = "commons-booking_location_adress_city";
	
$items = get_posts( array(
    'post_type'      => 'cb_items',
	'post_status'    => 'publish',
	'order' 	 => $sort,
   	 'posts_per_page' => -1
	) );
	
if ( $items) {

   	foreach ( $items as $item ) {
		$itemID = $item->ID;
		$item_name = $item->post_title;	
		
		$query = $wpdb->prepare("SELECT location_id FROM $cbTimeframes WHERE item_id = %s AND date_start <= '%s' AND date_end >= '%s'", $itemID, $date, $date);
		$timeframes = $wpdb->get_results($query);
		
		if ( $timeframes) {
		foreach ( $timeframes as $timeframe ) {			
			 
			
			 $closeddays = get_post_meta($timeframe->location_id, $cbClosedDays, TRUE);

			 if ( $closeddays == '' or
				( !in_array($weekday,$closeddays) and 
				  !in_array($dateDM,$holidays) )) {			 
		
		   	 	 $query2 = $wpdb->prepare("SELECT user_id FROM $cbBookings WHERE status = 'confirmed' AND item_id = %s AND date_start <= '%s' AND date_end >= '%s'", $itemID, $date, $date );
		    	 $bookings = $wpdb->get_results($query2);
		       			     			
				 if (!$bookings) {
				 
					$item_name = $item->post_title;		
					$location = get_post_meta($timeframe->location_id, $cbCity, TRUE);
					$location = str_replace('Berlin-','',$location);
					if ($bookable_items == 0) {$print .= '<ul>';}
					$print .= '<li><a href="'.get_permalink($item->ID).'">'.$item_name.'</a>';	
					$print .= '<span> ('.$location.')</span></li>';
					$bookable_items++;
					
			 	}
			 }
		}
		}
	}
	if ($bookable_items == 0) {
		$print .= 'am '.$dateDM.' ist nichts mehr buchbar!';
	} else {$print .= '</ul>';}	
	return $print;
  }
}
add_shortcode( 'cb_items_date', 'cb_items_date_shortcode' );

/****************** next date with bookable items (list) ****************/

function cb_items_next_date_shortcode( $atts ) {
	$maxTimeToday  = isset ( $atts['time'] ) ?  $atts['time'] : 14;
	 // Uhrzeit, ab der nicht mehr für heute abgefragt wird
	$maxDays  = isset ( $atts['days'] ) ?  $atts['days'] : 30;
	//wieviele zukünftige Tage abfragen
	$sort = isset ( $atts['sort'] ) ?  $atts['sort'] : 'DESC';
	// Reihenfolge Artikel

	$addDays = 0;
	setlocale (LC_ALL, 'de_DE.utf8');
	
	$today = new dateTime(current_time('mysql'));
   	 $timeToday = $today->format("G");
	if ($timeToday >= $maxTimeToday) {
		$addDays = 1;
	}
		
	for ($i = $addDays; $i < $maxDays; $i++) {
		$atts = array ('addDays' => $i, 'sort' => $sort);		
		$result = cb_items_date_shortcode( $atts );
		if (preg_match('%href%', $result)) {
			break;
		}
	}
	if ($i < $maxDays ){		

		if ($i == 0) {$nextday = 'noch heute';}
		else 
		if ($i == 1){$nextday = 'morgen';}
		else 
		if ($i == 2){$nextday = 'übermorgen';}
		else {
			$date = $today->modify('+'.$i.' days');
			$nextday = 'erst am '.strftime ('%A, %e. %B', $date->getTimestamp());
		}
		$print = '<div class="next-available"><b>'.$nextday.': '.$result.'</b></div>';	
	} 
	else {
		$print = '<p class="next-available">in den nächsten '.$maxDays.' Tagen ist nichts buchbar!</p>';
	}
	return $print;
}
add_shortcode( 'cb_items_next_date', 'cb_items_next_date_shortcode' );

/****************** availability of all items for the next 30 days (calendar table) ****************/

function cb_items_available_shortcode( $atts ) {

$print = '';
$desc = isset ( $atts['desc'] ) ?  $atts['desc'] : ''; 
$date = new dateTime(current_time('mysql'));
$today = $date->format("Y-m-d");
$year = $date->format("Y");
$days = 31; // oder aus settings?
$format = 'j.n.';
$holidays = get_holidays ($year, $format);
	
$days_display = array_fill(0,$days,'n');
$days_cols = array_fill(0,$days,'<col>');
$month = date("m");
$month_cols = 0;
$colspan = $days;
for ($i = 0; $i < $days; $i++) {
	$month_cols++;
	$days_display[$i] = $date->format('d');	 
	$days_dates[$i] = $date->format('Y-m-d');
	$days_weekday[$i] = $date->format('N');
	$daysDM[$i] = $date->format('j.n.');
	if ($date->format('N') >= 7 
	or in_array($daysDM[$i],$holidays)) {
		$days_cols[$i] = '<col class="bg_we">';
	}
	$date->modify('+1 day');
	if ($date->format('m') != $month ){
	   $colspan = $month_cols;
	   $month_cols = 0;
	   $month = $date->format('m');
	}	
} 
	
$trenner = "</th><th class='cal sortless'>";
$dayStr = implode($trenner, $days_display);
$colStr = implode(' ', $days_cols);
$print = "<table class='bookings tablesorter'><colgroup><col><col>".$colStr."</colgroup><thead>";
setlocale (LC_ALL,'de_DE.utf8');
$print .= "<tr><th colspan='2' class='sortless'></th><th class='sortless' colspan='".$colspan."'>";
if ($colspan > 1) {
	$print .= strftime('%B')."</th>";
} else {
	$print .= strftime('%b')."</th>";
}
if ($month_cols > 1){	
	$month2 = strftime('%B', strtotime($days_dates[$days-1]));
} else {
	$month2 = strftime('%b', strtotime($days_dates[$days-1]));
}

if ($colspan < $days) {
	$print .= "<th class='sortless' colspan='".$month_cols."'>".$month2."</th>";
}
$print .= "</tr><tr><th><span class='green'>".$desc."</span></th><th>Standort<th class='cal sortless'>".$dayStr."</th></tr></thead><tbody>";
	
$trenner = "</td><td>";
$days_display = array_fill(0,$days,'<span class="free">0</span>');
	
global $wpdb;
$cbTimeframes = $wpdb->prefix . "cb_timeframes";
$cbBookings = $wpdb->prefix . "cb_bookings";
$cbClosedDays = "commons-booking_location_closeddays";
$cbCity = "commons-booking_location_adress_city";
		
$items = get_posts( array(
    'post_type'      => 'cb_items',
	'post_status'    => 'publish',
	'order' 		 => 'ASC',
    'posts_per_page' => -1
) );	
	
foreach ( $items as $item ) {
		
		$itemID = $item->ID;
		$item_name = $item->post_title;	
		$days_display = array_fill(0,$days,'<span class="closed">*</span>');
		
		$query = $wpdb->prepare("SELECT * FROM $cbTimeframes WHERE item_id = %s AND date_end >= '%s'", $itemID, $today);
		$timeframes = $wpdb->get_results($query);
		if ($timeframes) {
		
		    foreach ( $timeframes as $timeframe ) {		
			$closeddays = get_post_meta($timeframe->location_id, $cbClosedDays, TRUE);
			for ($i = 0; $i < $days; $i++) {
				 if ($days_dates[$i] >= $timeframe->date_start and $days_dates[$i] <= $timeframe->date_end
				 and ($closeddays == '' 
				 or  (!in_array($days_weekday[$i],$closeddays)
				 and  !in_array($daysDM[$i],$holidays)) ) )
				 {
					  $days_display[$i] = "<span class='free'>0</span>";
				 }
			}
			$location = get_the_title ($timeframe->location_id); // get_post_meta($timeframe->location_id, $cbCity, TRUE);
			$location = str_replace('Berlin-','B-',$location);
		}
	
		$query = $wpdb->prepare("SELECT * FROM $cbBookings WHERE status = 'confirmed' AND date_end >= '%s' AND item_id = %s ORDER BY date_start ASC", $today, $itemID);
	    $bookings = $wpdb->get_results($query);
	
		foreach ( $bookings as $booking ) {				
			for ($i = 0; $i < $days; $i++) {
		 		if ($days_dates[$i] >= $booking->date_start and $days_dates[$i] <= $booking->date_end) {
				 $days_display[$i] = "<span class='booked'>X</span>";
		 		}
			}
		}
		$dayStr = implode($trenner, $days_display);
		$print .= "<tr><td><b><a href='".get_permalink($item->ID)."'>".$item_name."</a></b>".$trenner.$location.$trenner.$dayStr."</td></tr>";
	}	
}	
	
$print .= "</tbody></table>";
return $print;

}
add_shortcode( 'cb_items_available', 'cb_items_available_shortcode' );
?>
