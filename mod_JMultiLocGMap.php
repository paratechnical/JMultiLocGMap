<?php
/**
 * 	Copyright 20012
 *  This file is part of mod_JMultiLocGMap.
 *
 *  mod_JMultiLocGMap is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  mod_JMultiLocGMap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with mod_GMap.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Created on January , 2012
 * @author Paraschiv Andrei
 *
 */
defined( '_JEXEC' ) or die( 'Restricted access' );
//JHTML::addIncludePath(JPATH_BASE.DS.'modules'.DS.'mod_JMultiLocGMap'.DS.'markers');

function getMarkerListIds($xml)
{
	$results = $xml->xpath("/locationlists/locationlist/@id");
	return $results;
}

function getLocationsJsArray($xml,$markerListName)
{
	$jsArray = "[";
	$results = $xml->xpath("/locationlists/locationlist[@id='".$markerListName."']/location");
	for($i=0;$i<count($results);$i++)
	{
		$jsArray .= "["
						."'".$results[$i]['displayname']."',"
						.$results[$i]['lat'] .","
						.$results[$i]['long'] .","
						.$results[$i]['zindex']
					."]";
		if($i != count($results) - 1)
			$jsArray .= ",";
	}
	$jsArray .= "]";
	return $jsArray;
}

function generateLocationListChangeFunc($xml)
{
	$js = "function changeMarkers(markerType)". PHP_EOL;
	$js .= "{". PHP_EOL;
	foreach(getMarkerListIds($xml) as $id)
	{
		$js .= "if(markerType == '".$id."')". PHP_EOL;
		$js .= "{". PHP_EOL;
		$js .= "clearMarkers();". PHP_EOL;//getLocationsJsArray($xml,$id)
		$js .= "var ".$id."=".getLocationsJsArray($xml,$id). PHP_EOL;
		$js .= "setMarkers(map, ".$id.");". PHP_EOL;
		$js .= "}". PHP_EOL;
	}
	$js .= "}". PHP_EOL;
	return $js;
}

$document =& JFactory::getDocument();
$width = $params->get('width', 160);
$height = $params->get('height', 120);
$lat = $params->get('lat', 49);
$long = $params->get('lng', -122);
$zoom = $params->get('zoom', 3);
$mapName = $params->get('mapName', 'map');
$mapType = $params->get('mapType', 'ROADMAP');
$js = "http://maps.google.com/maps/api/js?sensor=false";
$path = JURI::root();

$document->addScript($js);
$mapOptions = '';

$path2markerxml = JPATH_BASE.DS."modules".DS."mod_JMultiLocGMap".DS."markers".DS."markers.xml";
//var_dump(file_exists($path2markerxml));

$xml = simplexml_load_file($path2markerxml);

$firstmarkerlist = "";
$markerlists = $xml->children();
if(count($markerlists) > 0)
	$firstmarkerlist = $markerlists[0]['id'];

$navControls = true;
if($params->get('static') || $params->get('navControls', false) == 0){
	$mapOptions .= ',disableDefaultUI: false'. PHP_EOL;
	$mapOptions .= ',streetViewControl: false' . PHP_EOL;
	$navControls = false;
}
if($params->get('smallmap')){
	$mapOptions .=  ', navigationControlOptions: {style: google.maps.NavigationControlStyle.SMALL} ' . PHP_EOL;
	$navControls = true;
}

if(!$navControls)
	$mapOptions .= ',navigationControl: false' . PHP_EOL;


if($params->get('static')){
	$mapOptions .= 	', draggable: false' .PHP_EOL;
}
$mapTypeControl = $params->get('mapTypeControl',false) ? 'true' : 'false';

$mapOptions .= ",mapTypeControl: {$mapTypeControl}". PHP_EOL;

$script =<<<EOL

	var markers = [];

	function clearMarkers()
	{
		for (var i = 0; i < markers.length; i++)
			  markers[i].setMap(null);
		markers=[];
	}

	function setMarkers(map, locations) {
	  // Add markers to the map

	  // Marker sizes are expressed as a Size of X,Y
	  // where the origin of the image (0,0) is located
	  // in the top left of the image.

	  // Origins, anchor positions and coordinates of the marker
	  // increase in the X direction to the right and in
	  // the Y direction down.
	  var image = new google.maps.MarkerImage('{$path}modules/mod_JMultiLocGMap/images/marker.png',
		  // This marker is 40 pixels wide by 40 pixels tall.
		  new google.maps.Size(40, 40),
		  // The origin for this image is 0,0.
		  new google.maps.Point(0,0),
		  // The anchor for this image is the base of the flagpole at 0,32.
		  new google.maps.Point(0, 40));
	  
		  // Shapes define the clickable region of the icon.
		  // The type defines an HTML &lt;area&gt; element 'poly' which
		  // traces out a polygon as a series of X,Y points. The final
		  // coordinate closes the poly by connecting to the first
		  // coordinate.
	  var shape = {
		  coord: [1, 1, 1, 40, 38, 40, 38 , 1],
		  type: 'poly'
	  };
	  for (var i = 0; i < locations.length; i++) {
		myLatLng = new google.maps.LatLng(locations[i][1], locations[i][2]);
		marker = new google.maps.Marker({
			position: myLatLng,
			map: map,
			icon: image,
			shape: shape,
			title: locations[i][0],
			zIndex: locations[i][3]
		});
		markers.push(marker);
	  }
	}
	
	google.maps.event.addDomListener(window, 'load', {$mapName}load);

    function {$mapName}load() {
		var options = {
			zoom : {$zoom},
			center: new google.maps.LatLng({$lat}, {$long}),
			mapTypeId: google.maps.MapTypeId.{$mapType}
			{$mapOptions}
		}

        {$mapName} = new google.maps.Map(document.getElementById("{$mapName}"), options);
		
		changeMarkers('{$firstmarkerlist}');
    }

EOL;

JHTML::_('behavior.mootools');

$document->addScriptDeclaration($script);
$document->addScriptDeclaration(generateLocationListChangeFunc($xml));

?>
	
<div style="width:100%">
	<select id="institutiontype" onchange="changeMarkers(this.options[this.selectedIndex].value)">
	<?php
	for($i=0; $i<count($markerlists); $i++)
	{
		$attr = $markerlists[$i]->attributes();
		echo "<option value='".$attr['id']."'>".$attr['displayname']."</option>";
	}
	?>
	</select>
</div>
<div id="<?php echo $mapName;?>" style="margin-left:auto;margin-right:auto;text-align:center;width: <?php echo $width; ?>px; height: <?php echo $height; ?>px"></div>