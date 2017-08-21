<?php
    /*
        Pull ODM data from server
    */
    include_once "define_response_function.php";
    require_once "../../redcap_connect.php";
    require_once "../../fhir_credentials.php";

    //Check if user authorized
    if (!session_id() || null == USERID){
        header('HTTP/1.1 401 Unauthorized', true, 401);
        return;
    }
    //Define base URL for current server instance and used scheme.
    $port = "8282";
    if (getCurScheme() == "https") {
        $port = "8445";
    }
    $base_url = getCurScheme()."://".$_SERVER['SERVER_NAME'].":".$port."/usciitg-prep-ws/api/fhir/patient/";// + encodeURIComponent(mrn) + "?";

    //define parameters
    $group_id = "";
    $mrn = "";
    $encounter_start = "";
    $project_id = "";
    $event_id = "";
    $instrument = "";
    $encounter_end = "";

    //fill parameters
    if (!empty($_GET["group_id"])){
        $group_id = urlencode($_GET["group_id"]);
    }
    if (!empty($_GET["mrn"])){
        $mrn = urlencode($_GET["mrn"]);
    }
    if (!empty($_GET["encounter_start"])){
        $encounter_start = urlencode($_GET["encounter_start"]);
    }
    if (!empty($_GET["project_id"])){
        $project_id = urlencode($_GET["project_id"]);
    }
    if (!empty($_GET["event_id"])){
        $event_id = urlencode($_GET["event_id"]);
    }
    if (!empty($_GET["instrument"])){
        $instrument = urlencode($_GET["instrument"]);
    }
    if (!empty($_GET["encounter_end"])){
        $encounter_end = urlencode($_GET["encounter_end"]);
    }

    //Build full URL for request
    $url = $base_url.$mrn."/odm?";
    //add to full URL request parameters only if they are exist. 
    if (!empty($group_id)){
        $url = $url."&group_id=".$group_id;
    }
    if (!empty($encounter_start)){
        $url = $url."&encounter_start=".$encounter_start;
    }
    if (!empty($project_id)){
        $url = $url."&project_id=".$project_id;
    }
    if (!empty($event_id)){
        $url = $url."&event_id=".$event_id;
    }
    if (!empty($instrument)){
        $url = $url."&instrument=".$instrument;
    }
    if (!empty($encounter_end)){
        $url = $url."&encounter_end=".$encounter_end;
    }

    //curl initialization
    $ch = curl_init();
    //set curl options and authorization
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, FHIR_USER.":".FHIR_PWD);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0); //Possible to wait very long time
    curl_setopt($ch, CURLOPT_TIMEOUT, 400); //timeout in seconds
    set_time_limit(0);//Possible to wait very long time
    //Execute curl request and get response
    $output = curl_exec($ch);
    //Get information about returned content type
    $info = curl_getinfo($ch);
    //Don't forget to close curl
    curl_close($ch);

    //return response to the stream which will be parsed by JS later
    echo ($output);

    header("Content-Type: ".$info["content_type"]);
    http_response_code($info["http_code"]);

?>