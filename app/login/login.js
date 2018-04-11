
/** 
* function to create the login app - 
* handles login/signup functionality 
*/
function Login(){}

//what tab are we currently looking at?
//default = login
Login.prototype.selected_tab = "login";
Login.prototype.tab_id_sufix = "_tab";
Login.prototype.box = "<svg>"+
"<rect width='20px' height='20px' fill='' />"+
"<circle cx='0' cy='0' r='2px' fill='#6D6763' />"+
"</svg>";

/**
* Populate the needed variables for initiation
*/
Login.prototype.init = function(){
	var app = this;
	//login fields
	this.unm = document.getElementById("unm");
	this.pwd = document.getElementById("pwd");
	
	
	this.unm.addEventListener("keypress",function(e){
		var code = e.keyCode || e.which;
		if(code == 13){
			app.login.click();
		}//end if: enter?
	});//end click event
	
	this.pwd.addEventListener("keypress",function(e){
		var code = e.keyCode || e.which;
		if(code == 13){
			app.login.click();
		}//end if: enter?
	});//end click event
	
	
	this.login = document.getElementById("login_btn");
	this.login_errors = document.getElementById("login_errors");
	
	//signup fields
	this.new_unm = document.getElementById("new_unm");
	this.new_pwd = document.getElementById("new_pwd");
	this.new_pwd_again = document.getElementById("new_pwd_again");
	this.email = document.getElementById("email");
	this.email_again = document.getElementById("email_again");
	this.signup = document.getElementById("signup_btn");
	this.signup_errors = document.getElementById("signup_errors");
	
	this.addEvents();
	//select default tab
	this.selectTab();
	this.validLogin();
	//this.validAcc();
	this.signup.disabled = true;
}//end function: Login --> init



Login.prototype.addEvents = function(){
	var app = this;
	//add click event for whent the user clicks the login button
	this.login.addEventListener("click", function(){
		app.handleLogin();
	});
	//login items
	this.unm.addEventListener("keyup",function(){
		app.validLogin();
	});
	this.pwd.addEventListener("keyup",function(){
		app.validLogin();
	});
	
	/// Signup items
	this.new_unm.addEventListener("keyup",function(){
		app.validAcc();
	});
	this.new_pwd_again.addEventListener("keyup",function(){
		app.validAcc();
	});
	this.new_pwd.addEventListener("keyup",function(){
		app.validAcc();
	});
	this.email.addEventListener("keyup",function(){
		app.validAcc();
	});
	this.email_again.addEventListener("keyup",function(){
		app.validAcc();
	});
	
	
	//add click event for when the user clicks the signup button
	this.signup.addEventListener("click", function(){
		console.log("hey there");
		app.handleSignup();
	});
}

Login.prototype.selectTab = function(){
	document.getElementById(this.selected_tab + this.tab_id_sufix).classList.add("selected");
}//end function: Login --> selectTab

Login.prototype.unselectTab = function(){
	document.getElementById(this.selected_tab + this.tab_id_sufix).classList.remove("selected");
}//end function: Login --> unselectTab

/**
* Show the tab the user has clicked on
* @param ele {String} the id of the element that was clicked on
*/
Login.prototype.showTab = function(ele){
	var tab_id = ele.split("_");
	var show_tab = tab_id[0];
	var tab = document.getElementById(show_tab);
	if(tab){
		this.unselectTab();
		document.getElementById(this.selected_tab).style.display = "none";
		this.selected_tab = show_tab;
		this.selectTab();
		tab.style.display = "block";
	}//end if: does the tab exist?
}//end function: Login --> showTab

/**
* Do they have the minimum data required to even 
* allow them to click the login buttton?
*/
Login.prototype.validLogin = function(){
	
	if(this.unm.value == "" || this.pwd.value == ""){
		this.login.disabled = true;
	}else{
		this.login.disabled = false;
	}//end else/if: have they given us a username/pass at all?
	
}//end function: Login --> validLogin


/**
* Check if the information they are trying to sign up with
* is valid data, otherwise, disable the signup button
*/
Login.prototype.validAcc = function(){
	var errors = document.getElementById('signup_errors');
	this.signup_errors.innerHTML = "";
	
	var error_list = "";
	
	//check if the passwords and emails are the same
	var match_pwd = (this.new_pwd.value == this.new_pwd_again.value) ? true : false;
	var match_email = (this.email.value == this.email_again.value) ? true : false;
	
	if(!match_pwd){
		error_list += "<li>Passwords do not match</li>";
	}//end if
	
	if(this.new_pwd.value.length < 6){
		error_list += "<li>Password is not long enough</li>";
	}
	
	if(!match_email){
		error_list += "<li>Emails do not match</li>";
	}//end if
	
	if(this.new_unm.value.length < 4){
		error_list += "<li>Username is not long enough</li>";
	}//end if
	
	this.signup_errors.innerHTML = error_list;
	
	if(error_list == ""){
		this.signup_errors.style.display = "none";
		this.signup.disabled = false;
	}else{
		this.signup_errors.style.display = "block";
		this.signup.disabled = true;
	}//end else/if: do we have matching email/pass and username?
	
}//end function: Login --> validAcc

Login.prototype.handleLogin = function(){
	var code;
	var desc;
	var app = this;
	this.login_errors.style.display = "none";
	//send ajax call to login
	ajax.ajaxLogin({
		"unm":this.unm.value,
		"pwd":this.pwd.value
	}).done(function(jsonObj){
		if(jsonObj['code'] == 0){
			app.login_errors.innerHTML = jsonObj['desc'];
			app.login_errors.style.display = "block";
		}else{
			var res = jsonObj["result"];
			app.setCookies(res["unm"],res["icon"],res["sid"]);
			app.goToLobby();
		}
		
	});
}//end functoin: Login --> handleLogin


Login.prototype.handleSignup = function(){
	//send ajax call to signup
	var code;
	var desc;
	var app = this;
	this.signup_errors.style.display = "none";
	//send ajax call to login
	ajax.ajaxSignup({
		"unm":this.new_unm.value,
		"pwd":this.new_pwd.value,
		"pwd_again":this.new_pwd_again.value,
		"email":this.email.value,
		"email_again":this.email_again.value
	}).done(function(jsonObj){
		if(jsonObj['code'] == 0){
			app.signup_errors.innerHTML = jsonObj['desc'];
			app.signup_errors.style.display = "block";
		}else{
			var res = jsonObj["result"];
			app.setCookies(res["unm"],res["icon"],res["sid"]);
			app.goToLobby();
		}
		
	});
}//end function: Login --> handleSignup

/**
* sets the value of the cookie to be
*/
Login.prototype.setCookies = function(unm,icon,sid){
	//name, value, days
	cookie.setCookie("dots",
		JSON.stringify({
			"unm":unm,
			"icon":icon,
			"sid":sid
		}),
	1);
}//end function: Login --> setCookies

/**
*Takes a user from here to the lobby
*/
Login.prototype.goToLobby = function(){
	var t = window.location.pathname;
	var x = t.substr(0,t.lastIndexOf("/"));
	window.location = x + "/chat.php";
}//end function: Login --> goToLobby

