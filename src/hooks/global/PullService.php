<?php
	class LoadODMData extends HookSubscription{
		/*
			Load ODM Data.
		*/

		//Inject JS when form opened. JS will prepare all UI and logic
		public function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id){
			//Check if record new. UI for loading data should work only for new record.
			if (!empty($record)){
				return;
			}

			//insert dialog
			$htmlFileContent = file_get_contents("../../hooks/data/htmls/ImportDataDialog.html");
			print_r($htmlFileContent);

			//*** Some test load code
			// $file = "../../hooks/data/sprint_sari_no_phi_sample_import.xml";
			// $xml = simplexml_load_file($file);

			// $itemGroupData = query_attribute($xml->ClinicalData->SubjectData->StudyEventData->FormData->ItemGroupData, "ItemGroupOID", "sprint_sari_rapid_crf");
			// $json = json_encode($itemGroupData);
			// $array = json_decode($json,TRUE);
			// print("<script type='text/javascript'>var json_data = ".$json.";</script>");
			// print("<script type='text/javascript'>var json = {\"id\":\"http://fhirtest.uhn.ca/baseDstu2/Patient/fhirTest/_history/6\",\"name\":\"Test, Fhir\",\"doB\":\"Sun Jun 18 00:00:00 EDT 1961\"};</script>");
			//*** Test code ends

			//if group id for project not specified, will use group assigned for user
			if (!isset($group_id)){
				//REDCap System method to get user rights information
				$usrInf = REDCap::getUserRights(USERID);
				$group_id = $usrInf[USERID]['group_id'];
			}

			//Load JS class with logic implementation for communication with server
			print 	"<script src='../../hooks/js/PullService.js' type='text/javascript'></script>";
			//Passing parameters to the class for initialization
			print 	"<script type='text/javascript'>PullService.group_id = '".$group_id."';</script>";
			print 	"<script type='text/javascript'>PullService.project_id = ".$project_id.";</script>";
			print 	"<script type='text/javascript'>PullService.event_id = ".$event_id.";</script>";
			print 	"<script type='text/javascript'>PullService.instrument = '".$instrument."';</script>";
			print 	"<script type='text/javascript'>PullService.hostName = '".$_SERVER['SERVER_NAME']."';</script>";
			//print_r(REDCap::getGroupNames());
		}

		//Hook name in UI
		public function hook_name() {
			return "Load ODM Data";
		}

	}

	//Extract attribute value by Path
	function query_attribute($xmlNode, $attr_name, $attr_value) {
	  foreach($xmlNode as $node) { 
	    switch($node[$attr_name]) {
	      case $attr_value:
	        return $node;
	    }
	  }
	}


?>