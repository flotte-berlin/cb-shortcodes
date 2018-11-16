<?php
/*
Plugin Name: CB Shortcodes: Booking overviews
Plugin URI: https://github.com/flotte-berlin/cb-shortcodes
Description: Shortcodes for displaying cb-bookings overviews on a page. Place in a non-public page! 
Version: 1.0
Author: gundelfisch
Author URI: https://flotte-berlin.de
License: GPLv2 or later
 */
/***
 * Shortcodes for displaying cb-bookings overviews on a page
 * Attention: the results contain personal user-data and should be displayed only on private pages for selected users!
 * Author: gundelfisch
 * Version: 1.0.0.
 * 
 * *
 * [cb_bookings_preview]   coming bookings of all locations with booker's names (abbreviated)
 * [cb_bookings_overview]  bookings overview of 1 location with booker's contact data, parameter 'locid' and 'days' (max. days +/- today, default 15)
 * [cb_bookings_location]  bookings overview for location manager (ACF), parameter 'days' (max. days +/- today, default 15)
 *
 *
 * sortable table works with Plugin 'Table Sorter'
 * * ACF = Plugin 'Advanced Custom Fields' (select 1-n locations in user profile)
 */

/****************** coming bookings of all locations: ****************/

function cb_bookings_preview_shortcode( $atts ) {

$today = date("Y-m-d");
$heute = date("j. n. Y");
$print = '';
global $wpdb;

$cbTable = $wpdb->prefix . "cb_bookings";
$query = $wpdb->prepare("SELECT * FROM $cbTable WHERE status = 'confirmed' AND date_end >= '%s' ORDER by location_id ASC, item_id ASC, date_start ASC",
	$today
);
$bookings = $wpdb->get_results($query);
$location = '';
$item = '';
$countLoc = 0;

if ( $bookings ) {
	$print = "<table class='user-bookings tablesorter'><thead><tr class='bg'><th>Standort</th><th>Lastenrad</th><th>Buchungen</th><th>Anzahl</th></tr></thead><tbody>";

	foreach ( $bookings as $booking ) 
	{
	if ($booking->location_id != $location or $booking->item_id != $item)  {
		if ($countLoc > 0) {
			$print .= "</ul></td><td>".$countLoc."</td></tr>";
		}
		$countLoc = 0;
		$location = $booking->location_id;
		$item = $booking->item_id;
		$loc_name = get_the_title ($location);	
		$item_name = get_the_title ($item);
		$print .= "<tr><td>".$loc_name. "</td>";
		$print .= "<td><b><a href='/cb-items/" . $item_name . "'><span class='green'>". $item_name . "</span></a></b></td>";
		$anchor = $item_name.":S".$location;
		$show = 'document.getElementById("'.$anchor.'").style.display="block"';			
		$hide = 'document.getElementById("'.$anchor.'").style.display="none"';
		$print .= "<td><a class='anker' name='a:".$anchor."'></a><a href='#a:".$anchor."' onclick='".$show."' ondblclick='".$hide."'>Details</a>";
		$print .= "<ul id='".$anchor."' style='display:none;'>";
	}
	$countLoc++;
	$user = get_userdata ( $booking->user_id ); 				
	$date_start = DateTime::createFromFormat('Y-m-d', $booking->date_start);
	$date_end = DateTime::createFromFormat('Y-m-d', $booking->date_end);
			
	$print .= "<li>".$date_start->format('d.m.') . " - " . $date_end->format('d.m.') ." ".$user->first_name." ". substr($user->last_name,0,1).".</li>";
	
	}
	if ($countLoc > 0) {
		$print .= "</ul></td><td>".$countLoc."</td></tr>";
	}
$print .= "</tbody></table>";
}
return $print;
}
add_shortcode( 'cb_bookings_preview', 'cb_bookings_preview_shortcode' );

/****************** bookings overview of 1 location: ****************/

