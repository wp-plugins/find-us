<?php
/*
Plugin Name: Find Us
Plugin URI: http://wordpress.designpraxis.at
Description: Integrates Google Maps with your WordPress website
Version: 1.2
Author: Roland Rust
Author URI: http://wordpress.designpraxis.at

Phoolge Class by Justin Johnson <justinjohnson@system7designs.com>
Get your Google API key: http://www.google.com/apis/maps/signup.html
*/
/* 
Changelog:

Changes in 1.2:
- google map API only loads when [findusmap] is detected in the post(s)

Changes in 1.1:
- more modification to the customized phoogle class
- Google Maps API Query changed to "from: xy to: yz" (retruns more often a result)

*/

/* Admin */
if (eregi("find-us",$_REQUEST['page'])) {
add_action('admin_head', 'dprx_find_us_js');
}
add_action('admin_menu', 'dprx_find_us_add_admin_pages');


/* WP Loop */
add_action('wp_head', 'dprx_check_map_start',20);
function dprx_check_map_start() {
	$sql = $GLOBALS['wp_the_query']->request;
	$res = $GLOBALS['wpdb']->get_results($sql, ARRAY_A);
	foreach($res as $r) {
		if (strpos($r['post_content'], "[findusmap]")) {
			$GLOBALS['dprx_load_map'] = 1;
		}
	}
}


add_action('wp_head', 'dprx_find_us_init',21);
add_action('wp_head', 'dprx_find_us_js',22);
add_action('the_content', 'dprx_find_us_filter_content');


function dprx_find_us_filter_content($data) {
	if(empty($GLOBALS['dprx_load_map'])) {
		return $data;
	}
	if (strpos ( $data, "[findusmap]" ) ) {
		$replace = "";
		ob_start();
		dprx_display_find_us();
		$replace = ob_get_contents();
		ob_end_clean();
		return str_replace("[findusmap]", $replace, $data);
	} else {
		return $data;
	}
}

function dprx_find_us_init() {
	if(empty($GLOBALS['dprx_load_map'])) {
		return;
	}
	
	if (!empty($_POST['dprx_find_us_apikey'])) {
		update_option("dprx_find_us_apikey",$_POST['dprx_find_us_apikey']);
	}
	
	$googlemapapi = get_option("dprx_find_us_apikey");
	if (!empty($googlemapapi)) {
		include(dirname(__FILE__)."/phoogle.php");
		include(dirname(__FILE__)."/find_us_mapclass.php");
		$GLOBALS['dprx_find_us_map'] = new dprx_find_us_Map();
	}
	if (!empty($_POST['dprx_location'])) {
		update_option("dprx_location",$_POST['dprx_location']);
		$dprx_location = $_POST['dprx_location'];
		if (!empty($dprx_location)) {
			$GLOBALS['dprx_find_us_map']->addAddress($dprx_location);
			if (!get_option("dprx_location_end_lat")) { update_option("dprx_location_end_lat",$GLOBALS['dprx_find_us_map']->dprx_find_us_lat()); }
			if (!get_option("dprx_location_end_lon")) { update_option("dprx_location_end_lon",$GLOBALS['dprx_find_us_map']->dprx_find_us_lon()); }
		}
	}
	if (!empty($googlemapapi)) {
	$GLOBALS['dprx_find_us_map']->mapWidth = get_option("dprx_find_us_width");
	if (empty($GLOBALS['dprx_find_us_map']->mapWidth)) {  $GLOBALS['dprx_find_us_map']->mapWidth = '350'; }
	$GLOBALS['dprx_find_us_map']->mapHeight = get_option("dprx_find_us_height");
	if (empty($GLOBALS['dprx_find_us_map']->mapHeight)) {  $GLOBALS['dprx_find_us_map']->mapHeight = '250'; }
	$GLOBALS['dprx_find_us_map']->controlType = get_option("dprx_find_us_sontrolType");
	if (empty($GLOBALS['dprx_find_us_map']->controlType)) {  $GLOBALS['dprx_find_us_map']->controlType = 'small'; }
	$GLOBALS['dprx_find_us_map']->showType = get_option("dprx_find_us_sontrolType");
	if (empty($GLOBALS['dprx_find_us_map']->showType)) {  $GLOBALS['dprx_find_us_map']->showType = false; }
	$GLOBALS['dprx_find_us_map']->setAPIKey(get_option("dprx_find_us_apikey"));
	}
}

