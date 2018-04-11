<?php
	
	//if there isn't even a cookie, don't bother
	if(!isset($_COOKIE['dots']) || empty($_COOKIE['dots'])){
		header( 'Location: ../dots/' ) ;
	}
	
	$pg_title = "Lobby";
	$additional_css = '<link rel="stylesheet" href="./css/lobby.css">';
	require_once("./lib/layout_top.php");
?>


<div class="main_content" id="lobby">
	<?php 
		require_once("./lib/user_header.php");
		require_once("./lib/lobby_chat_controls.php");
		require_once("./lib/lobby_user_controls.php");
	?>
	
</div>



<?php
	$additional_js .= "<script src='./app/lobby/lobby.js'></script>" 
					. "<script src='./app/chat/chat.js'></script>"
					. "<script>var lobby = new Lobby();</script>";
	
	require_once("./lib/layout_bottom.php");
?>