<?php
/**
 * Shortcodes for displaying cb-bookings summaries/statistics on a page
 * Attention: the results contain personal user-data and should be displayed only on private pages for selected users!
 * Author: gundelfisch
 * Version: 1.0.0.
 * 
 * [cb_bookings_user]      user bookings summary (all subscriber bookings, sortable table)
 * [user_activities]       user activities (activity dates for all subscribers, sortable table)
 * [cb_bookings_contracts] bookings review (all past bookings incl. contract checking, sortable table)
 * 
 * sortable table requires Plugin 'Table Sorter'
 * contract checking requires Plugin 'Commons Bookings Contracts Extension'
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
	$print = "<table class='user-bookings tablesorter'><thead><tr class='bg'><th>User</th><th>Name</th><th>registriert<br>am</th><th class='sortless'>Buch. <span class='booked'>Bestätigt</span>/<span class='green'>Storniert</span><br>am Status: von - bis was</th><th>Buch.<br>confirm</th><th>Tage<br>conf.</th><th>Buch.<br>neue</th><th>Tage<br>neue</th><th>Buch.<br>cancel</th></tr></thead><tbody>";
	
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

/****************** list users last activity dates ****************/

function user_activities_shortcode( $atts ) {
$print = '';

$args = array(
	'role'         => 'subscriber',
 ); 
$users = get_users( $args );
if ($users) {

	$trenner = "</th><th>";
	$trenner2 = "</th><th class='dateFormat-ddmmyyyy'>";
	$print .= '<table class="tablesorter {sortlist: [[2,0],[1,0]]}">';
	$print .= "<thead><tr><th>user".$trenner."name".$trenner."Status".$trenner."registered".$trenner."PW set/reset".$trenner."last Login".$trenner."last booking</th></tr></thead><tbody>";
	$trenner = "</td><td>";
	$count_logins = 0;
	$count_bookers = 0;
	$count_actives = 0;
	$count_inactives = 0;
	$count_duplicates = 0;
	global $wpdb;
	$cbTable = $wpdb->prefix . "cb_bookings";
	$logTable = $wpdb->prefix . "aryo_activity_log";

	foreach ( $users as $user ) {
	 	
		if (strpos($user->user_login,'deleted') === false) {
			
			$reg_date = substr($user->user_registered,0,10);	
			$pw_date = '-';		
			if ($user->user_activation_key != '') {
				$timestamp = intval(substr($user->user_activation_key,0,10));
				$pw_date = date('Y-m-d', $timestamp);
			}
       		$sessions = $user->session_tokens;	
			if ($sessions) {	
				$count_logins++;
		 		$last_login_time = max(array_column($sessions, 'login')); 
				$last_login_date = date('Y-m-d',$last_login_time);
			}						
			else {
				$userID = $user->ID;		
				$query = $wpdb->prepare("SELECT *FROM $logTable WHERE user_id = $userID AND action = 'logged_in'", RID);
				$logins = $wpdb->get_results($query);
				if ($logins) {		
					$count_logins++;
		 		   	$last_login_time = max(array_column($logins, 'hist_time')); 
					$last_login_date = date('Y-m-d',$last_login_time);
				}						
				else {$last_login_date = '-';}
			}
			$userID = $user->ID;		
			$query = $wpdb->prepare("SELECT booking_time FROM $cbTable WHERE user_id = $userID ORDER BY booking_time DESC LIMIT 1", RID);
			$bookings = $wpdb->get_results($query);
			if ($bookings) {
				$count_bookers++;
				$last_booking_time = max(array_column($bookings, 'booking_time')); 
				$last_booking_date = substr($last_booking_time,0,10);
			}
			else {$last_booking_date = '-';}
			
			$username = $user->user_login;
			if ($sessions or $logins or $bookings or $pw_date == '-'  )
			{
				$count_actives++;
				$class = '';
				$status = "";
			}
			else {
				$count_inactives++;
				$class = "class='bg'";
				$status = "!activ";
			}
			if ($user->phone == '') {
				$class .= " style='color:red;'";				
				$status = "!phone";
			}
			else {
				
				$args2 = array(	
					'exclude'      => $userID,
					'meta_key'     => 'phone',
					'meta_value'   => $user->phone,					
 				); 
				$users2 = get_users( $args2 );
				if ($users2) { 				
				
				  foreach ($users2 as $user2) {
					 if ($user2->phone == $user->phone and $user2->ID != $userID) {
						 $username .= " = ".$user2->user_login;
						 $count_duplicates++;
						 $status = "2user";
						 $class .= " style='color:red;'";
					} 
				  }
				}
			}
								
			$print .= "<tr ".$class."><td>".$username.$trenner.substr($user->first_name,0,15).' '. substr($user->last_name,0,1).".".$trenner.$status.$trenner.$reg_date.$trenner.$pw_date.$trenner.$last_login_date.$trenner.$last_booking_date."</td></tr>";
		}
	}
	$count_duplicates = $count_duplicates / 2; //Anzahl Paare zählen
	$print .= "</tbody><tfoot><tr><td>aktive Subscriber: ".$count_actives."</td><td class='orange'>doppelte: ".$count_duplicates.$trenner.$trenner.count($users).$trenner.$count_inactives.$trenner.$count_logins.$trenner.$count_bookers."</th></tr></tfoot>";
	$print .= "</table>";
	}
return $print;
}
add_shortcode( 'user_activities', 'user_activities_shortcode' );

