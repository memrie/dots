
<?php
//check for a cookie, otherwise send to index

//grab cookieinfo
$res = json_decode($_COOKIE["dots"],true);

$unm = $res["unm"];
$icon = $res["icon"];

?>

<div id="user_details">

<div class="table" id="icon_username">
	<div class="table-row">
		<div class="table-cell"  id="avatar">
			<?php
				echo '<img align="left" class="circle-img" src="https://www.gravatar.com/avatar/'.$icon.'?s=50" />';
			?>
		</div>
		<div class="table-cell" id="welcome">
			Welcome, <span><?php echo $unm; ?></span>
		</div>
		
		<div class="table-cell" id="logout">
			<span id="goToLobby">
				<i class="far fa-comments"></i> Lobby
			</span>
			<span id="logoutUser">
				<i class="far fa-sign-out"></i> Logout
			</span>
		</div>
	</div>
</div>

</div>

<?php
$additional_js = '<script src="./app/app/user.js"></script>';
$additional_js .='<script>var u = new User();</script>';
?>
					
	