<?php 
	include_once "bool_helper.php";
	interface iHookStorage {
		public function load_configuration();
		public function save_configuration($configuration);
		public function edit_hook_disable($configuration, $hook_class, $is_disabled);
		public function edit_hook_local($configuration, $hook_class, $is_local);
		public function add_project($configuration, $hook_class, $project_id);
		public function remove_project($configuration, $hook_class, $project_id);
	}

	/**
	* represent Xml hook configuration
	*/
	class HookConf
	{
		public $assigned_projects = array();
		public $is_disabled = FALSE;
		public $is_local = FALSE;

		function __construct($_is_disabled, $_is_local)
		{
			$this->is_disabled = to_bool((string)$_is_disabled);
			$this->is_local = to_bool((string)$_is_local);
		}
	}
?>