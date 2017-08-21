<?php 
	include_once "iHookStorage.php";
	include_once "bool_helper.php";

	class HookStorageSQL implements iHookStorage {
		private $hook_table = "";
		private $hook_assigned_projects_table = "";
		private $conn = null;

		function __construct(){
			$this->hook_table = get_class()."_hooks";
			$this->hook_assigned_projects_table = get_class()."_assigned_projects";
		}

		private function sql_conn(){
			$this->conn = new mysqli($GLOBALS["hostname"], $GLOBALS["username"], $GLOBALS["password"], $GLOBALS["db"]);
			// Check connection
			if ($this->conn->connect_error) {
			    die("Connection failed: " . $this->conn->connect_error);
			}
		}
		private function check_table(){
			if ($this->conn->query("SHOW TABLES LIKE '".$this->hook_table."'")->num_rows==0){//check if table exist
				$this->conn->query("CREATE TABLE ".$this->hook_table." (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL, hook_class nvarchar(255) NOT NULL, is_local tinyint NOT NULL DEFAULT 0, is_disabled tinyint NOT NULL DEFAULT 0)");
			}
			$this->check_error();
			if ($this->conn->query("SHOW TABLES LIKE '".$this->hook_assigned_projects_table."'")->num_rows==0){//check if table exist
				$this->conn->query("CREATE TABLE ".$this->hook_assigned_projects_table." (hook_id INT NOT NULL, project_id INT NOT NULL)");
			}
			$this->check_error();
		}
		private function check_error($sql){
			if (!empty($this->conn->error)){
				print "<div class='red'>".$this->conn->error."</div>";
				die("SQL error: " . $this->conn->connect_error . "<br/> SQL Query: ".$sql);
			}
		}
		private function query($sql){
			$result = $this->conn->query($sql);
			$this->check_error($sql);
			return $result;
		}
		private function get_hook_id_by_name($hook_class){
			$sql = "SELECT id  FROM ".$this->hook_table." WHERE hook_class = '".$hook_class."' "; //get all stored hook configs
			$result = $this->query($sql);
			if ($result->num_rows > 0){ //check if new record
				$row = $result->fetch_row();
				return $row[0];
			}
			return NULL;
		}
		private function edit_hook_conf_sql($hook_conf, $hook_class){
			$this->sql_conn();


			//update hook table
			$id = $this->get_hook_id_by_name($hook_class);
			if (is_null($id)){ //check if new record
				$sql = "INSERT INTO ".$this->hook_table." (hook_class, is_local, is_disabled) VALUES ('".$hook_class."', ".to_bool_s($hook_conf->is_local).", ".to_bool_s($hook_conf->is_disabled).")";
				$this->query($sql);
				$id = $this->conn->insert_id;
			} else {
				$sql = "UPDATE ".$this->hook_table." SET is_local = ".to_bool_s($hook_conf->is_local).", is_disabled = ".to_bool_s($hook_conf->is_disabled)." WHERE id = ".$id;
				$this->query($sql);
			}

			$this->conn->close();
		}
		private function add_assigned_project($hook_class, $project_id){
			$this->sql_conn();

			//update hook_assigned_projects
			$id = $this->get_hook_id_by_name($hook_class);
			if (is_null($id)) return; //Somethin go wrong. No hook with this id.
			$sql = "SELECT project_id FROM ".$this->hook_assigned_projects_table." WHERE hook_id = ".$id." AND project_id = ".$project_id;
			$result = $this->query($sql);
			if ($result->num_rows > 0){ //check if new record
				return; //something worng. This project already added
			}
			$sql = "INSERT INTO ".$this->hook_assigned_projects_table." (hook_id, project_id) VALUES (".$id.", ".$project_id.")";
			$this->query($sql);
		}

		private function remove_assigned_project($hook_class, $project_id){
			$this->sql_conn();

			//update hook_assigned_projects
			$id = $this->get_hook_id_by_name($hook_class);
			if (is_null($id)) return; //Somethin go wrong. No hook with this id.

			$sql = "DELETE FROM ".$this->hook_assigned_projects_table." WHERE hook_id = ".$id." AND project_id = ".$project_id;
			$this->query($sql);
		}

		public function load_configuration(){ 
			$configuration = array();
			
			$this->sql_conn();
			$this->check_table();

			$sql = "SELECT id, hook_class, is_disabled, is_local, project_id FROM ".$this->hook_table." as h LEFT JOIN ".$this->hook_assigned_projects_table." as ap ON h.id = ap.hook_id"; //get all stored hook configs
			$result = $this->query($sql);

			while ($row=mysqli_fetch_row($result)) {
				$hook_conf = $configuration[$row[1]];
				if (is_null($hook_conf)) {
					$hook_conf = new HookConf(to_bool($row[2]), to_bool($row[3]));
					$configuration[$row[1]] = $hook_conf;//store by name config for hook
				}
				if (!is_null($row[4]) && !in_array($row[4], $hook_conf->assigned_projects)){
					array_push($hook_conf->assigned_projects, $row[4]);
				}
			}
			$this->conn->close();

			return $configuration;
		}
		public function save_configuration($configuration){
			//do not need any save code. all save directly within changing
		}
		public function edit_hook_disable($configuration, $hook_class, $is_disabled){
			$hook_conf = $configuration[$hook_class];
			if (is_null($hook_conf)) {
				$hook_conf = new HookConf(FALSE, FALSE);
				$configuration[$hook_class] = $hook_conf;
			}

			$hook_conf->is_disabled = $is_disabled;
		
			$this->edit_hook_conf_sql($hook_conf, $hook_class);
		}
		public function edit_hook_local($configuration, $hook_class, $is_local){
			$hook_conf = $configuration[$hook_class];
			if (is_null($hook_conf)) {
				$hook_conf = new HookConf(FALSE, FALSE);
				$configuration[$hook_class] = $hook_conf;
			}

			$hook_conf->is_local = $is_local;
		
			$this->edit_hook_conf_sql($hook_conf, $hook_class);
		}
		public function add_project($configuration, $hook_class, $project_id){
			$hook_conf = $configuration[$hook_class];
			if (is_null($hook_conf)) {
				$hook_conf = new HookConf(FALSE, FALSE);
				$configuration[$hook_class] = $hook_conf;
			}
			array_push($hook_conf->assigned_projects, $project_id);

			$this->add_assigned_project($hook_class, $project_id);
		}
		public function remove_project($configuration, $hook_class, $project_id){
			$hook_conf = $configuration[$hook_class];
			if (!is_null($hook_conf)) {
				$id = array_search($project_id, $hook_conf->assigned_projects);
				unset($hook_conf->assigned_projects[$id]);
			}

			$this->remove_assigned_project($hook_class, $project_id);
		}

	}
?>