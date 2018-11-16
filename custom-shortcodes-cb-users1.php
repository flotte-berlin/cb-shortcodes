<?php
/*
Plugin Name: CB Shortcodes:  user bookings summaries/statistics 
Plugin URI: https://github.com/flotte-berlin/cb-shortcodes
Description: Shortcodes for displaying user bookings summaries/statistics on a page.
Attention: the results contain personal user-data and should be placed only on a non-public page!
Version: 1.0
Author: gundelfisch
Author URI: https://flotte-berlin.de
License: GPLv2 or later
 */
/**
 * [cb_bookings_user]      user bookings summary (all subscriber bookings, sortable table)
 * 
 * sortable table requires Plugin 'Table Sorter'
 */

/****************** user statistics : ****************/

function cb_bookings_user_shortcode( $atts ) {
$heute = date("j. n. Y");
$start_date = new dateTime();
$start_date = $start_date->modify('-1 month');
$date_begin = $start_date->format('Y-m-d');

$print = '';
global $wpdb;

$cbTable = $wpdb->prefix . "cb_bookings";
$query = $wpdb->prepare("SELECT * FROM $cbTable WHERE status != 'pending' ORDER by user_id DESC, date_start DESC, booking_time DESC", RID);
$bookings = $wpdb->get_results($query);
$userID = '';
$bookingsSubscriber = 0;
$bookingsSubscriberCancel = 0;
$countSubscriber = 0;
$daysSubscriber = 0;
	
$bookingsUser = 0;
$bookingsUserConfirm = 0;
$bookingsUserCancel = 0;
$daysUser = 0;
$bookingsUserRecent = 0;
$daysUserRecent = 0;

$trenner = "</td><td>";
if ($bookings) {
	$print = "<table class='user-bookings tablesorter {sortlist: [[7,1]]}'><thead><tr class='bg'><th>User</th><th>Name</th><th>registriert<br>am</th><th class='sortless'>Buch. <span class='booked'>Bestätigt</span>/<span class='green'>Storniert</span><br>am Status: von - bis was</th><th>Buch.<br>confirm</th><th>Tage<br>conf.</th><th>Buch.<br>neue</th><th>Tage<br>neue</th><th>Buch.<br>cancel</th></tr></thead><tbody>";
	
    foreach ( $bookings as $booking ) {
	if ($booking->user_id != $userID) {	
		if ($bookingsUser > 0 ) {
			$print .= "</ul>".$trenner.$bookingsUserConfirm.$trenner.$daysUser.$trenner.$bookingsUserRecent.$trenner.$daysUserRecent.$trenner.$bookingsUserCancel."</td></tr>";
		}
		$userID = $booking->user_id;
		$user = get_userdata ( $userID ); 		
		
		if ( in_array( 'subscriber', (array) $user->roles ) ) {				
			
		 	$reg_date = new DateTime($user->user_registered);
			$show = 'document.getElementById("'.$userID.'").style.display="block"';			
			$hide = 'document.getElementById("'.$userID.'").style.display="none"';
			$print .= "<tr><td>".substr($user->user_login,0,15)."</td><td>".$user->first_name . ' ' . substr($user->last_name,0,1).".</td><td>".$reg_date->format('Y-m-d')."</td><td>";
			$print .= "<a class='anker' name='a".$userID."'></a><a href='#a".$userID."' onclick='".$show."' ondblclick='".$hide."'>Details</a>";
			$print .= "<ul id='".$userID."' style='display:none;'>";
			$countSubscriber++;			
		}
			$bookingsUser = 0;
			$bookingsUserConfirm = 0;
			$bookingsUserCancel = 0;
			$daysUser = 0;
			$bookingsUserRecent = 0;
			$daysUserRecent = 0;
	}
	
	if ( in_array( 'subscriber', (array) $user->roles )) {
		$bookingsUser++;
		if ($booking->status == 'confirmed'){ 
			$bookingsUserConfirm++;
			$bookingsSubscriber++;
		}
		else if ($booking->status == 'canceled') {
			$bookingsUserCancel++;
			$bookingsSubscriberCancel++;
		}
				
		if ($booking->status == 'confirmed'){ $status = '<span class="booked">B</span>';}
		if ($booking->status == 'canceled'){ $status = '<span class="green">S</span>';}
			$item_name = get_the_title ( $booking->item_id );
			$date_start = DateTime::createFromFormat('Y-m-d', $booking->date_start);
			$date_end = DateTime::createFromFormat('Y-m-d', $booking->date_end);
			$booking_time = DateTime::createFromFormat('Y-m-d H:i:s', $booking->booking_time);
			$print .= "<li>".$booking_time->format('d.m.')." ".$status.": ".$date_start->format('d.m.')." - ".$date_end->format('d.m.')." ".$item_name."</li>";
		
		if ($booking->status == 'confirmed') {
			if ($booking->date_start == $booking->date_end){
		 	   $days = 1;		
			} else {
				$days = date_diff($date_start, $date_end)->format('%a') + 1;
			}		
			$daysUser += $days;
			$daysSubscriber += $days;
			if ($booking->date_start >= $date_begin) {
				$daysUserRecent += $days;
				$bookingsUserRecent++;
			}
		}
	}
    }
	if ($bookingsUser > 0 ) {
		$print .= 	"</ul>".$trenner.$bookingsUserConfirm.$trenner.$daysUser.$trenner.$bookingsUserRecent.$trenner.$daysUserRecent.$trenner.$bookingsUserCancel."</td></tr>";
	}
	$user_count = count_users();
	$avail_roles = $user_count['avail_roles'];
	$count_sub = $avail_roles['subscriber']; 
	
	$print .= "</tbody><tfoot><tr><td colspan='4'>registrierte User: ".$count_sub.", davon ".$countSubscriber." aktiv (mit bestätigten/stornierten Buchungen)".$trenner.$bookingsSubscriber.$trenner.$daysSubscriber.$trenner.$trenner.$trenner.$bookingsSubscriberCancel."</td></tr></tfoot></table>";
}
return $print;
}
add_shortcode( 'cb_bookings_user', 'cb_bookings_user_shortcode' );

?>
