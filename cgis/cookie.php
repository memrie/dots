<?php



function setCookie(){
	$expire = time()+60*60; //will expire in 1 hour
	$path = "/~et5392/";
	$domain = "serenity.ist.rit.edu";
	$secure = false; //it is false by default
	setcookie("session_id",$user,$expire,$path,$domain,$secure);
	setcookie("loggedIn_status",true,$expire,$path,$domain,$secure);
}

?>