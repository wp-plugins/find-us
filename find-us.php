<?php
/*
Plugin Name: Find Us
Plugin URI: http://wordpress.designpraxis.at
Description: Integrates Google Maps with your WordPress website
Version: 1.4
Author: Roland Rust
Author URI: http://wordpress.designpraxis.at

Phoolge Class by Justin Johnson <justinjohnson@system7designs.com>
Get your Google API key: http://www.google.com/apis/maps/signup.html
*/
/* 
Changelog:

Changes in 1.4:
- corrections to produce xhtml-strict code

Changes in 1.3:
- options are deleted on module deactivation
- switch between EasyMode and AdvacedMode
- additional AdvancedMode options: 
	- set width and height
	- change the control type (large or small)
	- show map/satellite/hyprid modes
- AdvancedMode display style ids for css modification.

Changes in 1.2:
- google map API only loads when [findusmap] is detected in the post(s)

Changes in 1.1:
- more modification to the customized phoogle class
- Google Maps API Query changed to "from: xy to: yz" (retruns more often a result)

*/

add_action('init', 'dprx_find_us_init_locale');
function dprx_find_us_init_locale() {
	$locale = get_locale();
	$mofile = dirname(__FILE__) . "/locale/".$locale.".mo";
	load_textdomain('dprx_find_us', $mofile);
}

/* Admin */
if (eregi("find-us",$_REQUEST['page'])) {
	$GLOBALS['dprx_load_map'] = 1;
	add_action('admin_head', 'dprx_find_us_init',21);
	add_action('admin_head', 'dprx_find_us_js',22);
}
add_action('admin_menu', 'dprx_find_us_add_admin_pages');


