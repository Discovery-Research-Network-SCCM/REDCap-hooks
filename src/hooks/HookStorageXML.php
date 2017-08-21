<?php 
	include_once "iHookStorage.php";
	include_once "bool_helper.php";

	class HookStorageXML implements iHookStorage {
		public function load_configuration(){ 
			$configuration = array();

			$file = $GLOBALS["HOOK_ROOT"]."/config.xml";
			$xml=simplexml_load_file($file) or ($xml = new SimpleXMLElement("<configuration></configuration>"));
			if ($xml->getName()=="configuration"){ //root 
				foreach ($xml as $key => $hookItem) { //iterate throught childs of root
					if ($hookItem->getName() == "hook"){ // if child is hook tag
						$hook_conf = new HookConf($hookItem->attributes()["disabled"], $hookItem->attributes()["local"]);//create config for hook. read disabled and local settings
						$hook_class = (string)$hookItem->attributes()["hookClass"];
						$configuration[$hook_class] = $hook_conf;//store by name config for hook

						foreach ($hookItem as $key => $projects_root) { //look for "projects" child node
							if ($projects_root->getName() == "projects"){
								foreach ($projects_root as $key => $projectItem) { //iterate through assigned projects
									if ($projectItem->getName() == "project"){
										array_push($hook_conf->assigned_projects, (string)$projectItem->attributes()["id"]);
									}
								}								
							}
						}
					}
				}
			}
			return $configuration;
		}
		public function save_configuration($configuration){
			$file = $GLOBALS["HOOK_ROOT"]."/config.xml";
			$rootXML = new SimpleXMLElement("<configuration></configuration>");
			foreach ($configuration as $hook_class => $hook_conf) {
				$hook = $rootXML->addChild("hook");
				$hook->addAttribute("disabled", to_bool_s($hook_conf->is_disabled));
				$hook->addAttribute("local", to_bool_s($hook_conf->is_local));
				$hook->addAttribute("hookClass", $hook_class);
				$projects = $hook->addChild("projects");
				foreach ($hook_conf->assigned_projects as $project_id) {
					$project = $projects->addChild("project");
					$project->addAttribute("id", $project_id);
				}
			}

			//file_put_contents("config.xml", $rootXML->asXML());
			return $rootXML->asXML($file);
		}
		public function edit_hook_disable($configuration, $hook_class, $is_disabled){
			$hook_conf = $configuration[$hook_class];
			if (is_null($hook_conf)) {
				$hook_conf = new HookConf(FALSE, FALSE);
				$configuration[$hook_class] = $hook_conf;
			}

			$hook_conf->is_disabled = $is_disabled;
		
			$this->save_configuration($configuration);
		}
		public function edit_hook_local($configuration, $hook_class, $is_local){
			$hook_conf = $configuration[$hook_class];
			if (is_null($hook_conf)) {
				$hook_conf = new HookConf(FALSE, FALSE);
				$configuration[$hook_class] = $hook_conf;
			}

			$hook_conf->is_local = $is_local;
		
			$this->save_configuration($configuration);
		}
		public function add_project($configuration, $hook_class, $project_id){
			$hook_conf = $configuration[$hook_class];
			if (is_null($hook_conf)) {
				$hook_conf = new HookConf(FALSE, FALSE);
				$configuration[$hook_class] = $hook_conf;
			}
			array_push($hook_conf->assigned_projects, $project_id);

			$this->save_configuration($configuration);
		}
		public function remove_project($configuration, $hook_class, $project_id){
			$hook_conf = $configuration[$hook_class];
			if (!is_null($hook_conf)) {
				$id = array_search($project_id, $hook_conf->assigned_projects);
				unset($hook_conf->assigned_projects[$id]);
			}

			$this->save_configuration($configuration);
		}

	}
?>