function cb_bookings_overview_shortcode( $atts ) {

	if ( isset( $atts['locid'] ) && ! ( empty ($atts['locid'] ) ) ) { // make sure the location id is set

		$date = new dateTime(current_time('mysql'));
		$heute = $date->format("j. n. Y");
		$today = $date->format("Y-m-d");
		$location = $atts['locid'];
		$max_days = isset ( $atts['days'] ) ?  $atts['days'] : 30;
		if (!$max_days > 0) {$max_days = 15;}
		$start_date = new dateTime();
		$start_date = $start_date->modify('-'.$max_days.' days');
		$date_begin = $start_date->format('Y-m-d');
		$end_date = new dateTime();
		$end_date = $end_date->modify('+'.$max_days.' days');
		$date_last = $end_date->format('Y-m-d');

		$loc_name = get_the_title ($location);	
		$print = "<p><b>Buchungsübersicht für Standort: ". $loc_name. "</b><br>(Buchungsstand vom ". $heute . " für +/-".$max_days." Tage)</p>";
		$trenner = "</th><th>";
		$print .= "<table><tr><th>von".$trenner."bis".$trenner."gebucht von".$trenner."Mail".$trenner."Telefon</th></tr>";
		$trenner = "</td><td>";
		global $wpdb;

		$cbTable = $wpdb->prefix . "cb_bookings";
		$query = $wpdb->prepare("SELECT * FROM $cbTable WHERE status = 'confirmed' AND location_id = %s AND date_end >= '%s' AND date_start <= '%s' ORDER by item_id ASC, date_start ASC",
			$location,
			$date_begin,
			$date_last
	);
		$bookings = $wpdb->get_results($query);
		$item = '';
		$next_booking = 0; 

		foreach ( $bookings as $booking ) 
		{		
			if ($booking->item_id != $item) {	
				$next_booking = 0; 
				$item = $booking->item_id;
				$item_name = get_the_title ($item);	
				$print .= "<tr><td colspan='5'><b>Lastenrad: <a class='green' href='/cb-items/" . $item_name . "'>". $item_name . "</a></b></td></tr>";
			}
			$user = get_user_by ( 'id', $booking->user_id ); 		
			$date_start = DateTime::createFromFormat('Y-m-d', $booking->date_start);
			$date_end = DateTime::createFromFormat('Y-m-d', $booking->date_end);
			$style='';	
			if ($booking->date_end < $today)   {$style = "class='bg'";	}
			else if ($booking->date_start >= $today and $next_booking != 1 ){
					$style = "class='next'";
					$next_booking = 1; 
			}
			$print .= "<tr ".$style."><td>".$date_start->format('j. n.').$trenner.$date_end->format('j. n.').$trenner. $user->first_name . ' ' .$user->last_name.$trenner.$user->user_email.$trenner.$user->phone."</td></tr>";
			
		}
		$print .= "</table>";
		return $print;
	} else { // if ( isset( $atts['locid'] ) && ! ( empty ($atts['locid'] ) ) )
		echo ("You must provide a Location id!");
	} // end if locid

}
add_shortcode( 'cb_bookings_overview', 'cb_bookings_overview_shortcode' );

/****************** bookings overview for location manager: ****************/

function cb_bookings_location_shortcode( $atts ) {	

$current_user = wp_get_current_user();
$max_days = $atts['days'];
if (!$max_days > 0) {$max_days = 15;}
	
if ( 0 != $current_user->ID ) {
	$user_info = get_userdata($current_user->ID);
	$print = "<p>Hallo ". $user_info->first_name .",";
	if (in_array( 'administrator', (array) $user_info->roles )
	or  in_array( 'editor', (array) $user_info->roles )
	or  in_array( 'contributor', (array) $user_info->roles )
	   ){
		$print .= "<br>als Administrator siehst du hier immer die Buchungsübersicht zu allen Standorten:</p>";
	} else {
		$print .= "<br>für Partner werden hier Buchungsinfos nur zu den Standorten angezeigt, die im Benutzerprofil eingetragen sind:</p>";
	}

     if (in_array( 'administrator', (array) $user_info->roles )
	 or  in_array( 'editor', (array) $user_info->roles )
	 or  in_array( 'contributor', (array) $user_info->roles )
		) {
		$print .= "<p>als Teammitglied siehst du hier zuerst noch alle Standort-Ansprechpartner:</p>";
		$args = array(	
				'role__not_in' => array('subscriber'),
				'meta_key'     => 'user_locations',
				'meta_value'   => '',
				'meta_compare' => '!='	
 		 ); 
	    $users = get_users( $args );
		$trenner = "</th><th>";		
		$trenner1 = "</th><th class = 'sortless'>";		
		$print .= '<table class = "tablesorter"><thead><tr><th>Wer'.$trenner1.'Mail'.$trenner1.'Telefon'.$trenner.' Standorte</th></thead><tbody>';
		$trenner = "</td><td>";
		foreach ( $users as $user ) { 
					
				$user_locations = get_field('user_locations', 'user_'.$user->ID);
			    
			 	if ( $user_locations) {
					 
				 	 $print .= "<tr><td>".$user->first_name." ".$user->last_name.$trenner.$user->user_email.$trenner.$user->phone.$trenner."<ul><li>".implode('</li><li>', array_column($user_locations, 'post_title'))."</li></ul></td></tr>";
				}
			
		}
		$print .= "</tbody></table>";	
		
	 }
	
	 if ( in_array( 'administrator', (array) $user_info->roles ) ) {
		$locations = get_posts( array(
   			'post_type'      => 'cb_locations',
			'post_status'    => 'publish',
			'order' 		 => 'ASC',
   			'posts_per_page' => -1	
		 ) );
		 
	 } else {
	   $locations = get_field('user_locations', 'user_'.$current_user->ID);
	 }
 
	 if ( $locations ) {
   		foreach ( $locations as $location ) {  				
				  $atts = array ('locid' => $location->ID, 'days' => $max_days);				    		
	    		  $print .= cb_bookings_overview_shortcode( $atts );	
		}
	}
}
return $print;
}

add_shortcode( 'cb_bookings_location', 'cb_bookings_location_shortcode' );
?>