/* WP Loop */
add_action('wp_head', 'dprx_check_map_start',20);
function dprx_check_map_start() {
	$sql = $GLOBALS['wp_the_query']->request;
	$res = $GLOBALS['wpdb']->get_results($sql, ARRAY_A);
	foreach($res as $r) {
		preg_match("/(.*?)\[findusmap(.*)(\])(.*?)/", $r['post_content'], $match);
		if ($match) {
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
	
	preg_match("/(.*?)\[findusmap(.*)(\])(.*?)/", $data, $match);
	if ($match) {
		$strtoreplace = $match[0];
		$params = $match[2];
		$params = explode("#",$params);
		foreach($params as $p) {
			$kv=explode(":",$p);
			if($kv[0] == "width") {
				$width = $kv[1];
			}
			if($kv[0] == "height") {
				$height = $kv[1];
			}
			if($kv[0] == "ctype") {
				$ctype = $kv[1];
			}
			if($kv[0] == "endlocation") {
				$endlocation = $kv[1];
			}
			if($kv[0] == "mtypes") {
				$mtypes = $kv[1];
			}
		}
		$replace = "";
		ob_start();
		dprx_display_find_us($width,$height,$ctype,$endlocation,$mtypes);
		$replace = ob_get_contents();
		ob_end_clean();
		return str_replace($strtoreplace, $replace, $data);
	} else {
		return $data;
	}
}

function dprx_find_us_init() {
	if(empty($GLOBALS['dprx_load_map'])) {
		return;
	}
	
	if (!empty($_REQUEST['dprx_find_us_mode'])) {
		if (get_option("dprx_find_us_mode") == 1) {
			update_option("dprx_find_us_mode",0);
		} else {
			update_option("dprx_find_us_mode",1);
		}
	}
	
	if (!empty($_REQUEST['dprx_find_us_setoptions'])) {
		update_option("dprx_find_us_mtypes",$_POST['dprx_find_us_mtypes']);
	}
	
	if (!empty($_POST['dprx_find_us_apikey'])) {
		update_option("dprx_find_us_apikey",$_POST['dprx_find_us_apikey']);
	}
	
	if (!empty($_POST['dprx_find_us_width'])) {
		update_option("dprx_find_us_width",$_POST['dprx_find_us_width']);
	}
	
	if (!empty($_POST['dprx_find_us_height'])) {
		update_option("dprx_find_us_height",$_POST['dprx_find_us_height']);
	}
	if (!empty($_POST['dprx_find_us_ctype'])) {
		update_option("dprx_find_us_ctype",$_POST['dprx_find_us_ctype']);
	}
	
	$googlemapapi = get_option("dprx_find_us_apikey");
	if (!empty($googlemapapi)) {
		include(dirname(__FILE__)."/phoogle.php");
		include(dirname(__FILE__)."/find_us_mapclass.php");
		$GLOBALS['dprx_find_us_map'] = new dprx_find_us_Map();
	}
	if (!empty($_POST['dprx_find_us_location'])) {
		update_option("dprx_find_us_location",$_POST['dprx_find_us_location']);
		$dprx_find_us_location = $_POST['dprx_find_us_location'];
		if (!empty($dprx_find_us_location)) {
			$GLOBALS['dprx_find_us_map']->addAddress($dprx_find_us_location);
			if (!get_option("dprx_find_us_location_end_lat")) { update_option("dprx_find_us_location_end_lat",$GLOBALS['dprx_find_us_map']->dprx_find_us_lat()); }
			if (!get_option("dprx_find_us_location_end_lon")) { update_option("dprx_find_us_location_end_lon",$GLOBALS['dprx_find_us_map']->dprx_find_us_lon()); }
		}
	}
	if (!empty($googlemapapi)) {
	if (empty($GLOBALS['dprx_find_us_map']->showType)) {  $GLOBALS['dprx_find_us_map']->showType = false; }
	$GLOBALS['dprx_find_us_map']->setAPIKey(get_option("dprx_find_us_apikey"));
	}
}


add_action('activate_find-us/find-us.php', 'dprx_find_us_activate');
function dprx_find_us_activate() {
	update_option("dprx_find_us_width","320");
	update_option("dprx_find_us_height","200");
	update_option("dprx_find_us_ctype","small");
	update_option("dprx_find_us_mode",1);
	update_option("dprx_find_us_mtypes",0);
}

/* options are deleted in case of plugin deactivation */
add_action('deactivate_find-us/find-us.php', 'dprx_find_us_deactivate');
function dprx_find_us_deactivate() {
	delete_option("dprx_find_us_location");
	delete_option("dprx_find_us_mode");
	delete_option("dprx_find_us_apikey");
	delete_option("dprx_find_us_width");
	delete_option("dprx_find_us_height");
	delete_option("dprx_find_us_ctype");
	delete_option("dprx_find_us_mtypes");
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
function dprx_display_find_us($width="",$height="",$ctype="",$endlocation="",$mtypes="") {
	?>
	<div id="dprx_find_us_map_box">
		<div style="float:left">
		<?php
		if (empty($endlocation)) { $endlocation = get_option("dprx_find_us_location"); }
		$GLOBALS['dprx_find_us_map']->addaddress("".$endlocation."");
		if (empty($width)) { $width = get_option("dprx_find_us_width"); }
		$GLOBALS['dprx_find_us_map']->mapWidth = $width;
		if (empty($height)) { $height = get_option("dprx_find_us_height"); }
		$GLOBALS['dprx_find_us_map']->mapHeight = $height;
		if (empty($ctype)) { $ctype = get_option("dprx_find_us_ctype"); }
		$GLOBALS['dprx_find_us_map']->controlType = $ctype;
		if (empty($mtypes)) { $mtypes = get_option("dprx_find_us_mtypes"); }
		if($mtypes == 1) {
			$GLOBALS['dprx_find_us_map']->showType = true;
		} else {
			$GLOBALS['dprx_find_us_map']->showType = false;
		}
		
		if (!empty($_REQUEST['dprx_find_us_location_start'])) {
			$GLOBALS['dprx_find_us_map']->addAddress($_REQUEST['dprx_find_us_location_start']);
			$lat = $GLOBALS['dprx_find_us_map']->dprx_find_us_lat(1);
			$lon = $GLOBALS['dprx_find_us_map']->dprx_find_us_lon(1);
			$GLOBALS['dprx_find_us_map']->centerMap($lat,$lon);
		}
		if (count($GLOBALS['dprx_find_us_map']->validPoints) > 0) {
			$GLOBALS['dprx_find_us_map']->showMap();
		} else {
			_e('No valid points found. Please try again', 'dprx_find_us');
		}
		?>
		</div>
		
		<div id="dprx_find_us_map_form" style="float:left;padding-left: 5px;padding-top: 5px;">
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
			<input type="text" style="width:200px;" id="dprx_form_location" name="dprx_find_us_location_start" value="<?php echo $_REQUEST['dprx_find_us_location_start']; ?>" /><br />
			<input type="submit" id="dprx_form_setlocation" name="setlocation" value="<?php _e('Find your way to us &raquo;','dprx_find_us'); ?>" /><br />	
			powered by <a href="http://wordpress.designpraxis.at">designpraxis</a>
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
	if (get_option("dprx_find_us_mode") == 1) {
		?>
			<p>
			<?php _e("Looking for a less komplex Solution? Please swith to ","dprx_find_us"); ?>
			<br /><a href="admin.php?page=<?php echo $_REQUEST['page']; ?>&dprx_find_us_mode=1"><?php _e("EasyMode","dprx_find_us"); ?> &raquo;</a>
			<br /><br />
			</p>
		<?php
	} else {
		?>
			<p>
			<?php _e("Looking for more options? Please swith to ","dprx_find_us"); ?>
			<br /><a href="admin.php?page=<?php echo $_REQUEST['page']; ?>&dprx_find_us_mode=1"><?php _e("AdvancedMode","dprx_find_us"); ?> &raquo;</a>
			<br /><br />
			</p>
		<?php
	}
	?>
	<!-- Basic Options -->
	<form method="post" action="<?php bloginfo("wpurl"); ?>/wp-admin/admin.php?page=find-us/find-us.php">
	<fieldset name="dprx_find_us_basic_options"  class="options">
		<p><b><?php _e('Enter your Google Maps API Key', 'dprx_find_us') ?></b></p>
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
	</fieldset>
	</form>
	<?php
	if (get_option("dprx_find_us_apikey")) {
		$dprx_find_us_location = get_option("dprx_find_us_location");
	?>
	<!-- Basic Options -->
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<fieldset name="dprx_find_us_basic_options"  class="options">
		<p><b><?php _e('Where can people find you?', 'dprx_find_us') ?></b></p>
		<?php _e('Just type in your address. In case you cannot find it here, try an address near yours and refine location search on the map.', 'dprx_find_us') ?>
		<input type="text" style="width:400px;" id="dprx_find_us_location" name="dprx_find_us_location" value="<?php echo get_option("dprx_find_us_location"); ?>" />
		<p class="submit">
		<input type="submit" id="dprx_find_us_setoptions" name="dprx_find_us_setoptions" Value="<?php _e('set your location','dprx_find_us'); ?>" />	
		</p>
		
		<?php
		if (get_option("dprx_find_us_mode") == 1) {
		?>
		<p>
		<?php _e('Map width:') ?> <input type="text" style="width:100px;" id="dprx_find_us_width" name="dprx_find_us_width" value="<?php echo get_option("dprx_find_us_width"); ?>" /> px
		</p>
		<p>
		<?php _e('Map height:') ?> <input type="text" style="width:100px;" id="dprx_find_us_height" name="dprx_find_us_height" value="<?php echo get_option("dprx_find_us_height"); ?>" /> px
		</p>
		<p>
		<?php _e('Control Type:') ?> <select name="dprx_find_us_ctype" id="dprx_find_us_ctype">
		<option value="large"<?php if (get_option("dprx_find_us_ctype") == "large") { echo " selected"; } ?>><?php _e('large'); ?></option>
		<option value="small"<?php if (get_option("dprx_find_us_ctype") == "small") { echo " selected"; } ?>><?php _e('small'); ?></option>
		</select>
		</p>
		<p>
		<?php _e('Show Map Types:') ?> <input type="checkbox" id="dprx_find_us_mtypes" name="dprx_find_us_mtypes" value="1"
		<?php $mtypes = get_option("dprx_find_us_mtypes");
		if ($mtypes == 1) {
			echo " checked";
		}?>/>
		</p>
		<p class="submit">
		<input type="submit" id="dprx_find_us_setoptions" name="dprx_find_us_setoptions" Value="<?php _e('set default options','dprx_find_us'); ?>" />	
		</p>
		<?php
		}
		?>
		<p><b><?php _e('To display the "Find Us"-Map, use the following code:') ?></b></p>
		
		<?php
		if (get_option("dprx_find_us_mode") == 1) {
			$tag = "[findusmap";
			$endlocation = get_option("dprx_find_us_location");
			if (!empty($endlocation)) {
				$tag .= "#endlocation:".get_option("dprx_find_us_location");
			}
			$width = get_option("dprx_find_us_width");
			if (!empty($width)) {
				$tag .= "#width:".get_option("dprx_find_us_width");
			}
			$height = get_option("dprx_find_us_height");
			if (!empty($height)) {
				$tag .= "#height:".get_option("dprx_find_us_height");
			}
			$ctype = get_option("dprx_find_us_ctype");
			if (!empty($ctype)) {
				$tag .= "#ctype:".get_option("dprx_find_us_ctype");
			}
			$mtypes = get_option("dprx_find_us_mtypes");
			if (!empty($mtypes)) {
				$tag .= "#mtypes:".get_option("dprx_find_us_mtypes");
			}
			$tag .="]";
			echo $tag;
			?>
			<p>
			<?php _e('This is the same as using [findusmap], since without parameters specified, defaults are used','dprx_find_us'); ?>
			<br />
			<?php _e('Change the parameters within the tag to use custom settings for a page or post.','dprx_find_us'); ?>
			</p>
			<p>
			<b><?php _e('You can use following ids to style the map display in css','dprx_find_us'); ?></b>
			</p>
			<p>
			#dprx_find_us_map_box (<?php _e('for the div around map and form','dprx_find_us'); ?>)<br />
			#dprx_find_us_map_form (<?php _e('for the floating div around the form','dprx_find_us'); ?>)<br />
			#dprx_form_location (<?php _e('for the input field','dprx_find_us'); ?>)<br />
			#dprx_form_setlocation (<?php _e('for the submit button','dprx_find_us'); ?>)
			</p>
			<?php
		} else {
			$tag = "[findusmap]";
			echo $tag;
		}
		?>
		<p><b><?php _e('This is what your "Find Us"-Map will look like:') ?></b></p>
		<?php 
		if (get_option("dprx_find_us_location")) {
			echo dprx_find_us_filter_content(" ".$tag." ");
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