<?php

/**
* Phoogle Maps 2.0 | Uses Google Maps API to create customizable maps
* that can be embedded on your website
*    Copyright (C) 2005  Justin Johnson
*
*    This program is free software; you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation; either version 2 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program; if not, write to the Free Software
*    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA 
*
*
* Phoogle Maps 2.0
* Uses Google Maps Mapping API to create customizable
* Google Maps that can be embedded on your website
*
* @class        Phoogle Maps 2.0
* @author       Justin Johnson <justinjohnson@system7designs.com>
* @copyright    2005 system7designs
* @modified    2007 wordpress@designpraxis.at
*/




class PhoogleMap{
/**
* validPoints : array
* Holds addresses and HTML Messages for points that are valid (ie: have longitude and latitutde)
*/
    var $validPoints = array();
/**
* invalidPoints : array
* Holds addresses and HTML Messages for points that are invalid (ie: don't have longitude and latitutde)
*/
    var $invalidPoints = array();
/**
* mapWidth
* width of the Google Map, in pixels
*/
    var $mapWidth = 300;
/**
* mapHeight
* height of the Google Map, in pixels
*/
    var $mapHeight = 300;

/**
* apiKey
* Google API Key
*/
    var $apiKey = "";

/**
* showControl
* True/False whether to show map controls or not
*/
    var $showControl = true;
	
/**
* showType
* True/False whether to show map type controls or not
*/
    var $showType = true;
/**
* controlType
* string: can be 'small' or 'large'
* display's either the large or the small controls on the map, small by default
*/

    var $controlType = 'small';
    
/**
* zoomLevel
* int: 0-17
* set's the initial zoom level of the map
*/

    var $zoomLevel = 4;




/**
* @function     addGeoPoint
* @description  Add's an address to be displayed on the Google Map using latitude/longitude
*               early version of this function, considered experimental
*/

function addGeoPoint($lat,$long,$infoHTML){
    $pointer = count($this->validPoints);
        $this->validPoints[$pointer]['lat'] = $lat;
        $this->validPoints[$pointer]['long'] = $long;
        $this->validPoints[$pointer]['htmlMessage'] = $infoHTML;
    }
    
/**
* @function     centerMap
* @description  center's Google Map on a specific point
*               (thus eliminating the need for two different show methods from version 1.0)
*/

function centerMap($lat,$long){
    $this->centerMap = "map.setCenter(new GLatLng(".$long.",".$lat."), ".$this->zoomLevel.");\n";
}
    
    
/**
* @function     addAddress
* @param        $address:string
* @returns      Boolean True:False (True if address has long/lat, false if it doesn't)
* @description  Add's an address to be displayed on the Google Map
*               (thus eliminating the need for two different show methods from version 1.0)
*/
	function addAddress($address,$htmlMessage=null){
	 if (!is_string($address)){
		die("All Addresses must be passed as a string");
	  }
		$apiURL = "http://maps.google.com/maps/geo?&output=xml&key=".$this->apiKey."&q=";
		$addressData = file_get_contents($apiURL.urlencode($address));
		
		$results = $this->xml2array($addressData);
		if (empty($results['kml'][Response]['Placemark']['Point']['coordinates'])){
			$pointer = count($this->invalidPoints);
			$this->invalidPoints[$pointer]['lat']= $results['kml'][Response]['Placemark']['Point']['coordinates'][0];
			$this->invalidPoints[$pointer]['long']= $results['kml'][Response]['Placemark']['Point']['coordinates'][1];
			$this->invalidPoints[$pointer]['passedAddress'] = $address;
			$this->invalidPoints[$pointer]['htmlMessage'] = $htmlMessage;
			return false;
		  } else {
			$pointer = count($this->validPoints);
			$this->validPoints[$pointer]['lat']= $results['kml'][Response]['Placemark']['Point']['coordinates'];
			$this->validPoints[$pointer]['long']= $results['kml'][Response]['Placemark']['Point']['coordinates'];
			$this->validPoints[$pointer]['passedAddress'] = $address;
			$this->validPoints[$pointer]['htmlMessage'] = $htmlMessage;
			return true;
		}
	
	
	}
/**
* @function     showValidPoints
* @param        $displayType:string
* @param        $css_id:string
* @returns      nothing
* @description  Displays either a table or a list of the address points that are valid.
*               Mainly used for debugging but could be useful for showing a list of addresses
*               on the map
*/
	function showValidPoints($displayType,$css_id){
    $total = count($this->validPoints);
      if ($displayType == "table"){
        echo "<table id=\"".$css_id."\">\n<tr>\n\t<td>Address</td>\n</tr>\n";
        for ($t=0; $t<$total; $t++){
            echo"<tr>\n\t<td>".$this->validPoints[$t]['passedAddress']."</td>\n</tr>\n";
        }
        echo "</table>\n";
        }
      if ($displayType == "list"){
        echo "<ul id=\"".$css_id."\">\n";
      for ($lst=0; $lst<$total; $lst++){
        echo "<li>".$this->validPoints[$lst]['passedAddress']."</li>\n";
        }
        echo "</ul>\n";
       }
	}
/**
* @function     showInvalidPoints
* @param        $displayType:string
* @param        $css_id:string
* @returns      nothing
* @description  Displays either a table or a list of the address points that are invalid.
*               Mainly used for debugging shows only the points that are NOT on the map
*/
	function showInvalidPoints($displayType,$css_id){
      $total = count($this->invalidPoints);
      if ($displayType == "table"){
        echo "<table id=\"".$css_id."\">\n<tr>\n\t<td>Address</td>\n</tr>\n";
        for ($t=0; $t<$total; $t++){
            echo"<tr>\n\t<td>".$this->invalidPoints[$t]['passedAddress']."</td>\n</tr>\n";
        }
        echo "</table>\n";
        }
      if ($displayType == "list"){
        echo "<ul id=\"".$css_id."\">\n";
      for ($lst=0; $lst<$total; $lst++){
        echo "<li>".$this->invalidPoints[$lst]['passedAddress']."</li>\n";
        }
        echo "</ul>\n";
       }
	}
/**
* @function     setWidth
* @param        $width:int
* @returns      nothing
* @description  Sets the width of the map to be displayed
*/
	function setWidth($width){
		$this->mapWidth = $width;
	}
/**
* @function     setHeight
* @param        $height:int
* @returns      nothing
* @description  Sets the height of the map to be displayed
*/
	function setHeight($height){
		$this->mapHeight = $height;
	}
/**
* @function     setAPIkey
* @param        $key:string
* @returns      nothing
* @description  Stores the API Key acquired from Google
*/
	function setAPIkey($key){
		$this->apiKey = $key;
	}
/**
* @function     printGoogleJS
* @returns      nothing
* @description  Adds the necessary Javascript for the Google Map to function
*               should be called in between the html <head></head> tags
*/
	function printGoogleJS(){
		echo "\n<script src=\"http://maps.google.com/maps?file=api&amp;v=2&amp;key=".$this->apiKey."\" type=\"text/javascript\"></script>\n";
	}
	
