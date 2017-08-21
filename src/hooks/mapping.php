<?php
/**
 * PLUGIN NAME: Name Of The Plugin
 * DESCRIPTION: A brief description of the Plugin.
 * VERSION: The Plugin's Version Number, e.g.: 1.0
 * AUTHOR: Name Of The Plugin Author
 */

// Call the REDCap Connect file in the main "redcap" directory
require_once "../redcap_connect.php";
require_once "inithooks.php";
include_once "bool_helper.php";
include_once "state_codes.php";

//if (SUPER_USER != 1){
//	echo "You have no rights";
//	return;
//}

$project_list = get_all_projects();
$selected_project = 0;
$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
$normalized_url = "";

if( isset($_SERVER['HTTPS'])  && $_SERVER['HTTPS'] != 'off'){
	$normalized_url = "https://" . $_SERVER['HTTP_HOST'] . $uri_parts[0];
}else {
	$normalized_url = "http://" . $_SERVER['HTTP_HOST'] . $uri_parts[0];
}


function get_all_projects(){
	$result = array();
	$conn = $GLOBALS["conn"];
	$p = $conn->query("SELECT project_id, app_title FROM redcap_projects WHERE surveys_enabled = 1") or dir("can't get project list");
	if ($p->num_rows <= 0) return $result;

	$result[0] = "Choose survey project";
	while ($row=mysqli_fetch_row($p)) {
		$result[$row[0]] = $row[1];
	}
	return $result;
}

//function getStateByNum(){
//	return $regionMapper;
//}

function get_survey_data($regionMapper, $project_id){
	$result = array();
	$conn = $GLOBALS["conn"];
	$p = $conn->query("SELECT value FROM redcap.redcap_data where project_id = ".$project_id." and (field_name = 'prepopulate_state') ") or dir("can't get project data");
	if ($p->num_rows <= 0) return $result;

	while ($row=mysqli_fetch_row($p)) {

		if (!$regionMapper[$row[0]]) echo "strange code: ".$row[0]."\r\n";
		$st = $row[0];//strval($regionMapper[$row[0]]);
		if (array_key_exists($st, $result)){
			$val = $result[$st];
			$val=$val+1;
			$result[$st] = $val;
		}else {
			$result[$st] = 1;
		}
	}
	//print_r($result);
	return $result;
}

function get_participant_data($project_id){
	//SELECT participant_identifier FROM redcap.redcap_surveys_participants as p join redcap.redcap_surveys as s on s.survey_id=p.survey_id where s.project_id = 58
	//'Aaron, Gardner, Idaho Falls, ID, 10, Eastern Idaho Regional Medical Center'
	$result = array();
	$conn = $GLOBALS["conn"];
	$p = $conn->query("SELECT participant_identifier FROM redcap.redcap_surveys_participants as p join redcap.redcap_surveys as s on s.survey_id=p.survey_id where s.project_id = ".$project_id) or dir("can't get project data");
	if ($p->num_rows <= 0) return $result;

	while ($row=mysqli_fetch_row($p)) {
		$splited_data = explode(',', $row[0]);
		$state = trim($splited_data[3]);
		if (empty($state)) {
			continue;
		}
		if (array_key_exists($state, $result)){
			$val = $result[$state];
			$val=$val+1;
			$result[$state] = $val;
		}else {
			$result[$state] = 1;
		}
	}
	//print_r($result);
	return $result;
}

