/************************************************
* @desc		handles a chat instance
* 
* @date		October 30th, 2017
* @author	erika tobias [et5392@rit.edu]
************************************************/

/**
* Creates a Chat Instance
* @constructor
*/
function Chat(id, name, user_list, allow_challenges, parent_app){
	//if we have an id set it, otherwise default to lobby
	this.chat_id = id;
	this.game_id = 0;
	this.name = name;
	this.user_list = user_list;
	this.allow_challenges = allow_challenges;
	this.parent_app = parent_app;
	this.current_dialog = "";
	
	this.init();
}//end function: Chat

//default is "0" --> will be updated later in "updateChat"
Chat.prototype.timestamp = 0;
Chat.prototype.user_amt = document.getElementById("user_amt");


Chat.prototype.init = function(){
	var app = this;
	//grab the css needed for this class
	var css_style = document.createElement('link');
	css_style.rel = "stylesheet";
	css_style.href="./css/chat.css";
	document.getElementsByTagName('head')[0].appendChild(css_style);
	
	this.my_message = document.getElementById("your_message");
	this.all_messages = document.getElementById("messages-list");
	this.message_button = document.getElementById("send_your_message");
	
	
	this.my_message.addEventListener("keypress",function(e){
		var code = e.keyCode || e.which;
		if(code == 13){
			if(!e.shiftKey){
				app.message_button.click();
			}//shift?
		}//end if: enter?
	});//end click event
	
	
	
	var data = JSON.parse(cookie.getCookie("dots"));
	this.unm = data.unm;
	this.sid = data.sid;
	
	this.message_button.addEventListener("click",function(){
		app.sendMsg();
	});
	
	//let the user join the chat
	this.joinChat();
	
	//make sure the user leaves any chat when they leave this page
	window.onbeforeunload = (function(){
		app.leaveChat();
		return null;
	});
}//end function: Chat --> init


Chat.prototype.joinChat = function(){
	var app = this;
	ajax.ajaxGetChatRoom({
		"unm" : app.unm,
		"sid" : app.sid,
		"chat_name" : app.name,
		"chat_id" : app.chat_id,
		"game_id" : ""
	}).done(function(jsonObj){
		app.chat_id = jsonObj.result.id;
		app.chat_name = jsonObj.result.name;
		app.timestamp = jsonObj.result.timestamp
		var code = jsonObj.code;
		if(code > 0){
			app.updateChat();
			var item = document.createElement("div");
			var d = new Date();
			var this_timestamp =  d.getFullYear() + "-" + (d.getMonth() + 1)
								+ "-" + d.getDate() + " " + d.getHours() 
								+ ":" + d.getMinutes();
			
			
			item.innerHTML = "<b class='system'>system [" 
							+ this_timestamp + "]:</b> Welcome back, " + app.unm + "!";
			app.all_messages.appendChild(item);
			
			//let's setup a way to get updates 
			setInterval(function(){
				app.updateChat();
			}, 2000);
			
		}else if(code < 0){
			//session is bad
			document.location = "../dots/";
		}else{
			var item = document.createElement("div");
			var d = new Date();
			var this_timestamp =  d.getFullYear() + "-" + (d.getMonth() + 1)
								+ "-" + d.getDate() + " " + d.getHours() 
								+ ":" + d.getMinutes();
			
			
			item.innerHTML = "<b class='system'>system [" 
							+ this_timestamp + "]:</b> Something has gone wrong." 
							+ " Please reload the page as this could cause unexpected behavior";
			app.all_messages.appendChild(item);
		}//end else/if: what was the result code?
		
		
	});
}//end function: Chat --> joinChat

/**
* Leave the chat system
*
*/
Chat.prototype.leaveChat = function(){
	var app = this;
	ajax.ajaxLeaveChatRoom({
		"unm" : app.unm,
		"chat_id" : app.chat_id
	}).done(function(jsonObj){});
}//end function: Chat --> leaveChat

/**
* makes a call to grab updated information and then
* updates the information accordingly
*/
Chat.prototype.updateChat = function(){
	var app = this;
	ajax.ajaxUpdateChat({
		"unm" : app.unm,
		"cid" : app.chat_id,
		"sid" : app.sid,
		"time" : app.timestamp
	}).done(function(jsonObj){
		if(jsonObj["code"] == 0){
			//display a message
		}else if(jsonObj["code"] == -1){
			window.location = "../dots/";
		}else{
			var time = jsonObj["result"]["timestamp"];
			app.timestamp = (time !== app.timestamp) ? time : app.timestamp;
			app.user_list.innerHTML = "";
			var users = jsonObj["result"]["online_users"];
			
			app.user_amt.innerHTML = ((users.length - 1) > -1) ? users.length - 1 : 0;
			
			for(user in users){	
				if(users[user] !== app.unm){
					app.user_list.appendChild(app.createOnlineUser(users[user]));
				}//end if: is it you?
			}//end for: go through all results
			
			var msgs = jsonObj["result"]["messages"];
			for(msg in msgs){
				var user = msgs[msg]["user"];
				var user_class = (user == "system") ? "system" : "user_msg";
				var item = document.createElement("div");
				var this_timestamp = msgs[msg]["time"];
				var where_ts = this_timestamp.indexOf(".");
				var user_display_time = this_timestamp.substring((where_ts - 3), -10);
				
				item.innerHTML = "<b class='"+user_class+"'>" + user + " [" 
								+ user_display_time + "]:</b> " + msgs[msg]["message"];
				app.all_messages.appendChild(item);
			}//end for: go through all messages we have that are new
			//scroll to bottom of the message div now
			app.all_messages.scrollTop = app.all_messages.scrollHeight;
			
			if(app.name == "lobby"){
				app.parent_app.updatePublicGames(jsonObj["result"]["games"], true);
				app.parent_app.updatePublicGames(jsonObj["result"]["your_games"], false);
				app.parent_app.updateYourChallenges(jsonObj["result"]["challenges"]);
				app.parent_app.updateStats(jsonObj["result"]["stats"]);
			}//end if: is our parent app the lobby?
			
		}//end else/if: was it successful?
	});
}//end function: Chat --> retrieveMsgs


