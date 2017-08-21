<?php
	class TimeFormStatistic extends HookSubscription{
		/*
		    Time statistic for forms
		*/
	    //When form was opened
		public function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id){
			//Current User Id
			$usr = USERID;
			//If not authorized - skip
			if (empty($usr)) return;
			//Open connection
			$conn = $this->sql_conn();
			//Check if table for statistic exist. If not - will create one.
			$this->check_table($conn);
			//Set record as 0 if not specified. 
			if (is_null($record)) $record = 0;
			
			$this->timer_start($conn, $project_id, $record, $instrument, $event_id);

			$conn->close();
		}

		//When form save
		public function redcap_save_record($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id){
			$usr = USERID;
			$conn = $this->sql_conn();
			if (empty($usr)) return;
			if (is_null($record)) $record = 0;
			
			$this->timer_stop($conn, $project_id, $record, $instrument, $event_id);
			
			$conn->close();
		}

		#end hook functions

		//Hook name for UI
		public function hook_name() {
			return "Track spent time";
		}

		#helper methods

		//Ste timezone for time calculations
		private function TZ(){
			return new DateTimeZone(date_default_timezone_get());
		}


		//Initiate mysql connection based on current REDCap database settings
		private function sql_conn(){
			$conn = new mysqli($GLOBALS["hostname"], $GLOBALS["username"], $GLOBALS["password"], $GLOBALS["db"]);
			// Check connection
			if ($conn->connect_error) {
			    die("Connection failed: " . $conn->connect_error);
			}
			return $conn;
		}

		//Check if time statistic table exist. If not - will create one. 
		private function check_table($conn){
			if ($conn->query("SHOW TABLES LIKE '".get_class()."'")->num_rows==0){//check if table exist
				$conn->query("CREATE TABLE ".get_class()." (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL, user_id nvarchar(255), record_id nvarchar(100) NOT NULL, record_id int NOT NULL, event_id int NOT NULL, instrument varchar(255), time_start DateTime, duration int NOT NULL DEFAULT 0)");
			}
		}

		//Checking error and display as red div on top of the page
		private function check_error($conn){
			if (!empty($conn->error)){
				print "<div class='red'>".$conn->error."</div>";
				die("SQL error: " . $conn->connect_error);
			}
		}

		//Start timer when enter form
		private function timer_start($conn, $project_id, $record, $instrument, $event_id){
			//Clear previous session value
			unset($_SESSION[get_class()."_rec_id"]);
			//Define SQL query for insert record row in DB fir current user/project/record/instrument with current time stamp
			$sql = "INSERT INTO ".get_class()." (user_id, project_id, record_id, event_id, instrument, time_start) VALUES ('".USERID."', ".$project_id.", '".$record."', ".$event_id.", '".$instrument."', '".NOW."')";
			//Execute query
			$conn->query($sql);
			//Check if SQL return no errors
			$this->check_error($conn);
			//Save in session last id record
			$_SESSION[get_class()."_rec_id"] = $conn->insert_id;
		}

		//Stop timer
		private function timer_stop($conn, $project_id, $record, $instrument, $event_id){
			//Check if session value is not empty
			if (!array_key_exists(get_class()."_rec_id", $_SESSION)) return;

			//Query for checking existed row in DB
			$result = $conn->query("SELECT time_start, duration FROM ".get_class()." WHERE id = ".$_SESSION[get_class()."_rec_id"]);
			if ($result->num_rows > 0){ //check if record exist
				$row = $result->fetch_row();
				//Calculate duration between initial time stamp and now
				$duration = date_timestamp_get(new DateTime(NOW, $this->TZ())) - date_timestamp_get(new DateTime($row[0], $this->TZ()));
			
				//Update row, put duration
				$sql = "UPDATE ".get_class()." SET record_id = '".$record."', duration = ".$duration." WHERE id = ".$_SESSION[get_class()."_rec_id"]; //time_start = NULL, 
				$conn->query($sql);
				//Check if SQL returned no values
				$this->check_error($conn);
			}
		}
		#end helper methods
	}

?>