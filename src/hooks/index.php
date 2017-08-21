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

if (SUPER_USER != 1){
	echo "You have no rights";
	return;
}

$inithooks = new InitHooks();
$inithooks->manage_configuration(); #initialize some variables and check if all required hooks installed

$project_list = get_all_projects();

function get_all_projects(){
	$result = array();
	$conn = $GLOBALS["conn"];
	$p = $conn->query("SELECT project_id, app_title FROM redcap_projects") or dir("can't get project list");
	if ($p->num_rows <= 0) return $result;

	while ($row=mysqli_fetch_row($p)) {
		$result[$row[0]] = $row[1];
	}
	return $result;
}

function filter_projects($project_list, $ids){
	$filtered = array();
	foreach ($project_list as $id => $name) {
		if (!in_array($id, $ids)){
			$filtered[$id] = $name;
		}
	}
	return $filtered;
}


if (is_string($_POST["action"])){ //process post request
	$post_action = $_POST["action"];

	if ($post_action == "disableHook"){
		$post_hookClass = $_POST["hookClass"];
		$post_value = $_POST["value"];
		$inithooks->hookStorage->edit_hook_disable($inithooks->configuration, $post_hookClass, $post_value);

	} else if ($post_action == "actionGlobal"){
		$post_hookClass = $_POST["hookClass"];
		$post_value = $_POST["value"];

		$inithooks->hookStorage->edit_hook_local($inithooks->configuration, $post_hookClass, $post_value);
	} else if ($post_action == "actionAddProject"){
		$post_hookClass = $_POST["hookClass"];
		$post_value = $_POST["value"];

		$inithooks->hookStorage->add_project($inithooks->configuration, $post_hookClass, $post_value);
	} else if ($post_action == "actionRemoveProject") {
		$post_hookClass = $_POST["hookClass"];
		$post_value = $_POST["value"];
		$inithooks->hookStorage->remove_project($inithooks->configuration, $post_hookClass, $post_value);
	}

	return;
}

// Display the header
$HtmlPage = new HtmlPage();
$HtmlPage->PrintHeaderExt();

// HTML page content goes here
?>
<h3 style="color:#800000;">
	Hooks settings
</h3>
<div>
	<?php foreach ($inithooks->hooks_classes as $key => $value) { 
		$hook_conf = $inithooks->configuration[$value];
		if (is_null($hook_conf)){
			$hook_conf = new HookConf(FALSE, FALSE);
		}

		?>
		<div class="flexigrid" style="width:850px;">
			<div class="mDiv">
				<div class="ftitle">
					<div style="float:left;">
						<b>Hook:&nbsp;</b><b style="color:#800000;"> <?php echo $value::hook_name(); ?></b>
					</div>
					<div style="float:right;">
						<input type="checkbox" name="disabledHook" value="Disabled" class="actionDisable"  <?php echo "hookClass=\"".$value."\""; if ($hook_conf->is_disabled) echo "checked=\"checked\""; ?> >&nbsp;Disabled
					</div>
					<div class="clear"></div>
				</div>
			</div>
			<div class="hDiv">
				<div class="hDivBox">
					<table >
						<tr>
							<td >
								<div style="width:425px;">
									<input type="radio" class="actionGlobal" name=<?php echo "\"".$value."_hookType\""; echo "hookClass=\"".$value."\""; ?> value="false" <?php if (!$hook_conf->is_local) echo "checked=\"checked\"";?> >&nbsp;Global hook
								</div>
							</td>
							<td>
								<div style="width:425px;">
									<input type="radio" class="actionGlobal" name=<?php echo "\"".$value."_hookType\""; echo "hookClass=\"".$value."\""; ?> value="true" <?php if ($hook_conf->is_local) echo "checked=\"checked\"";?> >&nbsp;Project specific hook
								</div>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<?php if ($hook_conf->is_local) { ?>
				<div class="bDiv">
					<div class="hDivBox">
						<table >
							<?php foreach ($hook_conf->assigned_projects as $project_id) {  
								$project_name = $project_list[$project_id];
								if (is_null($project_name)) continue;
								?>
								<tr>
									<td >
										<div style="width:775px; margin-left:20px;">
											<?php echo $project_name; ?>
										</div>
									</td>
									<td>
										<div style="width:35px; ">
											<a href="javascript:;" onclick="return false;">
												<img src="images/cross.png" <?php echo "hookClass=\"".$value."\" project_id=\"".$project_id."\""; ?> class="actionRemoveProject">
											</a>
										</div>
									</td>
								</tr>
							<?php } ?>
						</table>
					</div>
				</div>
				<?php 
				$filtered = filter_projects($project_list, $hook_conf->assigned_projects);
				if (count($filtered) > 0) { ?>
					<div class="hDiv">
						<div class="hDivBox">
							<table >
								<tr>
									<td style="width:850px; text-align:center;">
										<select <?php echo "hookClass=\"".$value."\""; ?> class="projectToAdd">
											<?php 
												foreach ($filtered as $project_id => $project_name) {
													echo "<option value=\"".$project_id."\">".$project_name."</option>";
												}
											?>
										</select>
										<input type="button" value="Add" <?php echo "hookClass=\"".$value."\""; ?> class="actionAddProject" >
									</td>
								</tr>					
							</table>
						</div>
					</div>
				<?php } ?>
			<?php } ?>
			<br/>
		</div>
	<?php } ?>
</div>

<script type="text/javascript">
	$(".actionDisable").change(function(){
		$.post(<?php echo "\"".$_SERVER["REQUEST_URI"]."\""; ?>, { "action" : "disableHook", "hookClass" : $(this).attr("hookClass"), "value" : $(this).is(':checked') }, function(res){
			location.reload();
		});
		
	});
	$(".actionGlobal").change(function(){
		$.post(<?php echo "\"".$_SERVER["REQUEST_URI"]."\""; ?>, { "action" : "actionGlobal", "hookClass" : $(this).attr("hookClass"), "value" : $(this).val() }, function(res){
			location.reload();
		});
		
	});
	$(".actionAddProject").click(function(){
		var project_id = $(".projectToAdd[hookClass=\""+$(this).attr("hookClass")+"\"] option:selected").val();
		$.post(<?php echo "\"".$_SERVER["REQUEST_URI"]."\""; ?>, { "action" : "actionAddProject", "hookClass" : $(this).attr("hookClass"), "value" : project_id }, function(res){
			location.reload();
		});
		
	});
	$(".actionRemoveProject").click(function(){
		$.post(<?php echo "\"".$_SERVER["REQUEST_URI"]."\""; ?>, { "action" : "actionRemoveProject", "hookClass" : $(this).attr("hookClass"), "value" : $(this).attr("project_id") }, function(res){
			location.reload();
		});
		
	});
</script>

<?php
// Display the footer
	$HtmlPage->PrintFooterExt();
?>