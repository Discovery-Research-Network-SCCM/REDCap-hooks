<?php 
	function to_bool($v){
		if (strcasecmp($v , "false") == 0 || $v === "" || $v === 0 || $v === false || $v === FALSE) {
			return 0;
		}
		if (strcasecmp($v , "true") == 0 || $v === "1" || $v === 1 || $v === true || $v === TRUE) {
			return 1;
		}
		 //strcasecmp($v, "1") or $v === 1 or $v === true or $v === TRUE) return TRUE;
		
		return NULL;
	}
	function to_bool_s($v){
		$r = to_bool($v); 
		if ($r === 0){
			return "false";
		}else if ($r === 1){
			return "true";
		}else {
			return "";
		}
	}
	//tests
	//runTests();
	function runTests(){
		print "to_bool(true) -> [".to_bool(true)."]<br/>";
		print "to_bool(tRue) -> [".to_bool(tRue)."]<br/>";
		print "to_bool(false) -> [".to_bool(false)."]<br/>";
		print "to_bool(fAlse) -> [".to_bool(fAlse)."]<br/>";
		print "to_bool(FALSE) -> [".to_bool(FALSE)."]<br/>";
		print "to_bool(TRUE) -> [".to_bool(TRUE)."]<br/>";
		print "to_bool(1) -> [".to_bool(1)."]<br/>";
		print "to_bool(0) -> [".to_bool(0)."]<br/>";
		print "to_bool(NULL) -> [".to_bool(NULL)."]<br/>";
		print "to_bool(\"\") -> [".to_bool("")."]<br/>";
		print "to_bool(\"1\") -> [".to_bool("1")."]<br/>";
		print "to_bool(\"true\") -> [".to_bool("true")."]<br/>";
		print "to_bool(\"false\") -> [".to_bool("false")."]<br/>";
		print "to_bool(\"tRue\") -> [".to_bool("tRue")."]<br/>";
		print "to_bool(\"fAlse\") -> [".to_bool("fAlse")."]<br/>";
		print "to_bool(3) -> [".to_bool(3)."]<br/>";
		print "to_bool(\"asdasd\") -> [".to_bool("asdasd")."]<br/>";
	}

?>