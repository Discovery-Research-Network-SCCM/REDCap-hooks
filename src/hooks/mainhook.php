<?php
	error_reporting(32767);
	include_once dirname(__FILE__)."/inithooks.php";
	
	function redcap_control_center(){
		$inithooks = new InitHooks();
		$inithooks->init_custom_hooks(); #initialize some variables and check if all required hooks installed

		foreach ($inithooks->hooks_classes as $class) {
			$instance = new $class();
			if (method_exists($instance, __FUNCTION__)){
				$instance->{__FUNCTION__}();
			}
		}
		
	}
	
	function redcap_custom_verify_username($username) {
		$inithooks = new InitHooks();
		$inithooks->init_custom_hooks(); #initialize some variables and check if all required hooks installed

		foreach ($inithooks->hooks_classes as $class) {
			$instance = new $class();
			if (method_exists($instance, __FUNCTION__)){
				$instance->{__FUNCTION__}($username);
			}
		}
	}

	function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id){
		$inithooks = new InitHooks();
		$inithooks->init_custom_hooks($project_id); #initialize some variables and check if all required hooks installed

		foreach ($inithooks->hooks_classes as $class) {
			$instance = new $class();
			if (method_exists($instance, __FUNCTION__)){
				$instance->{__FUNCTION__}($project_id, $record, $instrument, $event_id, $group_id);
			}
		}
	}
	function redcap_save_record($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id){
		$inithooks = new InitHooks();
		$inithooks->init_custom_hooks($project_id); #initialize some variables and check if all required hooks installed

		foreach ($inithooks->hooks_classes as $class) {
			$instance = new $class();
			if (method_exists($instance, __FUNCTION__)){
				$instance->{__FUNCTION__}($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id);
			}
		}
	}
	function redcap_survey_complete($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id){
		$inithooks = new InitHooks();
		$inithooks->init_custom_hooks($project_id); #initialize some variables and check if all required hooks installed

		foreach ($inithooks->hooks_classes as $class) {
			$instance = new $class();
			if (method_exists($instance, __FUNCTION__)){
				$instance->{__FUNCTION__}($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id);
			}
		}
	}
	function redcap_survey_page($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id){
		//for surevey
		$inithooks = new InitHooks();
		$inithooks->init_custom_hooks($project_id); #initialize some variables and check if all required hooks installed

		foreach ($inithooks->hooks_classes as $class) {
			$instance = new $class();
			if (method_exists($instance, __FUNCTION__)){
				$instance->{__FUNCTION__}($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id);
			}
		}
	}

	function redcap_user_rights($project_id)
	{
		$inithooks = new InitHooks();
		$inithooks->init_custom_hooks($project_id); #initialize some variables and check if all required hooks installed

		foreach ($inithooks->hooks_classes as $class) {
			$instance = new $class();
			if (method_exists($instance, __FUNCTION__)){
				$instance->{__FUNCTION__}($project_id);
			}
		}
    }
	//todo: automatically check and create database
	//todo: look local hooks by project name not by project id    

?>