/****************** bookings review (contracts) *************/
function cb_bookings_contracts_shortcode ( $atts ) {

$today = new dateTime(current_time('mysql'));
$yesterday = $today->modify('-1 day');
$date = $yesterday->format("Y-m-d");	

$print = "<p><b>Bestätigte Buchungen pro Standort mit Start-Datum in Vergangenheit (bis ".$yesterday->format('d.m.Y')."):</b></p>";
$trenner = "</td><td>";
	
global $wpdb;
$cbTable = $wpdb->prefix . "cb_bookings";
$query = $wpdb->prepare("SELECT * FROM $cbTable WHERE status = 'confirmed' AND date_start < '$date' ORDER by location_id ASC, item_id ASC, date_start DESC", RID);
$bookings = $wpdb->get_results($query);
	
$location = '';
$item = '';
$bookings_loc = 0;
$bookings_loc_sub = 0;
$location = '';
$noContract = 0;
$noContract_loc = 0;
$noContractStr = '';
$sub_bookings = 0;
	
$print .= '<table class="tablesorter">';
$trenner = "</th><th>";
$trenner1 = "</th><th class='sortless'>";
$trenner2 = "</th><th class='dateFormat-ddmmyyyy'>";
$print .= "<thead><tr><th>Standort".$trenner."Lastenrad".$trenner."&sum; Bu. ".$trenner."Subs.".$trenner2."erfasst bis".$trenner." o.V. ".$trenner1."Buchungen ohne Vertrag</tr></thead><tbody>";
$trenner = "</td><td>";

foreach ( $bookings as $booking ) 
{
	if ($booking->location_id != $location or $booking->item_id != $item ) {
		if ($bookings_loc > 0 ) {			
			$print .= "</ul></td><td>".$bookings_loc.$trenner.$bookings_loc_sub.$trenner.$lastContract.$trenner.$noContract_loc.$trenner."<ul>".$noContractStr."</ul></td></tr>";		
			$bookings_loc = 0;			
			$bookings_loc_sub = 0;
			$lastContract = '';
			$noContract_loc = 0;
			$noContractStr = '';
		}
		
		$location = $booking->location_id;
		$loc_name = get_the_title ( $booking->location_id );	
		$item = $booking->item_id;
		$item_name = get_the_title ( $booking->item_id );
		$print .= "<tr><td>".$loc_name.$trenner.$item_name."</td>";		

	}
	$bookings_loc++;
	
	$date_start = DateTime::createFromFormat('Y-m-d', $booking->date_start);
	$date_end = DateTime::createFromFormat('Y-m-d', $booking->date_end);
	
	//letztes Datum mit Erfassung merken (order by date DESC !):
	if ($booking->contract == 1 and $lastContract == '') {
	    $lastContract = $date_start->format('d.m.Y'); 
	}

	$user = get_userdata ( $booking->user_id ); 		
	if ( in_array( 'subscriber', (array) $user->roles ) ) {
		 $sub_bookings++;	
		 $bookings_loc_sub++;
	// Buchung von Subscriber ohne Vertrag vor letztem Erfassungsdatum:
		 if ( $booking->contract != 1 and $lastContract != '') { 
	   	  	  $noContract_loc++;
		   	  $noContract++;		
		 	  $noContractStr .= "<li>".$date_start->format('d. n.') . " - " .$date_end->format('d. n.')." " .$user->first_name." ".$user->last_name."</li>";
		}	
	}
	
}

if ($bookings_loc > 0 ) {	
	$print .= "</ul></td><td>".$bookings_loc.$trenner.$bookings_loc_sub.$trenner.$lastContract.$trenner.$noContract_loc.$trenner."<ul>".$noContractStr."</ul></td></tr>";		
}
$trenner = "</th><th>";
$count = count( $bookings );
$print .= " </tbody> <tfoot>";
$print .= "<tr><th colspan='2'>alle Standorte".$trenner.$count.$trenner.$sub_bookings.$trenner.$trenner.$noContract.$trenner."</th></tr>";
$print .= "</tfoot></table>";
return $print;

}
add_shortcode( 'cb_bookings_contracts', 'cb_bookings_contracts_shortcode' );

?>

