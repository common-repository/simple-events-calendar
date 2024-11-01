<?php
/*
Plugin Name: Simple Events Calendar
Plugin URI: http://www.studiostacks.com/plugins/simple-events-calendar
Description: A simple plugin for adding upcomding events to your wordpress site with micoformats tags. Manage your campaigns from the admin panel and group your events with labels. Easily add your events to any page or post by using a simple shortcode. By default the shortcode will display all 'upcoming' events, but it is also possible to show only 'expired' events or both. By using labels you can decide which events group to display. By default all groups are showed.
Many sites show their events, but it always looks bad when events that took place already are displayed as being upcoming. With this plugin your content will never be outdated.
Version: 1.4.1
Author: Jerry Rietveld
Author URI: http://www.jgrietveld.com
License: GPL2
*/

/*  Copyright 2015  Jerry G. Rietveld  (email : jerry+plugin@jgrietveld.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
define('SE_VERSION', '1.4.1');
define('SE_DOMAIN',get_bloginfo('url').'/wp-content/plugins/simple-events-calendar/');
define('SE_TEXTDOMAIN','simple-events-calendar');
define('SE_FOLDER', plugin_basename( dirname(__FILE__)) );
define('SE_PLUGIN', WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)));

// The date
if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
    define('DATE', __('%a %B %#d',SE_TEXTDOMAIN)); // For WINDOWS %e doesn't work
} else {
	define('DATE', __('%a %B %e',SE_TEXTDOMAIN));
}
// The time
if(get_option('SE_clock') == "12") {
	define('TIME', __('%l:%M %p',SE_TEXTDOMAIN)); // 12 hour AM/PM
} else {
	define('TIME', __('%H:%M',SE_TEXTDOMAIN)); // 24 hour military time
}
// The year
define('YEAR','%Y');

// Make plugin available for translation
function sec_init() {
	load_plugin_textdomain(SE_TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) );
}
add_action('plugins_loaded', 'sec_init');

// Setting the stylesheet:
if(isset($_REQUEST['page'])) {
	if($_REQUEST['page']=='simple-events' || $_REQUEST['page']=='simple-events-settings')  {
		add_action( 'admin_init', 'se_admin_head' );
		function se_admin_head() {
			wp_enqueue_style('se-styling', SE_DOMAIN .'se-admin-style.css', false, SE_VERSION, "all");
		}
	}
}

// Creating the Table in the DB or updating the structure
global $SE_db_version;
$SE_db_version = "2.1.1";

function SE_install() {
	global $wpdb;
	global $SE_db_version;
	
	$table_name = $wpdb->prefix . "simple_events";
	  
	$sql = "CREATE TABLE $table_name (
	id mediumint(9) NOT NULL AUTO_INCREMENT,
	event_start INT(10) ZEROFILL NOT NULL,
	event_end INT(10) ZEROFILL NOT NULL,
	event_title TEXT NOT NULL,
	event_desc LONGTEXT NULL,
	event_url TEXT NULL,
	event_loc TEXT NOT NULL,
	event_loc_url TEXT NULL,
	event_label VARCHAR(10) NOT NULL,
	UNIQUE KEY id (id)
	);";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	
	add_option("SE_db_version", $SE_db_version);
	
	global $wpdb;
	$installed_ver = get_option( "SE_db_version" );
	
	if( $installed_ver != $SE_db_version ) {	
		$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		event_start INT(10) ZEROFILL NOT NULL,
		event_end INT(10) ZEROFILL NOT NULL,
		event_title TEXT NOT NULL,
		event_desc LONGTEXT NULL,
		event_url TEXT NULL,
		event_loc TEXT NOT NULL,
		event_loc_url TEXT NULL,
		event_label VARCHAR(10) NOT NULL,
		UNIQUE KEY id (id)
		);";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		update_option( "SE_db_version", $SE_db_version );
	}
} // End Create Table in DB function

register_activation_hook(__FILE__,'SE_install');
   
function myplugin_update_db_check() {
	global $SE_db_version;
	if (get_site_option('SE_db_version') != $SE_db_version) {
		SE_install();
	}
}
add_action('plugins_loaded', 'myplugin_update_db_check');

// Set default settings
 if( !get_option('SE_clock')) {
	$SE_clock = "24";
	$SE_ext_css = "no";
	$SE_timezone = "+00:00";
	$SE_author = "manage_options";
	$SE_links = "none";
	$SE_twitter = "yes";
	add_option("SE_clock", $SE_clock);
	add_option("SE_ext_css", $SE_ext_css);
	add_option("SE_timezone", $SE_timezone);
	add_option("SE_author", $SE_author);
	add_option("SE_links", $SE_links);
	add_option("SE_twitter", $SE_twitter);
}

// Create the admin menu
add_action('admin_menu', 'SE_menu');

function SE_menu() {
	add_menu_page('Simple Events Calendar', 'Events', get_option('SE_author'), 'simple-events', 'simple_events_options','dashicons-calendar-alt');
}// End function SE_menu to add a button to the admin dashboard

function simple_events_options() {
	global $wpdb;						
	$table_name = $wpdb->prefix . "simple_events";

	if (!current_user_can(get_option('SE_author')))  {
		wp_die( __('You do not have sufficient permissions to manage events.',SE_TEXTDOMAIN) );
	} // end check authorisation level of the user logged in


// Delete event button
if(isset($_POST['SEsettingsUpdate'])) {
	echo '<div id="message" class="updated fade"><p>'.__('The settings have been saved!',SE_TEXTDOMAIN).'</p></div>';
}

if(isset($_POST['delete'])) {
	   $remove_event = sanitize_key( $_POST['event_id'] );
	   $wpdb->query(" DELETE FROM $table_name WHERE id = $remove_event ");
	   $result = $wpdb->get_row( "SELECT * FROM $table_name WHERE id = $remove_event" );
	   if(!$result) {
			echo '<div id="message" class="updated fade"><p>'.__('The event has been deleted!',SE_TEXTDOMAIN).'</p></div>';
		}
}

if(isset($_POST['store_event'])) {
	$sec_event_title = sanitize_text_field($_POST['event']['event_title']);
	if(!empty($sec_event_title)) { // New entries at least have to have a title, if none then nothing is written to DB
		$event = $_POST['event'];
		$event_start = mktime($event['event_start_hr'],$event['event_start_mn'],0,$event['event_start_month'],$event['event_start_day'],$event['event_start_yr']);
		$event_end = mktime($event['event_end_hr'],$event['event_end_mn'],0,$event['event_end_month'],$event['event_end_day'],$event['event_end_yr']);
		$newEvent['event_start'] 	= $event_start;
		$newEvent['event_end'] 		= $event_end;
		$newEvent['event_title'] 	= sanitize_text_field($event['event_title']);
		$newEvent['event_desc'] 	= sanitize_text_field($event['event_desc']);
		$newEvent['event_url'] 		= str_replace(" ", "", esc_url_raw($event['event_url']));
		$newEvent['event_loc'] 		= sanitize_text_field($event['event_loc']);
		$newEvent['event_loc_url'] 	= str_replace(" ", "", esc_url_raw($event['event_loc_url']));
		$newEvent['event_label'] 	= strtolower(str_replace(" ", "", sanitize_title($event['event_label'])));
		
		$wpdb->insert( $table_name, $newEvent );
		$result = $wpdb->insert_id;
		if(!$result) {
			echo '<div id="message" class="error fade"><p>'.__('Unfortunately something went wrong...',SE_TEXTDOMAIN).'</p></div>';
		} else { echo '<div id="message" class="updated fade"><p>'.__('The event was successfully added! Use the shortcode <code>[events]</code> to display the events. Check the <a href="?page=simple-events&tab=help">Help</a> tab below for more advanced options.',SE_TEXTDOMAIN).'</p></div>'; }
		// End check  if entry was written successfully to the DB 
	} else { // So if no title was entered for the event then:
		echo '<div id="message" class="error fade"><p>'.__('C\'mon, at least give me a title!',SE_TEXTDOMAIN).'</p></div>';
	} // End check if title was entered or not and if so, the event was written to the DB
} // END check if event was saved 

elseif(isset($_POST['update_event'])) {
	global $wpdb;						
	$table_name = $wpdb->prefix . "simple_events";
	$event = $_POST['event'];
	$event_start = mktime($event['event_start_hr'],$event['event_start_mn'],0,$event['event_start_month'],$event['event_start_day'],$event['event_start_yr']);
	$event_end = mktime($event['event_end_hr'],$event['event_end_mn'],0,$event['event_end_month'],$event['event_end_day'],$event['event_end_yr']);
	$editEvent['event_start'] 	= $event_start;
	$editEvent['event_end'] 	= $event_end;
	$editEvent['event_title'] 	= sanitize_text_field($event['event_title']);
	$editEvent['event_desc'] 	= sanitize_text_field($event['event_desc']);
	$editEvent['event_url'] 	= str_replace(" ", "", esc_url_raw($event['event_url']));
	$editEvent['event_loc'] 	= sanitize_text_field($event['event_loc']);
	$editEvent['event_loc_url'] = str_replace(" ", "", esc_url_raw($event['event_loc_url']));
	$editEvent['event_label'] 	= strtolower(str_replace(" ", "", sanitize_title($event['event_label'])));
	
	$where['id'] = sanitize_key( $_POST['eventid'] );
	
	$result = $wpdb->update( $table_name, $editEvent, $where );
	if($result !== false) {
		echo '<div id="message" class="updated fade"><p>'.__('The event was succesfully updated!',SE_TEXTDOMAIN).'</p></div>';
	} else {
		echo '<div id="message" class="error fade"><p>'.__('There was a problem updating your event!',SE_TEXTDOMAIN).'</p></div>';
	}
} // END check if event was updated ?>

<div class="wrap">
	<h1>Simple Events Calendar <span class="version">v<?php echo SE_VERSION;?></span></h1>

<?php

if( isset( $_GET[ 'tab' ] ) ) {
	$sec_admin_tabs = array (
		"existing_events",
		"new_events",
		"advanced_options",
		"help"
	);
    $get_tab = sanitize_key($_GET[ 'tab' ]);
    if(in_array($get_tab, $sec_admin_tabs)) {
    	$active_tab = $get_tab;
    } else {
    	$active_tab = "existing_events";
    }
} else {
	$active_tab = "existing_events";
} // end if

?>

<h2 class="nav-tab-wrapper">
    <a href="?page=simple-events&tab=existing_events" class="nav-tab <?php echo $active_tab == 'existing_events' ? 'nav-tab-active' : ''; ?>"><?php _e('Stored events',SE_TEXTDOMAIN);?></a>
    <a href="?page=simple-events&tab=new_events" class="nav-tab <?php echo $active_tab == 'new_events' ? 'nav-tab-active' : ''; ?>"><?php _e('Add event',SE_TEXTDOMAIN);?></a>
    <a href="?page=simple-events&tab=advanced_options" class="nav-tab <?php echo $active_tab == 'advanced_options' ? 'nav-tab-active' : ''; ?>"><?php _e('Settings',SE_TEXTDOMAIN);?></a>
    <a href="?page=simple-events&tab=help" class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>"><?php _e('Help',SE_TEXTDOMAIN);?></a>
</h2>


    <div class="se-nav-tab option-page <?php echo $active_tab == 'advanced_options' ? 'se-nav-tab-active' : ''; ?>">

		<?php if(isset($_POST['SEsettingsUpdate'])) {
			if(isset($_POST['SE_clock']) && $_POST['SE_clock'] == 12) {
				$SE_clock = 12; 
			} else {
				$SE_clock = 24;
			}
			$SE_timezone = $_POST['SE_timezone'];
			$SE_author = $_POST['SE_author'];
			$SE_links = $_POST['SE_links'];
			if(isset($_POST['SE_twitter']) && $_POST['SE_twitter'] == "no") $SE_twitter = "no"; else $SE_twitter = "yes";
			if(isset($_POST['SE_ext_css']) && $_POST['SE_ext_css'] == "yes") $SE_ext_css = "yes"; else $SE_ext_css = "no";
			update_option("SE_clock", $SE_clock);
			update_option("SE_ext_css", $SE_ext_css);
			update_option("SE_timezone", $SE_timezone);
			update_option("SE_author", $SE_author);
			update_option("SE_links", $SE_links);
			update_option("SE_twitter", $SE_twitter);
		}
			?>
    	<form method="post" class="advanced_options_form">
    		<table class="form-table">
    			<tbody>
    				<tr>
						<th colspan="2"><h2>Advanced Settings</h2></th>
					</tr>

			    	<tr valign="top">
						<th scope="row"><?php _e('Time notation',SE_TEXTDOMAIN);?></th>
				    	<td class="activated">
				        	<input type="checkbox" name="SE_clock" value="12" <?php if(get_option('SE_clock') == "12") echo "checked";?> />
				        	<label><?php _e('Change time to 12 hour (AM/PM) clock notation.',SE_TEXTDOMAIN);?></label>
				        </td>
				    </tr>

			    	<tr valign="top">
						<th scope="row"><?php _e('Timezone',SE_TEXTDOMAIN);?></th>
				    	<td class="activated">
				        	<select name="SE_timezone" id="DropDownTimezone">
								<option value="-12:00" <?php if(get_option('SE_timezone') == "-12:00") echo "selected";?>>(GMT -12:00) Eniwetok, Kwajalein</option>
								<option value="-11:00" <?php if(get_option('SE_timezone') == "-11:00") echo "selected";?>>(GMT -11:00) Midway Island, Samoa</option>
								<option value="-10:00" <?php if(get_option('SE_timezone') == "-10:00") echo "selected";?>>(GMT -10:00) Hawaii</option>
								<option value="-09:00" <?php if(get_option('SE_timezone') == "-09:00") echo "selected";?>>(GMT -9:00) Alaska</option>
								<option value="-08:00" <?php if(get_option('SE_timezone') == "-08:00") echo "selected";?>>(GMT -8:00) Pacific Time (US &amp; Canada)</option>
								<option value="-07:00" <?php if(get_option('SE_timezone') == "-07:00") echo "selected";?>>(GMT -7:00) Mountain Time (US &amp; Canada)</option>
								<option value="-06:00" <?php if(get_option('SE_timezone') == "-06:00") echo "selected";?>>(GMT -6:00) Central Time (US &amp; Canada), Mexico City</option>
								<option value="-05:00" <?php if(get_option('SE_timezone') == "-05:00") echo "selected";?>>(GMT -5:00) Eastern Time (US &amp; Canada), Bogota, Lima</option>
								<option value="-04:00" <?php if(get_option('SE_timezone') == "-04:00") echo "selected";?>>(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz</option>
								<option value="-03:30" <?php if(get_option('SE_timezone') == "-03:30") echo "selected";?>>(GMT -3:30) Newfoundland</option>
								<option value="-03:00" <?php if(get_option('SE_timezone') == "-03:00") echo "selected";?>>(GMT -3:00) Brazil, Buenos Aires, Georgetown</option>
								<option value="-02:00" <?php if(get_option('SE_timezone') == "-02:00") echo "selected";?>>(GMT -2:00) Mid-Atlantic</option>
								<option value="-01:00" <?php if(get_option('SE_timezone') == "-01:00") echo "selected";?>>(GMT -1:00 hour) Azores, Cape Verde Islands</option>
								<option value="+00:00" <?php if(get_option('SE_timezone') == "+00:00") echo "selected";?>>(GMT) Western Europe Time, London, Lisbon, Casablanca</option>
								<option value="+01:00" <?php if(get_option('SE_timezone') == "+01:00") echo "selected";?>>(GMT +1:00 hour) Brussels, Copenhagen, Madrid, Paris</option>
								<option value="+02:00" <?php if(get_option('SE_timezone') == "+02:00") echo "selected";?>>(GMT +2:00) Kaliningrad, South Africa</option>
								<option value="+03:00" <?php if(get_option('SE_timezone') == "+03:00") echo "selected";?>>(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburg</option>
								<option value="+03:30" <?php if(get_option('SE_timezone') == "+03:30") echo "selected";?>>(GMT +3:30) Tehran</option>
								<option value="+04:00" <?php if(get_option('SE_timezone') == "+04:00") echo "selected";?>>(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi</option>
								<option value="+04:30" <?php if(get_option('SE_timezone') == "+04:30") echo "selected";?>>(GMT +4:30) Kabul</option>
								<option value="+05:00" <?php if(get_option('SE_timezone') == "+05:00") echo "selected";?>>(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent</option>
								<option value="+05:30" <?php if(get_option('SE_timezone') == "+05:30") echo "selected";?>>(GMT +5:30) Bombay, Calcutta, Madras, New Delhi</option>
								<option value="+05:45" <?php if(get_option('SE_timezone') == "+05:45") echo "selected";?>>(GMT +5:45) Kathmandu</option>
								<option value="+06:00" <?php if(get_option('SE_timezone') == "+06:00") echo "selected";?>>(GMT +6:00) Almaty, Dhaka, Colombo</option>
								<option value="+07:00" <?php if(get_option('SE_timezone') == "+07:00") echo "selected";?>>(GMT +7:00) Bangkok, Hanoi, Jakarta</option>
								<option value="+08:00" <?php if(get_option('SE_timezone') == "+08:00") echo "selected";?>>(GMT +8:00) Beijing, Perth, Singapore, Hong Kong</option>
								<option value="+09:00" <?php if(get_option('SE_timezone') == "+09:00") echo "selected";?>>(GMT +9:00) Tokyo, Seoul, Osaka, Sapporo, Yakutsk</option>
								<option value="+09:30" <?php if(get_option('SE_timezone') == "+09:30") echo "selected";?>>(GMT +9:30) Adelaide, Darwin</option>
								<option value="+10:00" <?php if(get_option('SE_timezone') == "+10:00") echo "selected";?>>(GMT +10:00) Eastern Australia, Guam, Vladivostok</option>
								<option value="+11:00" <?php if(get_option('SE_timezone') == "+11:00") echo "selected";?>>(GMT +11:00) Magadan, Solomon Islands, New Caledonia</option>
								<option value="+12:00" <?php if(get_option('SE_timezone') == "+12:00") echo "selected";?>>(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka</option>
								</select>
				        </td>
				    </tr>

			    	<tr valign="top">
						<th scope="row"><?php _e('Link action',SE_TEXTDOMAIN);?></th>
				    	<td class="activated">
				        	<select name="SE_links">
			                	<option value="both" <?php if(get_option('SE_links') == "both") echo "selected";?>><?php _e('Open both links in a new window',SE_TEXTDOMAIN);?></option>
			                	<option value="location" <?php if(get_option('SE_links') == "location") echo "selected";?>><?php _e('Open only the Location link in a new window',SE_TEXTDOMAIN);?></option>
			                	<option value="information" <?php if(get_option('SE_links') == "information") echo "selected";?>><?php _e('Open only the More Information link in a new window',SE_TEXTDOMAIN);?></option>
			                	<option value="none" <?php if(get_option('SE_links') == "none") echo "selected";?>><?php _e('Open both links in the same window',SE_TEXTDOMAIN);?></option>
			                </select>
				        </td>
				    </tr>
<?php if (current_user_can('manage_options'))  { ?>
			    	<tr valign="top">
						<th scope="row"><?php _e('Event managers',SE_TEXTDOMAIN);?></th>
				    	<td class="activated">
				        	<select name="SE_author">
			                	<option value="manage_options" <?php if(get_option('SE_author') == "manage_options") echo "selected";?>>Administrator</option>
			                	<option value="publish_pages" <?php if(get_option('SE_author') == "publish_pages") echo "selected";?>>Editor</option>
			                	<option value="publish_posts" <?php if(get_option('SE_author') == "publish_posts") echo "selected";?>>Author</option>
			                	<option value="edit_posts" <?php if(get_option('SE_author') == "edit_posts") echo "selected";?>>Contributor</option>
			                	<option value="read" <?php if(get_option('SE_author') == "read") echo "selected";?>>Subscriber</option>                    
			                </select>
				        </td>
				    </tr>
<?php } ?>
			    	<tr valign="top">
						<th scope="row"><?php _e('Twitter',SE_TEXTDOMAIN);?></th>
				    	<td class="activated">
				        	<input type="checkbox" name="SE_twitter" value="no" <?php if(get_option('SE_twitter') == "no") echo "checked";?> />
				        	<label><?php _e('Disable Twitter',SE_TEXTDOMAIN);?></label>
				        	<span class="explanation"><?php _e('By disabling Twitter, the JavaScript of the Twitter API will not be loaded for this plugin.',SE_TEXTDOMAIN);?></span>
				        </td>
				    </tr>
				    <!--<tr valign="top">
						<th scope="row"><?php _e('Styling',SE_TEXTDOMAIN);?></th>
				    	<td class="activated">
				        	<input type="checkbox" name="SE_ext_css" value="yes" <?php if(get_option('SE_ext_css') == "yes") echo "checked";?> />
				        	<label><?php _e('Disable styling and use your own CSS',SE_TEXTDOMAIN);?></label>
				        	<span class="explanation"><?php _e('By disabling the styling all CSS of the event listing will be switched off and you are required to write your own.',SE_TEXTDOMAIN);?></span>
				        </td>
				    </tr>-->
				    <tr>
				    	<th></th>
				    	<td>
				    		<p class="submit">
				            	<input name="SEsettingsUpdate" type="submit" value="<?php _e('Update settings',SE_TEXTDOMAIN)?>" class="button-primary" />
				            </p>
				    	</td>
				    </tr>
    			</tbody>
    		</table>
        </form>
    </div> <!-- END se-nav-tab option-page -->
    

	<div class="se-nav-tab new-page <?php echo $active_tab == 'new_events' ? 'se-nav-tab-active' : ''; ?>">
        <form method="post">
			<?php
				$year = date('Y');
				$month = date('n');
				$day = date('j');
				$start_timeH = date('G');
				if(date('i') > 37) {
					$start_timeM = '45';
				} elseif(date('i') > 20) {
					$start_timeM = '30';
				} elseif(date('i') > 5) {
					$start_timeM = '15';
				} else {
					$start_timeM = '0';
				}
				$end_year = (date('Y',mktime(date('G')+1)));
				$end_month = (date('n',mktime(date('G')+1)));
				$end_day = (date('j',mktime(date('G')+1)));
				$end_timeH = (date('G',mktime(date('G')+1)));
			?>
	    	<table class="form-table">
	    		<tbody>
			    	<tr>
						<th colspan="2">
							<h2><?php _e('Add new event',SE_TEXTDOMAIN);?></h2>
						</th>
					</tr>

			    	<tr>
				        <th class="se_entry_start"><?php _e('Start:',SE_TEXTDOMAIN);?></th>
				        <td>
					        <select name="event[event_start_day]">
								<?php $i = 0;
								while($i < 31) {
									$i++;
									if($day != $i) echo '<option value="' . $i . '">' . $i . '</options>'; else echo '<option selected value="' . $i . '">' . $i . '</options>';
								} ?>
					        </select>
					        <select name="event[event_start_month]"> <!-- ## EVENT START MONTH ## -->
								<?php $i = 0;
								while($i < 12) {
									$i++;
									if($month != $i) echo '<option value="' . $i . '">' . date('F', mktime(0,0,0, $i, 1, date('Y') )) . '</options>'; else echo '<option selected value="' . $i . '">' . date('F', mktime(0,0,0, $i, 1, date('Y') )) . '</options>';
								} ?>
					        </select>
					        <select name="event[event_start_yr]"> <!-- ## EVENT START YEAR ## -->
								<?php $i = 1979;
								while($i < 2040) {
									$i++;
									if($year != $i) echo '<option value="' . $i . '">' . $i . '</options>'; else echo '<option selected value="' . $i . '">' . $i . '</options>';
								} ?>
					        </select> - 
				        	<select name="event[event_start_hr]"> <!-- ## EVENT START HOUR ## -->
								<?php $i = 0;
								while($i < 24) {
									if($start_timeH != $i) echo '<option value="' . $i . '">' . date('H', mktime( $i ,0,0,0 )) . '</options>'; else echo '<option selected value="' . $i . '">' . date('H', mktime( $i ,0,0,0 )) . '</options>';	
									$i++;
								} ?>
					        </select>&nbsp;:&nbsp;<select name="event[event_start_mn]">
					        	<option <?php if($start_timeM == 0) echo "selected";?> value="0">00</option>
					        	<option <?php if($start_timeM == 5) echo "selected";?> value="5">05</option>
					        	<option <?php if($start_timeM == 10) echo "selected";?> value="10">10</option>
					        	<option <?php if($start_timeM == 15) echo "selected"; ?> value="15">15</option>
					        	<option <?php if($start_timeM == 20) echo "selected";?> value="20">20</option>
					        	<option <?php if($start_timeM == 25) echo "selected";?> value="25">25</option>
					        	<option <?php if($start_timeM == 30) echo "selected";?> value="30">30</option>
					        	<option <?php if($start_timeM == 35) echo "selected"; ?> value="35">35</option>
					        	<option <?php if($start_timeM == 40) echo "selected";?> value="40">40</option>
					        	<option <?php if($start_timeM == 45) echo "selected";?> value="45">45</option>
					        	<option <?php if($start_timeM == 50) echo "selected";?> value="50">50</option>
					        	<option <?php if($start_timeM == 55) echo "selected"; ?> value="55">55</option>
					        </select>
				        </td>
			        </tr>
			        <tr>
				        <th class="se_entry_start"><?php _e('End:',SE_TEXTDOMAIN);?></th>
				        <td>
					        <select name="event[event_end_day]"> <!-- ## EVENT END DAY ## -->
								<?php $i = 0;
								while($i < 31) {
									$i++;
									if($end_day != $i) echo '<option value="' . $i . '">' . $i . '</options>'; else echo '<option selected value="' . $i . '">' . $i . '</options>';
								} ?>
					        </select>
					        <select name="event[event_end_month]"> <!-- ## EVENT END MONTH ## -->
								<?php $i = 0;
								while($i < 12) {
									$i++;
									if($end_month != $i) echo '<option value="' . $i . '">' . date('F', mktime(0,0,0, $i, 1, date('Y') )) . '</options>'; else echo '<option selected value="' . $i . '">' . date('F', mktime(0,0,0, $i, 1, date('Y') )) . '</options>';
								} ?>
					        </select>
					        <select name="event[event_end_yr]"> <!-- ## EVENT END YEAR ## -->

								<?php $i = 1979;
								while($i < 2040) {
									$i++;
									if($end_year != $i) echo '<option value="' . $i . '">' . $i . '</options>'; else echo '<option selected value="' . $i . '">' . $i . '</options>';
								} ?>
					        </select> - 
					        <select name="event[event_end_hr]"> <!-- ## EVENT END HOUR ## -->
								<?php $i = 0;
								while($i < 24) {
									if($end_timeH != $i) echo '<option value="' . $i . '">' . date('H', mktime( $i ,0,0,0 )) . '</options>'; else echo '<option selected value="' . $i . '">' . date('H', mktime( $i ,0,0,0 )) . '</options>';	
									$i++;
								} ?>
					        </select>&nbsp;:&nbsp;<select name="event[event_end_mn]">
					        	<option <?php if($start_timeM == 0) echo "selected";?> value="0">00</option>
					        	<option <?php if($start_timeM == 5) echo "selected";?> value="5">05</option>
					        	<option <?php if($start_timeM == 10) echo "selected";?> value="10">10</option>
					        	<option <?php if($start_timeM == 15) echo "selected"; ?> value="15">15</option>
					        	<option <?php if($start_timeM == 20) echo "selected";?> value="20">20</option>
					        	<option <?php if($start_timeM == 25) echo "selected";?> value="25">25</option>
					        	<option <?php if($start_timeM == 30) echo "selected";?> value="30">30</option>
					        	<option <?php if($start_timeM == 35) echo "selected"; ?> value="35">35</option>
					        	<option <?php if($start_timeM == 40) echo "selected";?> value="40">40</option>
					        	<option <?php if($start_timeM == 45) echo "selected";?> value="45">45</option>
					        	<option <?php if($start_timeM == 50) echo "selected";?> value="50">50</option>
					        	<option <?php if($start_timeM == 55) echo "selected"; ?> value="55">55</option>
					        </select>
				        </td>
			        </tr>
			        
			        <tr>
				        <th class="se_entry_title"><?php _e('Title:',SE_TEXTDOMAIN);?></th>
				        <td> 
				        	<input name="event[event_title]" size="80" type="text" />
				        </td>
			        </tr>
			        <tr>
				        <th class="se_entry_description"><?php _e('Description:',SE_TEXTDOMAIN);?></th>
				        <td> 
				            <textarea class="textarea1" cols="51" rows="5" name="event[event_desc]"></textarea>
				        </td>
			        </tr>
			        <tr>
			        <th class="se_entry_label"><?php _e('Label:',SE_TEXTDOMAIN);?></th>
				        <td> 
				        	<input name="event[event_label]" type="text" size="15" maxlength="10" />
				            <span class="labelnote"><?php _e('Max. 10 characters. All spaces are removed when stored.',SE_TEXTDOMAIN);?></span>
				        </td>
			        </tr>
			        <tr>
				        <th class="se_entry_url"><?php _e('Event URL:',SE_TEXTDOMAIN);?></th>
				        <td>
				        	<input name="event[event_url]" size="80" type="text" />
				        </td>
			        </tr>
			        <tr>
				        <th class="se_entry_loc"><?php _e('Location:',SE_TEXTDOMAIN);?></th>
				        <td> 
				        	<input name="event[event_loc]" size="80" type="text" />
				        </td>
			        </tr>
			        <tr>
				        <th class="se_entry_loc_url"><?php _e('Location URL:',SE_TEXTDOMAIN);?></th>
				        <td> 
				        	<input name="event[event_loc_url]" size="80" type="text" />
				        </td>
			        </tr>
			        <tr>
			        	<th>
			            </th>
			            <td>
			            	<p class="submit">
			            	<input name="store_event" type="submit" value="<?php _e('Save event',SE_TEXTDOMAIN)?>" class="button-primary" />    
							<input type="hidden" name="action" value="save" />
			                </p>
			            </td>
			        </tr>
			    </tbody>
	    	</table>
	    </form>
	</div> <!-- END se-nav-tab new-page -->

<!-- Displaying the stored events: -->
<div class="se-nav-tab existing-page <?php echo $active_tab == 'existing_events' ? 'se-nav-tab-active' : ''; ?>">

	<?php if(isset($_POST['edit'])) { 

	global $wpdb;
	$table_name = $wpdb->prefix . "simple_events";
	$edit_event = sanitize_key($_POST['event_id']);
	$update = $wpdb->get_results(" SELECT * FROM $table_name WHERE id = $edit_event ", "ARRAY_A");
	?>


	<?php
	$year = date('Y',$update[0]['event_start']);
	$month = date('n',$update[0]['event_start']);
	$day = date('j',$update[0]['event_start']);
	$start_timeH = date('G',$update[0]['event_start']);
	$start_timeM = date('i',$update[0]['event_start']);
	$end_year = date('Y',$update[0]['event_end']);
	$end_month = date('n',$update[0]['event_end']);
	$end_day = date('j',$update[0]['event_end']);
	$end_timeH = date('G',$update[0]['event_end']);
	$end_timeM = date('i',$update[0]['event_end']);
	?>

	<form method="post">
	    <table class="form-table">
	    	<tbody>
		    	<tr>
					<th colspan="2">
						<h2><?php _e('Edit event:',SE_TEXTDOMAIN);?></h2>
					</th>
				</tr>
	    

		    	<tr>
			        <th class="se_entry_start"><?php _e('Start:',SE_TEXTDOMAIN);?></th>
			        <td>
				        <select name="event[event_start_day]"> <!-- ## EVENT START DAY ## -->
							<?php $i = 0;
							while($i < 31) {
								$i++;
								if($day != $i) echo '<option value="' . $i . '">' . $i . '</options>'; else echo '<option selected value="' . $i . '">' . $i . '</options>';
							} ?>
				        </select>
				        <select name="event[event_start_month]"> <!-- ## EVENT START MONTH ## -->
							<?php $i = 0;
							while($i < 12) {
								$i++;
								if($month != $i) echo '<option value="' . $i . '">' . date('F', mktime(0,0,0, $i, 1, date('Y'))) . '</options>'; else echo '<option selected value="' . $i . '">' . date('F', mktime(0,0,0, $i, 1, date('Y') )) . '</options>';
							} ?>
				        </select>
				        <select name="event[event_start_yr]"> <!-- ## EVENT START YEAR ## -->
							<?php $i = 1979;
							while($i < 2040) {
								$i++;
								if($year != $i) echo '<option value="' . $i . '">' . $i . '</options>'; else echo '<option selected value="' . $i . '">' . $i . '</options>';
							} ?>
				        </select> - 
				        <select name="event[event_start_hr]"> <!-- ## EVENT START HOUR ## -->
							<?php $i = 0;
							while($i < 24) {
								if($start_timeH != $i) echo '<option value="' . $i . '">' . date('H', mktime( $i ,0,0,0 )) . '</options>'; else echo '<option selected value="' . $i . '">' . date('H', mktime( $i ,0,0,0 )) . '</options>';	
								$i++;
							} ?>
				        </select>&nbsp;:&nbsp;<select name="event[event_start_mn]">
				        	<option <?php if($start_timeM == 0) echo "selected";?> value="0">00</option>
				        	<option <?php if($start_timeM == 5) echo "selected";?> value="5">05</option>
				        	<option <?php if($start_timeM == 10) echo "selected";?> value="10">10</option>
				        	<option <?php if($start_timeM == 15) echo "selected"; ?> value="15">15</option>
				        	<option <?php if($start_timeM == 20) echo "selected";?> value="20">20</option>
				        	<option <?php if($start_timeM == 25) echo "selected";?> value="25">25</option>
				        	<option <?php if($start_timeM == 30) echo "selected";?> value="30">30</option>
				        	<option <?php if($start_timeM == 35) echo "selected"; ?> value="35">35</option>
				        	<option <?php if($start_timeM == 40) echo "selected";?> value="40">40</option>
				        	<option <?php if($start_timeM == 45) echo "selected";?> value="45">45</option>
				        	<option <?php if($start_timeM == 50) echo "selected";?> value="50">50</option>
				        	<option <?php if($start_timeM == 55) echo "selected"; ?> value="55">55</option>
				        </select>
			        </td>
		        </tr>
		        
		        <tr>
			        <th class="se_entry_start"><?php _e('End:',SE_TEXTDOMAIN);?></th>
			        <td>
				        <select name="event[event_end_day]"> <!-- ## EVENT END DAY ## -->
							<?php $i = 0;
							while($i < 31) {
								$i++;
								if($end_day != $i) echo '<option value="' . $i . '">' . $i . '</options>'; else echo '<option selected value="' . $i . '">' . $i . '</options>';
							} ?>
				        </select>
				        <select name="event[event_end_month]"> <!-- ## EVENT END MONTH ## -->
							<?php $i = 0;
							while($i < 12) {
								$i++;
								if($end_month != $i) echo '<option value="' . $i . '">' . date('F', mktime(0,0,0, $i, 1, date('Y') )) . '</options>'; else echo '<option selected value="' . $i . '">' . date('F', mktime(0,0,0, $i, 1, date('Y') )) . '</options>';
							} ?>
				        </select>
				        <select name="event[event_end_yr]"> <!-- ## EVENT END YEAR ## -->
							<?php $i = 1979;
							while($i < 2040) {
								$i++;
								if($end_year != $i) echo '<option value="' . $i . '">' . $i . '</options>'; else echo '<option selected value="' . $i . '">' . $i . '</options>';
							} ?>
				        </select> - 
				        <select name="event[event_end_hr]"> <!-- ## EVENT END HOUR ## -->
							<?php $i = 0;
							while($i < 24) {
								if($end_timeH != $i) echo '<option value="' . $i . '">' . date('H', mktime( $i ,0,0,0 )) . '</options>'; else echo '<option selected value="' . $i . '">' . date('H', mktime( $i ,0,0,0 )) . '</options>';	
								$i++;
							} ?>
				        </select>&nbsp;:&nbsp;<select name="event[event_end_mn]">
				        	<option <?php if($end_timeM == 0) echo "selected";?> value="0">00</option>
				        	<option <?php if($end_timeM == 5) echo "selected";?> value="5">05</option>
				        	<option <?php if($end_timeM == 10) echo "selected";?> value="10">10</option>
				        	<option <?php if($end_timeM == 15) echo "selected"; ?> value="15">15</option>
				        	<option <?php if($end_timeM == 20) echo "selected";?> value="20">20</option>
				        	<option <?php if($end_timeM == 25) echo "selected";?> value="25">25</option>
				        	<option <?php if($end_timeM == 30) echo "selected";?> value="30">30</option>
				        	<option <?php if($end_timeM == 35) echo "selected"; ?> value="35">35</option>
				        	<option <?php if($end_timeM == 40) echo "selected";?> value="40">40</option>
				        	<option <?php if($end_timeM == 45) echo "selected";?> value="45">45</option>
				        	<option <?php if($end_timeM == 50) echo "selected";?> value="50">50</option>
				        	<option <?php if($end_timeM == 55) echo "selected"; ?> value="55">55</option>
				        </select>  
			        </td>
		        </tr>
	        
		        <tr>
			        <th class="se_entry_title"><?php _e('Title:',SE_TEXTDOMAIN);?></th>
			        <td> 
			        	<input name="event[event_title]" size="80" type="text" value="<?php echo esc_html($update[0]['event_title']);?>" />
			        </td>
		        </tr>
		        <tr>
			        <th class="se_entry_description"><?php _e('Description:',SE_TEXTDOMAIN);?></th>
			        <td>  
			        	
			            <textarea class="textarea1" cols="51" rows="5" name="event[event_desc]"><?php echo esc_textarea(stripslashes($update[0]['event_desc']));?></textarea>
			        </td>
		        </tr>
		        <tr>
			        <th class="se_entry_label"><?php _e('Label:',SE_TEXTDOMAIN);?></th>
			        <td> 
			        	<input name="event[event_label]" type="text" size="15" maxlength="10" value="<?php echo esc_html( $update[0]['event_label'] );?>" />
			            <span class="labelnote"><?php _e('Max. 10 characters. All spaces are removed when stored.',SE_TEXTDOMAIN);?></span>
			        </td>
		        </tr>
		        <tr>
			        <th class="se_entry_url"><?php _e('Event URL:',SE_TEXTDOMAIN);?></th>
			        <td>
			        	<input name="event[event_url]" size="80" type="text" value="<?php echo esc_url( $update[0]['event_url'] );?>" />
			        </td>
		        </tr>
		        <tr>
		        	<th class="se_entry_loc"><?php _e('Location:',SE_TEXTDOMAIN);?></th>
			        <td> 
			        	<input name="event[event_loc]" size="80" type="text" value="<?php echo esc_html( $update[0]['event_loc'] );?>" />
			        </td>
		        </tr>
		        <tr>
		        	<th class="se_entry_loc_url"><?php _e('Location URL:',SE_TEXTDOMAIN);?></th>
			        <td> 
			        	<input name="event[event_loc_url]" size="80" type="text" value="<?php echo esc_url( $update[0]['event_loc_url'] );?>" />
			        </td>
	        	</tr>
		        <tr>
		        	<th>
		            </th>
		            <td>
		            	<p class="submit">
		                <input type="hidden" name="eventid" value="<?php echo $edit_event;?>" />
		            	<input name="update_event" type="submit" value="<?php _e('Update event',SE_TEXTDOMAIN)?>" class="button-primary" />    
						<input type="hidden" name="action" value="save" />
						<a href="?page=simple-events&tab=existing_events" class="button-secondary">Cancel</a>
		                </p>
		            </td>
		        </tr>
	    
	    	</tbody>
	    </table>
	</form>
	<?php } ?>

<?php

global $wpdb;
$table_name = $wpdb->prefix . "simple_events";
$myevents = $wpdb->get_results(" SELECT * FROM $table_name ORDER BY event_start ", "ARRAY_A");
if(count($myevents) > 0) {

	// Display all events currently stored in the database
	setlocale(LC_ALL, get_locale());
?>

<table class="form-table">
	<tbody>
		<tr>
			<th colspan="2">
				<h2><?php _e('Upcoming events',SE_TEXTDOMAIN);?></h2>
			</th>
		</tr>
	</tbody>
</table>

<table cellspacing="5" width="600">
	<tbody>
	<?php
	$i = 0;
	foreach($myevents as $details) { 
		if ($details['event_end'] > time()) { 
			++$i; ?>
    	
        <tr>
	        <th class="eventtitle" colspan="3">
	        	<form method="post">
	        		<input type="hidden" name="event_id" value="<?php echo $details['id'];?>" />
	        		
	        		<button class="se-button" type="submit" name="edit" value="<?php _e('Edit',SE_TEXTDOMAIN);?>"><span class="dashicons dashicons-edit"></span></button>
	        		<button class="se-button" type="submit" name="delete" value="<?php _e('Remove',SE_TEXTDOMAIN);?>"><span class="dashicons dashicons-trash"></span></button>
	        	</form> 
	        	<span title="<?php echo stripslashes(esc_html($details['event_title'])); ?>">
	        		<?php echo shortenstring(stripslashes(esc_html($details['event_title'])),40);?>
	        	</span>
	        </th>
        </tr>
        <tr>
        <td class="eventdescription" colspan="3"><?php echo stripslashes(nl2br($details['event_desc']));?></td>
        </tr>
        <tr>
        <td class="eventstart" width="33%"><?php echo strftime(DATE ." ". YEAR, $details['event_start']);?><br /><?php echo strftime(TIME, $details['event_start']);?></td>
        <td class="eventend" width="33%"><?php echo strftime(DATE ." ". YEAR, $details['event_end']);?><br /><?php echo strftime(TIME, $details['event_end']);?></td>
        <td></td>
        </tr>
        <tr>
        <td class="eventlocation">
			<?php if($details['event_loc'] != "" && $details['event_loc_url'] != "") {
				echo '<span class="dashicons dashicons-location"></span> <a class="eventlinks" href="'.esc_url( httpprefix($details['event_loc_url'])).'" target="_blank">'.shortenstring(esc_html( $details['event_loc'] ),20).'</a>'; 
			} elseif($details['event_loc'] != "" ) {
				echo '<span class="dashicons dashicons-location"></span> '. shortenstring(esc_html( $details['event_loc'] ),20);
			} elseif($details['event_loc_url'] != "" ) {
				echo '<span class="dashicons dashicons-location"></span> <a class="eventlinks" href="'.esc_url( httpprefix($details['event_loc_url'])).'">'.shortenstring(esc_url(httpprefix($details['event_loc_url'])),20).'</a>'; 				
			} else {
				echo '<span class="dashicons dashicons-location"></span>';
			}
			
			
			?></td>
            <td class="viewlabel">
			<?php if($details['event_label'] != "") { ?>
        		<span class="dashicons dashicons-tag"></span><span class="eventlabel" title="<?php _e('When including this label on your page when displaying events, only events with this label will be displayed',SE_TEXTDOMAIN);?>"><?php echo esc_html($details['event_label']);?></span>
			<?php } else { ?>
                    <span class="dashicons dashicons-tag"></span>
            <?php } ?>
            </td>
            <td>
			<?php if($details['event_url'] != "") {
				echo '<span class="dashicons dashicons-admin-links"></span> <a class="eventlinks" href="'.esc_url( httpprefix($details['event_url']) ).'" target="_blank">'.shortenstring(esc_url( httpprefix($details['event_url'])),20).'</a>';
			} else {
				echo '<span class="dashicons dashicons-admin-links"></span>';
			} ?>
            </td>
        </tr>
        <tr><td colspan="3"><hr /></td></tr>
<?php } // end foreach stored events display table
} ?>
	</tbody>
</table>
	<?php if($i < 1) { ?> 
		<p class="se-empty-list">You currently have no upcoming events. 
			<a href="?page=simple-events&tab=new_events" class="button-primary"><?php _e('Add event',SE_TEXTDOMAIN);?></a>
		</p>
	<?php } ?>

<table class="form-table">
	<tbody>
		<tr>
			<th colspan="2">
				<h2><?php _e('Past events',SE_TEXTDOMAIN);?></h2>
			</th>
		</tr>
	</tbody>
</table>

<table cellspacing="5" width="600">
	<tbody>
		<?php 
		$i = 0;
		foreach($myevents as $details) { 
			if ($details['event_end'] < time()) { 
				++$i; ?>
        <tr>
        <th class="eventtitle" colspan="3">
        	<form method="post">
        		<input type="hidden" name="event_id" value="<?php echo esc_attr($details['id']);?>" />
        		
        		<button class="se-button" type="submit" name="edit" value="<?php _e('Edit',SE_TEXTDOMAIN);?>"><span class="dashicons dashicons-edit"></span></button>
        		<button class="se-button" type="submit" name="delete" value="<?php _e('Remove',SE_TEXTDOMAIN);?>"><span class="dashicons dashicons-trash"></span></button>
        	</form> 
				<span title="<?php echo stripslashes(esc_html($details['event_title'])); ?>">
	        		<?php echo shortenstring(stripslashes(esc_html($details['event_title'])),40);?>
	        	</span>
        </th>
        </tr>
        <tr>
        <td class="eventdescription" colspan="3"><?php echo stripslashes(nl2br(esc_html($details['event_desc'])));?></td>
        </tr>
        <tr>
        <td class="eventstart" width="33%"><?php echo strftime(DATE ." ". YEAR, $details['event_start']);?><br /><?php echo strftime(TIME, $details['event_start']);?></td>
        <td class="eventend" width="33%"><?php echo strftime(DATE ." ". YEAR, $details['event_end']);?><br /><?php echo strftime(TIME, $details['event_end']);?></td>
        <td></td>
        </tr>
        <tr>
        <td class="eventlocation">
			<?php if($details['event_loc'] != "" && $details['event_loc_url'] != "") {
				echo '<span class="dashicons dashicons-location"></span> <a class="eventlinks" href="'.esc_url( httpprefix($details['event_loc_url'])).'" target="_blank">'.shortenstring(esc_html( $details['event_loc'] ),20).'</a>'; 
			} elseif($details['event_loc'] != "" ) {
				echo '<span class="dashicons dashicons-location"></span> '. shortenstring(esc_html( $details['event_loc'] ),20);
			} elseif($details['event_loc_url'] != "" ) {
				echo '<span class="dashicons dashicons-location"></span> <a class="eventlinks" href="'.esc_url( httpprefix($details['event_loc_url'])).'">'.shortenstring(esc_url(httpprefix($details['event_loc_url'])),20).'</a>'; 				
			} else {
				echo '<span class="dashicons dashicons-location"></span>';
			}
			
			
			?></td>
            <td class="viewlabel">
			<?php if($details['event_label'] != "") { ?>
        		<span class="dashicons dashicons-tag"></span><span class="eventlabel" title="<?php _e('When including this label on your page when displaying events, only events with this label will be displayed',SE_TEXTDOMAIN);?>"><?php echo esc_html( $details['event_label'] );?></span>
			<?php } else { ?>
                    <span class="dashicons dashicons-tag"></span>
            <?php } ?>
            </td>
            <td>
			<?php if($details['event_url'] != "") {
				echo '<span class="dashicons dashicons-admin-links"></span> <a class="eventlinks" href="'.esc_url( httpprefix($details['event_url']) ).'" target="_blank">'.shortenstring(esc_url( httpprefix($details['event_url']) ),20).'</a>';
			} else {
				echo '<span class="dashicons dashicons-admin-links"></span>';
			} ?>
            </td>
        </tr>
        <tr><td colspan="3"><hr /></td></tr>
        
<?php }} // end foreach stored events display table ?>
	</tbody>
</table>

<?php if($i < 1) { ?>
	<p class="se-empty-list"><?php _e('Your past events list is empty.',SE_TEXTDOMAIN);?></p>
<?php }

// end check if anything was stored in the DB 
} else { ?>
	<table class="form-table">
		<tbody>
			<tr>
				<th colspan="2">
					<h2><?php _e('Stored events',SE_TEXTDOMAIN);?></h2>
				</th>
			</tr>
		</tbody>
	</table>
	<p class="se-empty-list se-center margin-bottom"><?php _e('You have no stored events.',SE_TEXTDOMAIN);?> <a href="?page=simple-events&tab=new_events" class="button-primary"><?php _e('Add event',SE_TEXTDOMAIN);?></a></p><hr>
<?php } ?>
</div> <!-- END existing-page -->
	<div class="se-nav-tab help-page <?php echo $active_tab == 'help' ? 'se-nav-tab-active' : ''; ?>">
        
    	<table class="form-table">
    		<tbody>
		    	<tr>
					<th colspan="2">
						<h2><?php _e('How to use',SE_TEXTDOMAIN);?></h2>
					</th>
				</tr>
				<tr>
					<td colspan="2">
						<p><?php _e("The Simple Events Calendar works with <a target='_blank' href='https://wordpress.com/support/shortcodes/'>shortcodes</a>. To explain it super simple, a shortcode is a bucket you can add to your posts and pages which you can fill from somewhere else. The bucket for the Simple Events Calendar is <code>[events]</code> and you can fill it from the settings.",SE_TEXTDOMAIN);?></p>
						<p><?php _e("Here's a list of the available shortcodes you can use for the Simple Events Calendar plugin:",SE_TEXTDOMAIN);?></p>
					</td>
				</tr>
				<tr>
					<th><code>[events]</code></th>
					<td><?php _e("Display all <i>upcoming</i> events ordered by date, showing the soonest event first.",SE_TEXTDOMAIN);?></td>
				</tr>
				<tr>
					<th><code>[events label=xx]</code></th>
					<td><?php _e("Display all <i>upcoming</i> events with label <i>xx</i> ordered by date, showing the soonest event first.",SE_TEXTDOMAIN);?></td>
				</tr>
				<tr>
					<th><code>[events age=all]</code></th>
					<td><?php _e("Display all <i>upcoming and past </i> events ordered by date, showing the soonest event first.",SE_TEXTDOMAIN);?></td>
				</tr>
				<tr>
					<th><code>[events age=expired]</code></th>
					<td><?php _e("Display all <i>past</i> events ordered by date, showing the soonest event first.",SE_TEXTDOMAIN);?></td>
				</tr>
				<tr>
					<th><code>[events limit=3]</code></th>
					<td><?php _e("Display the <i>next 3</i> events ordered by date, showing the soonest event first.",SE_TEXTDOMAIN);?></td>
				</tr>
				<tr>
					<th><code>[events time=no]</code></th>
					<td><?php _e("Display all <i>upcoming</i> events <i>showing only dates and no times</i> ordered by date, showing the soonest event first.",SE_TEXTDOMAIN);?></td>
				</tr>
				<tr>
					<th><code>[events time=start]</code></th>
					<td><?php _e("Display all <i>upcoming</i> events <i>only showing start times</i> ordered by date, showing the soonest event first.",SE_TEXTDOMAIN);?></td>
				</tr>
				<tr>
					<th><code>[events tweet=no]</code></th>
					<td><?php _e("Display all <i>upcoming</i> events <i>without the Twitter button</i> ordered by date, showing the soonest event first.",SE_TEXTDOMAIN);?></td>
				</tr>

				<tr>
					<td colspan="2"><h3><?php _e("All parameters can be mixed to get the desired output.",SE_TEXTDOMAIN);?></h3></td>
				</tr>

				<tr>
					<th><code>[events label=xx age=all]</code></th>
					<td><?php _e("Display all <i>upcoming and past</i> events that have the label<i>xx</i>.",SE_TEXTDOMAIN);?></td>
				</tr>

			</tbody>
		</table>
	</div>
</div>

<div class="se-center sans-serif italic se-large">
	<a href="https://wordpress.org/support/plugin/simple-events-calendar/reviews/#new-post"><?php _e("If you like the Simple Events Calendar, please rate it!", SE_TEXTDOMAIN);?></a> <?php _e("Thanks",SE_TEXTDOMAIN);?> <span class="dashicons dashicons-heart"></span>
</div>
<?php } // End of simple_events_options function

// Add the shortcode for displaying the event on pages
function displayevents( $atts ) {
	setlocale(LC_ALL, get_locale());
	
	// VARIATIONS: EXPIRED  /  ALL  /  UPCOMING
	isset($atts['age']) ? $age = $atts['age'] : $age = NULL;
	$range = age($age);
	
	// See if Label filter is used
	$label = NULL;
	if(isset($atts['label'])) $label = strtolower(esc_html($atts['label']));
	
	// See if a limit has been set
	if( isset($atts['limit']) && $atts['limit'] > 0 ) {
		$limit = "LIMIT 0, " . $atts['limit'];
	} else {
		$limit = "";
	}
	
	$allevents = array();
	$allevents = eventquery($label,$age,$range,$limit);
	
	$i = 1;
	foreach ($allevents as $event) {
		// decide what needs to be mentioned in the start date
		if(date('Y',$event['event_start']) == date('Y',time())) {
			$eventtime = strftime(DATE .' '. TIME,$event['event_start']);
			$shorttime = strftime(DATE,$event['event_start']);
		} else {
			$eventtime = strftime(DATE . ' ' . YEAR . ' ' . TIME,$event['event_start']);
			$shorttime = strftime(DATE,$event['event_start']);
		}
		
		// decide what needs to be mentioned in the end date
		if(date('Y',$event['event_end']) == date('Y',$event['event_start'])) { $end_year = "same"; } else { $end_year = "diff";}
		if(date('dmy',$event['event_end']) == date('dmy',$event['event_start'])) { $end_date = "same"; } else { $end_date = "diff";}		
		
		// Check how to open the links
		if(get_option('SE_links') == "none") { $targetLoc = "_self"; $targetInf = "_self"; }
		elseif(get_option('SE_links') == "both") { $targetLoc = "_blank"; $targetInf = "_blank"; }
		elseif(get_option('SE_links') == "location") { $targetLoc = "_blank"; $targetInf = "_self"; }
		elseif(get_option('SE_links') == "information") { $targetLoc = "_self"; $targetInf = "_blank"; }
		
		// decide if the event location should be a URL
		$evt_loc = NULL;
		if($event['event_loc'] != "" && $event['event_loc_url'] != "" ) {
			$evt_loc = '<div><strong class="event-label">'.__('Where:',SE_TEXTDOMAIN).' </strong><a class="location" href="'.esc_url(httpprefix($event['event_loc_url'])).'" target="'.$targetLoc.'">'.esc_html($event['event_loc']).'</a></div>';
		} elseif($event['event_loc'] != "") {
			$evt_loc = '<div><strong class="event-label">'.__('Where:',SE_TEXTDOMAIN).' </strong>'.esc_html($event['event_loc']).'</div>';
		}
		
		// Check if more info link should be displayed
		$evt_url = NULL;
		if(isset($atts['moreinfo']) && $atts['moreinfo'] == "no" ) {
			$evt_url = "";
		} elseif($event['event_url']  != "" ) {
			$evt_url = '<div><strong class="event-label">'.__('More info:',SE_TEXTDOMAIN).' </strong> <span class="info-link"><a class="url" href="'.esc_url(httpprefix($event['event_url'])).'" target="'.$targetInf.'">'.__('Click here',SE_TEXTDOMAIN).'</a></span></div>';		
		}
		
		// Check if tweet button should be displayed
		if(isset($atts['tweet']) && $atts['tweet'] == "no" || get_option('SE_twitter') == "no") {
			$evt_twt = "";
		} else {
			$evt_twt = '<div><a href="http://twitter.com/share" class="twitter-share-button" data-url="'.esc_url(httpprefix($event['event_url'])).'" data-text="'.stripslashes(esc_html($event['event_title'])).' '.$shorttime.' in '. esc_html($event['event_loc']) .'" data-count="none" data-via="wpsec">Tweet</a></div>';
		}
		
		// Check if and how the times should be displayed
		$tz = get_option('SE_timezone');
		$start_time = '<strong class="event-label">'.__('When:',SE_TEXTDOMAIN).' </strong> <abbr class="dtstart" title="'.date('Y-m-d',$event['event_start']).'T'.date('H:i',$event['event_start']).$tz.'">'.$eventtime.'</abbr>';
		$start_notime = '<strong class="event-label">'.__('When:',SE_TEXTDOMAIN).' </strong><abbr class="dtstart" title="'.date('Y-m-d',$event['event_start']).'">'.$shorttime.'</abbr>';
		if(isset($atts['time']) && $atts['time'] == "no") {
			if($end_date == "same") {
				$evt_time = $start_notime;
			} elseif($end_year == "same") {
				$evt_time = $start_notime . '<abbr class="dtend" title="'.date('Y-m-d',$event['event_end']).'"> - '. strftime(DATE,$event['event_end']) .'</abbr>';
			} else {
				$evt_time = $start_notime . '<abbr class="dtend" title="'.date('Y-m-d',$event['event_end']).'"> - '. strftime(DATE . " " . YEAR,$event['event_end']) .'</abbr>';
			}
		} elseif( isset($atts['time']) && $atts['time'] == "start") {
			$evt_time = $start_time;
		} elseif($end_date == "same") {
			$evt_time = $start_time . ' - <abbr class="dtend" title="'.date('Y-m-d',$event['event_end']).'T'.date('H:i',$event['event_end']).$tz.'">'. strftime( TIME ,$event['event_end']) .'</abbr>';
		} elseif($end_year == "same") {
			$evt_time = $start_time . ' - <abbr class="dtend" title="'.date('Y-m-d',$event['event_end']).'T'.date('H:i',$event['event_end']).$tz.'">'. strftime(DATE ." ".TIME,$event['event_end']) .'</abbr>';
		} else {
			$evt_time = $start_time . ' - <abbr class="dtend" title="'.date('Y-m-d',$event['event_end']).'T'.date('H:i',$event['event_end']).$tz.'">'. strftime(DATE ." ".YEAR." ".TIME,$event['event_end']) .'</abbr>';
		}
		
		if($i % 2) $evt_no = "odd"; else $evt_no = "even"; // adding odd and even classes for styling options
		$the_events[] =
		'<div class="vevent event-item '.$evt_no.' event-'.$i.'">'.
		'<h3 class="summary">'.stripslashes($event['event_title']).'</h3>
		<p class="description">'.stripslashes(nl2br($event['event_desc'])).'</p>
		<div class="event-time">'.$evt_time.'</div>'.
		$evt_loc.
		$evt_url.
		$evt_twt.
		'</div>';
		$i++;
	} // end foreach ($allevents as $event)
	if(count($allevents) > 0) $items = '<style>.vevent abbr{text-decoration:none}</style>'.implode($the_events); else $items = "<p>".__("There are no events to display",SE_TEXTDOMAIN)."</p>";
	return($items);
}
add_shortcode( 'events', 'displayevents' );

function age($age) {
	if($age == "expired") {
		$range = "event_end <= " . time();
	} elseif($age == "all") {
		$range = "event_end > 315532800"; // timestamp for jan 1st 1980 - assuming no event will be creted before that date
	} else {
		$range = "event end > " . time();
	}
	return $range;
}


function eventquery($label,$age,$range,$limit) {
	global $wpdb;
	$table_name = $wpdb->prefix . "simple_events";
	if(!is_null($age) && !is_null($label)) {
		$conditions = "WHERE event_label = '$label' AND $range ORDER BY event_start $limit";
	} elseif(!is_null($age)) {
		$conditions = "WHERE $range ORDER BY event_start $limit";
	} elseif(!is_null($label)) {
		$currentTime = time();
		$conditions = "WHERE event_label = '$label' AND event_end >= $currentTime ORDER BY event_start $limit";
	} else {
		$currentTime = time();
		$conditions = "WHERE event_end >= $currentTime ORDER BY event_start $limit";
	}
	
	return $wpdb->get_results(" SELECT * FROM $table_name " . $conditions , "ARRAY_A");

}

// function to check url and set https prefix if needed
function httpprefix($httpsurl) {
	if(substr(strtolower($httpsurl),0,4) == "http") {
		$fullurl = $httpsurl;
	} else {
		$fullurl = "https://".$httpsurl;
	}
	return $fullurl;
}

// function to shorten long strings to $length characters and to prepend ... if it was actually shortend
function shortenstring() { // Needs 2 arguments: $strng,$length
	$args = func_get_args();
	if(func_num_args() == 2) {
		//$args = func_get_args();
		$longer = (strlen($args[0]) > $args[1]) ? '...' : '';
		return substr($args[0],0,$args[1]).$longer;
	}
}

function sec_head() {
	global $sec_options;
	echo "\n<!-- Simple Events Calendar ".SE_VERSION." by Jerry G. Rietveld (www.jgrietveld.com) -->";
	echo "\n<link rel=\"profile\" href=\"http://microformats.org/profile/hcalendar\" />\n\n";
}
add_action('wp_head', 'sec_head');

function SE_public_scripts() {	
	global $sec_options;
	if(!is_admin() && get_option('SE_twitter') == "yes") wp_enqueue_script('tweetevents', 'http://platform.twitter.com/widgets.js', '', SE_VERSION , true);
}
add_action( 'wp_enqueue_scripts', 'SE_public_scripts' );