Chat.prototype.createOnlineUser = function(user){
	var app = this;
	var display = '<span onclick="" class="challenge">' 
				 + '<i class="fas fa-gamepad"></i>'
				 + '<span class="username">'+user+'</span></span>' ;
	
	var unm = document.createElement('span');
	unm.className = "username";
	unm.innerHTML = user;
	var li = document.createElement("li");
	li.setAttribute("data-user", user);
	
	
	if(this.allow_challenges){
		var chal = document.createElement('span');
		chal.innerHTML = '<i class="fas fa-gamepad"></i></span>';
		chal.setAttribute('data-user', user);
		chal.className = "challenge";
		
		li.addEventListener('click',function(e){
			app.challengeUser(this.getAttribute('data-user'));
		});
		
		li.appendChild(chal);
	}//end if: is this the lobby's chat?
	
	li.appendChild(unm);
	return li;
}//end function: Chat --> createOnlineUser



Chat.prototype.challengeUser = function(username){
	var app = this;
	var body_tag = document.getElementsByTagName("body")[0];
	var main_div = document.createElement("div");
	main_div.className = "dialog table";
	main_div.id = "challenge_user";
	
	var inner_div = document.createElement("div");
	inner_div.className = "dialog_content";
	inner_div.innerHTML = "<p>Do you want to challenge " + username + "?</p>" 
							+ "<label>Rows:</label> <input type='number' max='50' min='5' value='10' id='rows' /><br/>"
							+ "<label>Cols:</label> <input type='number' max='50' min='5' value='10' id='cols' /><br/>"
							+ "<label>Public:</label> <input type='checkbox' checked id='public_game'><br/>";
	
	var challenge_btn = document.createElement("button");
	challenge_btn.innerHTML = "challenge " + username;
	var cancel_btn = document.createElement("button");
	cancel_btn.innerHTML = "cancel";
	cancel_btn.className = "cancel";
	
	inner_div.appendChild(challenge_btn);
	inner_div.appendChild(cancel_btn);
	
	cancel_btn.addEventListener('click',function(){
		body_tag.removeChild(main_div);
	});
	challenge_btn.addEventListener('click',function(){
		var rows = document.getElementById('rows').value;
		var cols = document.getElementById('cols').value;
		var pub = document.getElementById('public_game').checked;
		app.sendChallenge(username, rows, cols, pub);
		body_tag.removeChild(main_div);
	});
	
	var div_row = document.createElement("div");
	div_row.className = "table-row";
	var div_cell = document.createElement("div");
	div_cell.className = "table-cell";
	
	div_cell.appendChild(inner_div);
	div_row.appendChild(div_cell);
	main_div.appendChild(div_row);
	
	body_tag.appendChild(main_div);
}//end function: Chat --> challengeUser




Chat.prototype.sendChallenge = function(users, rows, cols, pub){
	var app = this;
	ajax.ajaxSendChallenge({
		"challenge" : users,
		"unm" : app.unm,
		"sid" : app.sid,
		"rows": rows,
		"cols": cols,
		"public": pub
	}).done(function(jsonObj){
		if(jsonObj["code"] == 0){
			app.my_message = app.tmp_msg;
		}else if(jsonObj["code"] == -1){
			window.location = "../dots/";
		}else{
			console.log("Challenge has been sent.");
		}//end else/if: hey!
	});
	
}//end function: Chat --> sendChallenge


Chat.prototype.sendMsg = function(){
	var msg = this.my_message.value;
	var app = this;
	this.temp_msg += msg;//store messages incase something is wrong
	
	ajax.ajaxSendMsg({
		"msg" : msg,
		"unm" : app.unm,
		"cid" : app.chat_id,
		"sid" : app.sid
	}).done(function(jsonObj){
		if(jsonObj["code"] == 0){
			app.my_message = app.tmp_msg;
		}else if(jsonObj["code"] == -1){
			window.location = "../dots/";
		}else{
			app.temp_msg = "";
			app.my_message.value = "";
		}//end else/if: hey!
	});
}//end function: Chat --> sendMsg







