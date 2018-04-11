<?php
	
	require("./db.class.php");
	require("./utility.php");
	
	if(isset($_REQUEST['method'])){
		//include all files for needed area (a)
		foreach (glob("./service/*.php") as $filename){
			include $filename;
		}
		$serviceMethod=$_REQUEST['method'];
		$data=$_REQUEST['data'];
		$result=@call_user_func($serviceMethod,$data);
		if($result){
			//might need the header cache stuff
			header("Content-Type:text/plain");
			echo $result;
		}
	}
?>