function get_cities_data($regionMapper, $project_id){
	$result = array();

	$conn = $GLOBALS["conn"];
	$p = $conn->query("SELECT value, field_name, record FROM redcap.redcap_data where project_id = ".$project_id." and (field_name = 'prepopulate_state' or field_name = 'participant_city') ") or dir("can't get project data");
	if ($p->num_rows <= 0) return $result;

	while ($row=mysqli_fetch_row($p)) {
		$rec_id = $row[2];
		$fld_name = $row[1];
		$val = $row[0];

		if ($fld_name == 'prepopulate_state'){
			if (!$regionMapper[$val]) echo "strange code: ".$val."\r\n";
			$st = strval($regionMapper[$val]);
			if (array_key_exists($rec_id, $result)){
				$obj = $result[$rec_id];
				//$obj['cnt']=$obj['cnt']+1;
				$obj['state']=$st;
				$result[$rec_id] = $obj;
			}else {
				$result[$rec_id] =  array(city => "", state => $st, cnt => 1);
			}
		}else if($fld_name == 'participant_city'){
			if (array_key_exists($rec_id, $result)){
				$obj = $result[$rec_id];
				//$obj['cnt']=$obj['cnt']+1;
				$obj['city']=$val;
				$result[$rec_id] = $obj;
			}else {
				$result[$rec_id] =  array(city => $val, state => "", cnt => 1);
			}
		}
	}
	//print_r($result);
	$real_result = array();
	foreach ($result as $key => $value) {
		if (empty($value['state']) || empty($value['city'])){
			continue;
		}
		$trCity = trim($value['city']);
		$trState = trim($value['state']);
		//
		//echo "[".$trCity."|".substr($trCity, count($trCity)-4)."]";
		if (substr($trCity, count($trCity)-4) == " ".$trState || substr($trCity, count($trCity)-4) == ",".$trState){
			$place = $value['city'];	
		}else {
			$place = $trCity.", ".$trState;
		}
		$place = trim($place);

		if (array_key_exists($place, $real_result)){
			$real_result[$place] = $real_result[$place] + $value['cnt'];
		}else {
			$real_result[$place] = $value['cnt'];
		}
	}
	//print_r($real_result);
	return $real_result;
}

function get_response_rate($all_participant_data, $real_response, $regionMapper){
	$result = array();
	//print_r($all_participant_data);
	//print_r($real_response);
	foreach ($all_participant_data as $st => $value) {
		$stateNum = strval($st);
		
		$stateName = strval($regionMapper[$stateNum]);
		//print_r($regionMapper.",");
		if (!array_key_exists($stateName, $result)){
			if (isset($all_participant_data[$stateNum]) && $all_participant_data[$stateNum] != 0){
				//print_r($state);
				$rate = 100 * $real_response[$stateNum] / $all_participant_data[$stateNum];
				$v = array_key_exists($stateNum, $all_participant_data).",";
				$result[$stateName] = $rate;
			}
		}
	}
	//print_r($result);
	return $result;
}

function get_county_population(){
	$result = array();
	$arr = array_map('str_getcsv', file('data/PopCounty.csv'));

	for ($i=0; $i < count($arr); $i++) { 
		if ($arr[$i][0] == "0" || substr($arr[$i][0], count($arr[$i][0])-3) == "000"){
			continue;
		}
		if (is_numeric($arr[$i][0]) == false){
			continue;
		}
		//$arr[$i][2] = intval(str_replace(",", "", $arr[$i][2]));
		array_push($result, $arr[$i]);
	}

	return $result;
}
function get_cities_coordinates(){
	$result = array();
	$arr = array_map('str_getcsv', file('data/zip_codes_states.csv'));
	for ($i=0; $i < count($arr); $i++) { 
		//$arr[$i][2] = intval(str_replace(",", "", $arr[$i][2]));
		$result[trim($arr[$i][3].", ".$arr[$i][4])] = $arr[$i];
	}

	return $result;
}

function selectedMapType($cur_map_type, $val){
	if ($cur_map_type == $val){
		return "selected";
	}
	return "";
}

$cur_project_id = 0;
$cur_map_type = 0;
if (is_string($_GET["project_id"])){ //process post request
	$cur_project_id = $_GET["project_id"];
}
if (is_string($_GET["map_type"])){ //process post request
	$cur_map_type = $_GET["map_type"];
}


?>
<!DOCTYPE html>
<html>
<head>
	<title>Map data</title>
	<script src="https://code.jquery.com/jquery-1.12.2.min.js"   integrity="sha256-lZFHibXzMHo3GGeehn1hudTAP3Sc0uKXBXAzHX1sjtk="   crossorigin="anonymous"></script>
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
  	<link rel="stylesheet" type="text/css" href="js/jquery-jvectormap-2.0.3.css">
  	<script type="text/javascript" src="js/jquery-jvectormap-2.0.3.min.js"></script>
  	<script type="text/javascript" src="maps/map_map.js"></script>
  	<script type="text/javascript" src="maps/jquery-jvectormap-us-aea-en.js"></script>
  	<script type="text/javascript" src="js/googleDraw.js"></script>
  	<script type="text/javascript" src="js/jVectorDraw.js"></script>