	function js_insides() {
		$inside = "
		var map;
		var directionsPanel;
		var directions;

		function showmap(){
		
      		map = new GMap2(document.getElementById(\"map\"));
		directionsPanel = document.getElementById('dprx_directions_div');
		\n";
		if (empty($this->centerMap)){
		     $inside .= "map.setCenter(new GLatLng(".$this->validPoints[0]['long'].",".$this->validPoints[0]['lat']."), ".$this->zoomLevel.");\n";
		} else {
			$inside .= $this->centerMap;
		}
		 if ($this->showControl){
			if ($this->controlType == 'small'){
			$inside .= "map.addControl(new GSmallMapControl());\n";
			}
			if ($this->controlType == 'large'){
			$inside .= "map.addControl(new GLargeMapControl());\n";
			}
		}
		if ($this->showType){
		$inside .= "map.addControl(new GMapTypeControl());\n";
		} 
	
		if (!empty($_REQUEST['dprx_find_us_location_start'])) {
		$inside .= "
		directions = new GDirections(map, directionsPanel);
		directions.load('from: ".$_REQUEST['dprx_find_us_location_start']." to: ".get_option("dprx_find_us_location")."');
		"; 
		} else {
			$latlon = explode(",",$this->validPoints[0]['long']);
		 $inside .= "
		 var point = new GPoint(".$latlon[0].",".$latlon[1].")\n;
		 var marker = new GMarker(point);\n
		 map.addOverlay(marker)\n
		 map.setCenter(new GLatLng(".$latlon[1].",".$latlon[0]."), 8);
		 GEvent.addListener(marker, \"click\", function() {\n
		      marker.openInfoWindowHtml(\"".$this->validPoints[0]['passedAddress']."\");\n
		 });\n"; 
		}
	$inside .= "\n
	}
	window.onload = showmap();
	
	";
	return $inside;
	}
/**
* @function     showMap
* @description  Displays the Google Map on the page
*/
	function showMap(){
		echo "\n<div id=\"map\" style=\"width: ".$this->mapWidth."px; height: ".$this->mapHeight."px\">\n</div>\n";
		echo "<script type=\"text/javascript\">\n";
		?>
		<!--
		<?php echo $this->js_insides(); ?>
		//-->
		<?php
		echo "</script>\n";
		}
 ///////////THIS BLOCK OF CODE IS FROM Roger Veciana's CLASS (assoc_array2xml) OBTAINED FROM PHPCLASSES.ORG//////////////
   	function xml2array($xml){
		$this->depth=-1;
		$this->xml_parser = xml_parser_create();
		xml_set_object($this->xml_parser, $this);
		xml_parser_set_option ($this->xml_parser,XML_OPTION_CASE_FOLDING,0);//Don't put tags uppercase
		xml_set_element_handler($this->xml_parser, "startElement", "endElement");
		xml_set_character_data_handler($this->xml_parser,"characterData");
		xml_parse($this->xml_parser,$xml,true);
		xml_parser_free($this->xml_parser);
		return $this->arrays[0];

    }
    function startElement($parser, $name, $attrs){
		   $this->keys[]=$name; 
		   $this->node_flag=1;
		   $this->depth++;
     }
    function characterData($parser,$data){
       $key=end($this->keys);
       $this->arrays[$this->depth][$key]=$data;
       $this->node_flag=0; 
     }
    function endElement($parser, $name)
     {
       $key=array_pop($this->keys);
       if($this->node_flag==1){
         $this->arrays[$this->depth][$key]=$this->arrays[$this->depth+1];
         unset($this->arrays[$this->depth+1]);
       }
       $this->node_flag=1;
       $this->depth--;
     }
//////////////////END CODE FROM Roger Veciana's CLASS (assoc_array2xml) /////////////////////////////////


}//End Of Class


?>