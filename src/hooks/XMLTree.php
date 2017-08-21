<?php 
	require_once "../redcap_connect.php";

	if (is_string($_GET["action"])){ //process post request
		if ($_GET["action"] == "getFile"){
			get_file();
			die();
		}
	}

	$hash = array();
	function test($is_file_example){
		$file = "../MyODM.xml";
		$xml = simplexml_load_file($file);
		//$xml = simplexml_load_file("config.xml");
		return draw_xml($xml, 0, "", $is_file_example);

	}
	function get_file(){
		$myxml = test(TRUE);
		$doc = DOMDocument::loadXML($myxml->asXML());
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;

		$xml_string = $doc->saveXML();
		#$str_len = strlen($myxml->asXML());
		$str_len = strlen($xml_string);

		header('Content-Disposition: attachment; filename="sample.xml"');
		header('Content-Type: plain/text'); # Don't use application/force-download - it's not a real MIME type, and the Content-Disposition header is sufficient
		header('Content-Length: ' . $str_len);
		header('Connection: close');

		echo $xml_string;
        #echo(json_decode(json_encode($myxml->asXML())));

		/*header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
        header("Cache-Control: public"); // needed for i.e.
        header("Content-Type: application/xml");
        header("Content-Transfer-Encoding: Binary");
        header("Content-Length:".strlen($myxml->asXML()));
        header("Content-Disposition: attachment; filename=MyODM.xml");*/
        #print strlen($myxml->asXML());
    }

	function draw_xml($node, $shift, $breadcrumb, $is_file_example){
		global $hash;

		$xml_node = 1;
		if (!array_key_exists($breadcrumb.$node->getName(), $hash)){
			if ($is_file_example == TRUE){
				if (empty($breadcrumb)){
					$xml_node = new SimpleXMLElement("<".$node->getName()."></".$node->getName().">");
				}else {
					$parent = $hash[$breadcrumb];
					$node_value = NULL;
					if (count($node->children()) <= 0){
						$node_value = $node->__toString();
					}
					$xml_node = $parent->addChild($node->getName(), $node_value);
					foreach ($node->attributes() as $key => $value) {
						$xml_node->addAttribute($key, $value);
					}
				}
			}else {
				echo drawStar($shift+1).$node->getName()."<br/>";
			}

			$hash[$breadcrumb.$node->getName()] = $xml_node;
		}
		if (count($node->children()) > 0){
			for ($i=0; $i < count($node->children()); $i++) { 
				draw_xml($node->children()[$i], $shift + 1, $breadcrumb.$node->getName(), $is_file_example);
			}
		}
		return $xml_node;
	}
	function drawStar($cnt){
		for ($i=0; $i < $cnt; $i++) { 
			echo "+++++";
		}
	}
	
	$HtmlPage = new HtmlPage();
	$HtmlPage->PrintHeaderExt();
	
	test(FALSE);

	$HtmlPage->PrintFooterExt();

?>

<a href="XMLTree.php?action=getFile" class="getfileclass">download thin xml</a>
<script type="text/javascript">
	$(".gesstfileclass").click(function(){
		$.post(<?php echo "\"".$_SERVER["REQUEST_URI"]."\""; ?>, { "action" : "getFile" }, function(res){
			//alert(res);
			//location.reload();
			return res;
		});
		
	});
	
</script>