</head>
<body onresize="onResize();" style="align-content: center; text-align: center;">
	<div id="undef_city">
		
	</div>

	<?php
	if (count($project_list) > 0) { ?>
		<div class="hDiv">
			<div class="hDivBox">
				<table >
					<tr>
						<td style="text-align:left;">
							choose survey: 
							<select class="projectToShow">
								<?php 
									foreach ($project_list as $project_id => $project_name) {
										if ($project_id == $cur_project_id){
											echo "<option value=\"".$project_id."\" selected>".$project_name."</option>";
										}else {
											echo "<option value=\"".$project_id."\" >".$project_name."</option>";
										}
									}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td style="text-align:left;">
							choose map type:
							<select class="mapToShow">
								<option value="0" <?php echo selectedMapType($cur_map_type, 0); ?> >Response Count</option>;
								<option value="1" <?php echo selectedMapType($cur_map_type, 1); ?> >Response Rate</option>;
								<option value="2" <?php echo selectedMapType($cur_map_type, 2); ?> >Hospital Distribution</option>;
							</select>
						</td>
					</tr>		
					<tr>
						<td style="text-align:left;">
							<input type="button" value="draw map" class="showMapForProject" >
						</td>
					</tr>			
				</table>
			</div>
		</div>
	<?php } ?>
	<div align="center" >
		<a class="map-link" target="_blank" href="#">Download map as image</a>
		<canvas id="canvas" style="display: none;"></canvas>
	</div>

	<div id="regions_div" align="center" style="border: 5px solid balck; margin: auto;">
		
	</div>
	<div id="map" align="center" style="border: 5px solid balck; margin: auto;">
		
	</div>
</body>
</html>
<script type="text/javascript">
	$(".showMapForProject").click(function(){
		var project_id = $(".projectToShow option:selected").val();
		var map_id = $(".mapToShow option:selected").val();

		if (project_id == 0){
			location.href = <?php echo "\"".$normalized_url."\""; ?>;
		}else {
			location.href = <?php echo "\"".$normalized_url."\""; ?> + "?project_id="+project_id+"&map_type="+map_id;
		}
		
	});

  	var rtime;
	var timeout = false;
	var delta = 200;
	var jDraw = undefined;
  	function onResize(){
      	rtime = new Date();
    	if (timeout === false) {
	        timeout = true;
	        setTimeout(resizeend, delta);
    	}
	}
	function resizeend() {
	    if (new Date() - rtime < delta) {
	        setTimeout(resizeend, delta);
	    } else {
	        timeout = false;
	        //googleDraw.drawMap(region_data, region_colors, displayMode);
	        if (jDraw){
	        	jDraw.resize();
	    	}
	    }               
	}

</script>

<?php 
	if ($cur_project_id == 0) return;

	//print_r($response_rate);

	$csv = get_county_population(); 
	$cities  = get_cities_coordinates();
?>

