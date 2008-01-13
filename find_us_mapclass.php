<?php

class dprx_find_us_Map extends PhoogleMap {
	
	    var $zoomLevel = 5;
	    
	function dprx_find_us_directions_js(){
		echo "<script type=\"text/javascript\">\n
		map.addOverlay(new GPolyline());
		</script>\n";
	}
	
	function dprx_find_us_coordinates(){
	    $total = count($this->validPoints);
	    //print_r($this->validPoints);
		echo "<ul id=\"".$css_id."\">\n";
	      for ($lst=0; $lst<$total; $lst++){
		      $coords = explode(",",$this->validPoints[$lst]['lat']);
		echo "<li>".$coords[0]."° lat</li>\n";
		echo "<li>".$coords[1]."° long</li>\n";
		}
		echo "</ul>\n";
	}
	function dprx_find_us_lat($arrnum=0) {
	    $total = count($this->validPoints);
	      for ($lst=0; $lst<$total; $lst++){
		      $coords = explode(",",$this->validPoints[$arrnum]['lat']);
		      return $coords[0];
		}
	}
	function dprx_find_us_lon($arrnum=0){
	    $total = count($this->validPoints);
	      for ($lst=0; $lst<$total; $lst++){
		      $coords = explode(",",$this->validPoints[$arrnum]['lat']);
		      return $coords[1];
		}
	}
	

	function dprx_find_us_distancefrom($lat2, $lon2, $unit = "k") { 
	  $theta = $this->dprx_find_us_long() - $lon2; 
	  $dist = sin(deg2rad($this->dprx_find_us_lat())) * sin(deg2rad($lat2)) +  cos(deg2rad($this->dprx_find_us_lat())) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
	  $dist = acos($dist); 
	  $dist = rad2deg($dist); 
	  $miles = $dist * 60 * 1.1515;
	  $unit = strtoupper($unit);
	
	  if ($unit == "K") {
	    return ($miles * 1.609344); 
	  } else if ($unit == "N") {
	      return ($miles * 0.8684);
	    } else {
		return $miles;
	      }
	}
}
?>
