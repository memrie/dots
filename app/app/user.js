
/**
* @constructor
*/
function User(){
	this.init();
}//end function: User


/**
*
*
*/
User.prototype.init = function(){
	var data = JSON.parse(cookie.getCookie("dots"));
	this.unm = data.unm;
	this.sid = data.sid;
	this.goToChat();
	this.logoutUser();
}//end function: User --> init


/**
* takes the user to the lobby
*/
User.prototype.goToChat = function(){
	var icon = document.getElementById("goToLobby");
	
	icon.addEventListener("click", function(){
		window.location = "./chat.php";
	});
	
	
}//end function: User --> goToChat

/**
* Allows the user to logout
*/
User.prototype.logoutUser = function(){
	var icon = document.getElementById("logoutUser");
	var app = this;
	icon.addEventListener("click", function(){
		app.logout();
	});
	
}//end function: User --> logoutUser

/**
* logs the user out
*/
User.prototype.logout = function(){
	var app = this;
	ajax.ajaxLogoutUser({
		"unm" : app.unm,
		"sid" : app.sid
	}).done(function(jsonObj){
		cookie.deleteCookie("dots");
		window.location = "../dots/";
	});
	
}//end function: User --> logout

/** --------------------- may/may not use ---------------------- **/
User.prototype.updateNotifications = function(){
	
	
	
}


User.prototype.updateChallenges = function(){
	
	
	
}


User.prototype.updateYourGames = function(){
	
	
}