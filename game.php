<?php
	
	//if there isn't even a cookie, don't bother
	if(!isset($_COOKIE['dots']) || empty($_COOKIE['dots'])){
		header( 'Location: ../dots/' );
	}else if(!isset($_GET['id']) || empty($_GET['id'])){
		header( 'Location: ./chat.php' );
	}//end if: is there a cookie or even an id?
	
	$pg_title = "____ vs. ____";
	$additional_css = '<link rel="stylesheet" href="./css/game.css">'
						. '<link rel="stylesheet" href="./css/animation.css">';
	require_once("./lib/layout_top.php");
?>


<div class="main_content" id="game">
	<?php 
		require_once("./lib/user_header.php");
		require_once("./lib/game_board.php");
		require_once("./lib/game_chat_controls.php");
		
	?>
	
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="./app/game/game.js"></script>
<script src="./app/game/game_board.js"></script>
<script src="./app/game/game_square.js"></script>
<script src="./app/game/game_dot.js"></script>

<script>

	window.onload = function(){
		var game = new Game(<?php echo $_GET['id']; ?>);
	}
	
	
</script>

<?php
	$additional_js .= "<script src='./app/chat/chat.js'></script>";

	require_once("./lib/layout_bottom.php");
?>