<script type="text/javascript">
    //google.charts.load('current', {'packages':['geochart']});
    var isCounty = undefined;
    var maxVal = undefined;
    var markers = undefined;

	<?php if ($cur_map_type == 0) { 
		$survey_data = get_survey_data($regionMapper, $cur_project_id);
		//print_r($survey_data);
		?>
	    var jsRegionData = { data : {	
			<?php if (count($survey_data) > 0) { 
				foreach ($survey_data as $state => $cnt) { 
					echo "'US-".strval($regionMapper[$state])."':".$cnt.",";
	  			} 
	  		} ?>  
 	    }, info: {
			<?php if (count($survey_data) > 0) { 
				foreach ($survey_data as $state => $cnt) { 
					echo "'US-".strval($regionMapper[$state])."':'Response Count: ".$cnt."',";
	  			} 
	  		} ?>  
 	    }};
	    var legentTitle = 'Response Count';
	    var region_colors = {
	    	colors: ['#FFFE9F','#14793A']
		};
		var legend_func = function (v) { return Math.round(v * 10) / 10;};
	    var displayMode = 'regions';
	<?php } else if ($cur_map_type == 1) { 
		$survey_data = get_survey_data($regionMapper, $cur_project_id);
		$all_participant_data = get_participant_data($cur_project_id);
		$response_rate = get_response_rate($all_participant_data, $survey_data, $regionMapper);
		//print_r($response_rate);
		?>
	    var jsRegionData = {  data : {
			<?php if (count($response_rate) > 0) { 
				foreach ($response_rate as $state => $cnt) { 
					if ($cnt < 30) {
						$rnd = 'sm';
					}else if($cnt >= 30 && $cnt <= 50) {
						$rnd = 'nm';
					}else {
						$rnd = 'bg';
					}
					echo "'US-".$state."':'".$rnd."',";
	  			} 
	  		} ?>  
	  	}, info: {
			<?php if (count($response_rate) > 0) { 
				foreach ($response_rate as $state => $cnt) { 
					echo "'US-".$state."':'Response Rate: ".floor($cnt)." %',";
	  			} 
	  		} ?>  
	  	}
	    };

	    var legentTitle = 'Response Rate (%)';
	    var region_colors = {
        	//values:[0, 30, 50, 100],
    		colors: {'sm':'#FC6469', 'nm':'#FFFE9F', 'bg':'#68DC64'},
    		//colors:['#DD4124', '#EFC050'],
    		//sizeAxis: {minSize: 0, maxSize:2}
	    };		
	    var legend_func = function(v){
	    	return {
                'sm': '< 30',
                'nm': '30 - 50',
                'bg': '> 50'
            }[v];
	    }
	    var displayMode = 'regions';
	<?php } else if ($cur_map_type == 2) { 
		$cities_data = get_cities_data($regionMapper, $cur_project_id);
		//print_r($cities_data);
		$undef_city = "";
		?>
		var markers = [
    		//['City', 'City', 'Responses'],

			<?php if (count($cities_data) > 0) { 
				foreach ($cities_data as $place => $cnt) { 
					if (empty($cities[$place])) {
						if (empty($undef_city)){
							$undef_city = $undef_city."[".$place."]";
						}else {
							$undef_city = $undef_city.", [".$place."]";
						}
						continue;
					}
					echo "{latLng: [".$cities[$place][1].", ".$cities[$place][2]."], Name: '".$place."', count: ".$cnt."},";
      			} 
      		} ?>
	    ];
	    $("#undef_city").html("Undefined cities coordinates: <?php echo $undef_city; ?>");
	    var jsRegionData = {  data : {
			<?php if (count($csv) > 0) { 
				foreach ($csv as $cnt) { 
			    	//print_r($state);
					echo "'".$cnt[0]."':".intval(str_replace(",", "", $cnt[2])).",";
	  			} 
	  		} ?>  
	  	}, info: {
			<?php if (count($csv) > 0) { 
				foreach ($csv as $cnt) { 
					echo "'".$cnt[0]."':'Population: ".$cnt[2]."',";
	  			} 
	  		} ?>  
	  	}
	    };

	    var region_colors = {
        	colors: ['#FFFE9F','#FB434A'],
	    };		
	    var legentTitle = 'Population density per square mile';
		var legend_func = null;
		isCounty = true;
		maxVal = 100000;
	    var displayMode = 'regions';//'markers';
    <?php  } ?>


    var googleDraw = new GoogleDraw(function(){
    	googleDraw.init("#regions_div", ".map-link");
        googleDraw.drawMap(region_data, region_colors, displayMode);
    });
    var jDraw = new jVectorDraw();
    jDraw.init("#map",null, isCounty);
    jDraw.drawMap(jsRegionData, region_colors, legentTitle, legend_func, maxVal, markers);

    //google.charts.setOnLoadCallback(drawMap);

	function svg_to_png(container) {
	    var wrapper = document.getElementById(container);
	    var svg = wrapper.querySelector("svg");

	    if (typeof window.XMLSerializer != "undefined") {
	        var svgData = (new XMLSerializer()).serializeToString(svg);
	    } else if (typeof svg.xml != "undefined") {
	        var svgData = svg.xml;
	    }

	    var canvas = document.createElement("canvas");
	    var svgSize = svg.getBoundingClientRect();
	    canvas.width = svgSize.width;
	    canvas.height = svgSize.height;
	    var ctx = canvas.getContext("2d");

	    var img = document.createElement("img");

	    img.onload = function() {
	        ctx.drawImage(img, 0, 0);
	        var imgsrc = canvas.toDataURL("image/png");

	        $(".map-link").prop("href", imgsrc);
	        //var a = document.createElement("a");
	        //a.download = container+".png";
	        //a.href = imgsrc;
	        //a.click();
	    };
	    img.setAttribute("src", "data:image/svg+xml;base64," + btoa(unescape(encodeURIComponent(svgData))) );
	}
	svg_to_png("map");
	//var canvas = document.getElementsByTagName("svg")[0];
	//var img    = canvas.toDataURL("image/png");
	//alert(canvas);


</script>

<?php
// Display the footer
	#$HtmlPage->PrintFooterExt();
?>