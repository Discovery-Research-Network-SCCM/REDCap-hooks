<?php
	class PrepopulateSurveyFields extends HookSubscription{
		/*
			Prepopulate Survey Fields.
		*/
		public function redcap_survey_page($project_id, $record /*= NULL*/, $instrument, $event_id, $group_id /*= NULL*/, $survey_hash, $response_id /*= NULL*/){
			//Get connection
			$conn = $this->sql_conn();
			//Query email and participant identifiers delimited by "," from DB
			$result = $conn->query("SELECT participant_email, participant_identifier FROM redcap_surveys_participants WHERE event_id = ".$event_id." AND hash like '".$survey_hash."'");
			if ($result->num_rows > 0){ //check if new record
				$row = $result->fetch_row();
				//Split by "," delimeter into array
				$parts = explode(",", $row[1]);
				//Output JS code which will find variables names and fill the values
				print "<script>
					$(document).ready(function() {
						$('input[name=participant_email]').val(\"".$row[0]."\");
						$('input[name=participant_first_name]').val(\"".$parts[0]."\");
						$('input[name=participant_last_name]').val(\"".$parts[1]."\");
						$('input[name=participant_city]').val(\"".$parts[2]."\");
						$('input[name=participant_state]').val(\"".$parts[3]."\");
						$('select[name=prepopulate_state] option[value=".$parts[3]."]').attr('selected', 'selected');
						$('input[name=prepopulate_region]').val(\"".$parts[4]."\");
						$('input[name=prepopulate_institution]').val(\"".$parts[5]."\");
						$('input[name=prepopulate_backup_name]').val(\"".$parts[6]."\");
						$('input[name=prepopulate_backup_email]').val(\"".$parts[7]."\");
					});
				</script>";
			}
			//Check if any error occured
			$this->check_error($conn);
			//Close onnection
			$conn->close();

		}
		//Checking error and display as red div on top of the page
		private function check_error($conn){
			if (!empty($conn->error)){
				print "<div class='red'>".$conn->error."</div>";
				die("SQL error: " . $conn->connect_error);
			}
		}
		//Initiate mysql connection based on current REDCap database settings
		private function sql_conn(){
			$conn = new mysqli($GLOBALS["hostname"], $GLOBALS["username"], $GLOBALS["password"], $GLOBALS["db"]);
			// Check connection for error
			if ($conn->connect_error) {
			    die("Connection failed: " . $conn->connect_error);
			}
			return $conn;
		}

		//Hook name for UI
		public function hook_name() {
			return "Prepopulate Survey Fields";
		}
	}

?>