function dprx_find_us_add_admin_pages() {
	add_options_page('Find Us', 'Find Us', 10, __FILE__, 'dprx_find_us_options_page');
}

function dprx_find_us_js() {
	if(empty($GLOBALS['dprx_load_map'])) {
		return;
	}
	$googlemapapi = get_option("dprx_find_us_apikey");
	if (!empty($googlemapapi)) {
	$GLOBALS['dprx_find_us_map']->printGoogleJS();
	}
}
function dprx_display_find_us() {
	$dprx_location = get_option("dprx_location");
	$GLOBALS['dprx_find_us_map']->addaddress($dprx_location);
	?>
	<div style="padding:10px;">
		<div style="float:left">
		<?php
		if (!empty($_REQUEST['dprx_location_start'])) {
			$GLOBALS['dprx_find_us_map']->addAddress($_REQUEST['dprx_location_start']);
			$lat = $GLOBALS['dprx_find_us_map']->dprx_find_us_lat(1);
			$lon = $GLOBALS['dprx_find_us_map']->dprx_find_us_lon(1);
			$GLOBALS['dprx_find_us_map']->centerMap($lat,$lon);
		}
		if (count($GLOBALS['dprx_find_us_map']->validPoints) > 0) {
			//$GLOBALS['dprx_find_us_map']->dprx_find_us_directions_js();
			$GLOBALS['dprx_find_us_map']->showMap();
		} else {
			_e('No valid points found. Please try again', 'dprx_find_us');
		}
		?>
		</div>
		<div style="float:left;padding:10px;">
		
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
			<input type="text" style="width:200px;" id="dprx_location" name="dprx_location_start" value="<?php echo $_REQUEST['dprx_location_start']; ?>" /><br />
			<input type="submit" id="setlocation" name="setlocation" Value="<?php _e('Find your way to us &raquo;','dprx_find_us'); ?>" />	
			</p>
			<p>
			powered by <a href="http://wordpress.designpraxis.at">designpraxis</a>
			</p>
		</form>
		</div>
		<div id="dprx_directions_div" style="clear:both;">
		</div>
	</div>
	<?php
}

function dprx_find_us_options_page() {
	
	?>
	<div class=wrap>
		<h2><?php _e('Find Us') ?></h2>
	<?php
	?>
	<!-- Basic Options -->
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<fieldset name="dprx_find_us_basic_options"  class="options">
		<legend><?php _e('Enter your Google Maps API Key', 'dprx_find_us') ?></legend>
			<label for="dprx_find_us_apikey">
				<input type="text" style="width:100%;" id="dprx_find_us_apikey" name="dprx_find_us_apikey" value="<?php echo get_option("dprx_find_us_apikey"); ?>" />
					<p class="submit">
					<input type="submit" id="setapikey" name="setapikey" Value="<?php _e('Set Google Maps API Key','dprx_find_us'); ?>" />	
					</p>
				<?php
				$googlemapapi = get_option("dprx_find_us_apikey");
				if (empty($googlemapapi)) {
				?>
					<a href="http://www.google.com/apis/maps/signup.html"><?php _e('Get your Google Maps API key first!', 'dprx_find_us') ?></a>
				<?php
				}
				?>
			</label>
	</fieldset>
	</form>
	<?php
	if (get_option("dprx_find_us_apikey")) {
		$dprx_location = get_option("dprx_location");
	?>
	<!-- Basic Options -->
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<fieldset name="dprx_find_us_basic_options"  class="options">
		<legend><?php _e('Where can people find you?', 'dprx_find_us') ?></legend>
		<?php _e('Just type in your address. In case you cannot find it here, try an address near yours and refine location search on the map.', 'dprx_find_us') ?>
		<input type="text" style="width:400px;" id="dprx_location" name="dprx_location" value="<?php echo get_option("dprx_location"); ?>" />
		<p class="submit">
		<input type="submit" id="setlocation" name="setlocation" Value="<?php _e('set your location','dprx_find_us'); ?>" />	
		</p>
		
		<p><?php _e('To display the "Find Us"-Map, use the following code:') ?></p>
		<p>[findusmap]</p>
		
		<p><?php _e('This is what your "Find Us"-Map will look like:') ?></p>
		<?php 
		if (get_option("dprx_location")) {
		dprx_display_find_us();
		}
		?>
	</fieldset>
	<?php
	}
	?>
	</form>
	</div>
	<?php
}
?>