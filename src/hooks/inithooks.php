<?php
	$GLOBALS["HOOK_ROOT"] = dirname(__FILE__);
	//include_once "HookStorageXML.php";
	include_once "HookStorageSQL.php";
	include_once "bool_helper.php";

	class HookSubscription { // Class that must be derived by hook class
		public function redcap_control_center(){

		}
		
		public function redcap_custom_verify_username($username) {

		}

		public function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id){

		}
		public function redcap_save_record($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id){

		}
		public function redcap_survey_complete($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id){

		}
		public function redcap_survey_page($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id){

		}
		public function redcap_user_rights($project_id){
		
		}

		public function hook_name() {
			return get_class();
		}
	}

	class InitHooks {
		public $configuration = array();
		public $hooks_classes = array();
		public $hookStorage = null;

		function __construct(){
			$this->hookStorage = new HookStorageSQL();
		}

		private function HOOK_ROOT(){ //define root of hook folder
			return $GLOBALS["HOOK_ROOT"];
		}
		
		private function GLOBAL_HOOK_PATH() {
			return $this->HOOK_ROOT()."/global";
		}

		/*
			find and load all hooks.
			by default load all global hooks.
			if $project_id specified then load these hooks
		*/
		public function init_custom_hooks($project_id){ 
			$this->configuration = $this->hookStorage->load_configuration();

			//scan global hooks
			$hooks = $this->scan_hooks_files($this->GLOBAL_HOOK_PATH());
			$this->load_hook_files($hooks, $project_id, FALSE);
		}

		public function manage_configuration(){ //future feature. for checking that all hooks installed
			$this->configuration = $this->hookStorage->load_configuration();

			//scan global hooks
			$hooks = $this->scan_hooks_files($this->GLOBAL_HOOK_PATH());
			$this->load_hook_files($hooks, $project_id, TRUE);
		}
		private function load_hook_files($hooks, $project_id, $is_manage){
			foreach($hooks as $hook_class => $hook_file){
				if (!$is_manage){
					if (array_key_exists($hook_class, $this->configuration) && $this->configuration[$hook_class]->is_disabled) continue;
					if ($this->configuration[$hook_class]->is_local && !in_array($project_id, $this->configuration[$hook_class]->assigned_projects)) continue;
				}

				include_once $hook_file;
				array_push($this->hooks_classes, $hook_class);
				//array_push($this->configuration["enabled"], $hook_class);
			}
		}

		private function scan_hooks_files($directory){ // search all hooks in directory
			if (!is_string($directory)) return array();
			$result = array();
			$ext = ".php";
			$ext_len = strlen($ext);

			$files = $this->scan_files_recursevly($directory);
			foreach ($files as $file) {
				if (substr($file, strlen($file) - $ext_len, $ext_len) != $ext)
					continue;

				$class = $this->scan_php_classes($file, "HookSubscription");
				if (!empty($class)){
					foreach ($class as $instance) {
						$result[$instance] = $file;
					}
				}
			}
			return $result;
		}

		private function scan_files_recursevly($directory){ // search all files in directory
			$result = array();
			$files = scandir($directory);

			foreach ($files as $file) {
				if ($file == "." || $file == "..") continue;

				if (is_dir($directory.DIRECTORY_SEPARATOR.$file)){
					$child_result = $this->scan_files_recursevly($directory.DIRECTORY_SEPARATOR.$file);
					$result = array_merge($result, $child_result);
				} else {
					array_push($result, $directory.DIRECTORY_SEPARATOR.$file);
				}
			}
			return $result;		
		}

		/*
			Analyze php files and detect all classes.
			if $implements not null, then return only classes that derived from $implements 
		*/
		private function scan_php_classes($filepath, $implements) { 
		  	$php_code = file_get_contents($filepath);
		  	$classes = $this->scan_tokens($php_code, $implements);
		  	return $classes;
		}

		private function scan_tokens($php_code, $implements) { // analyze php structure of files
		  	$classes = array();
		  	$tokens = token_get_all($php_code);
		  	$count = count($tokens);
		  	$shift = is_string($implements) ? 6 : 2;
		  	
		  	for ($i = 0; $i < $count - $shift; $i++) {
		    	if ($tokens[$i + 0][0] == T_CLASS
		        	&& $tokens[$i + 1][0] == T_WHITESPACE
		        	&& $tokens[$i + 2][0] == T_STRING) {
	    			if (is_string($implements)){
	    				if ($tokens[$i + 4][0] != T_EXTENDS
	    					|| $tokens[$i + 6][0] != T_STRING
	    					|| $tokens[$i + 6][1] != $implements){
	    					continue;
	    				}
	    			}

		        	$class_name = $tokens[$i+2][1];
		        	$classes[] = $class_name;
		    	}
		  	}
		  	return $classes;
		} 
	}
	

?>