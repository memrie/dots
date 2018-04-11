

/**
* Creates a Chat Instance
* @constructor
*/
function Chat(id, name){
	//if we have an id set it, otherwise default to lobby
	this.chat_id = id ? id : 00000000001;
	this.name = name ? name : "lobby";
}//end function: Chat

//default is "0" --> will be updated later in "updateChat"
Chat.prototype.timestamp = 0;


Chat.prototype.init = function(){
	this.my_message = document.getElementById("your_message");
	this.all_messages = document.getElementById("messages-list");
	this.message_button = document.getElementById("send_your_message");
	
	var data = JSON.parse(cookie.getCookie("dots"));
	console.log(data);
	
	
	var app = this;
	
	this.message_button.addEventListener("click",function(){
		app.sendMsg();
	});
	
	setInterval(function(){
		app.retrieveMsgs();
	}, 2000);
}//end function: Chat --> init



Chat.prototype.updateChat = function(){
	var app = this;
	
	ajax.ajaxGetMsgs({
		"unm" : unm,
		"sid" : "",
		"timestamp" : app.timestamp
	}).done(function(jsonObj){
		if(jsonObj["code"] == 0){
			console.log("something is wrong");
		}else if(jsonObj["code"] == -1){
			//session has expired
			console.log("the session is not valid");
		}else{
			var results = jsonObj["result"];
			for(res in results){
				console.log(res);
			}//end for: go through all results
		}//end else/if:
	});
}//end function: Chat --> retrieveMsgs


Chat.prototype.sendMsg(){
	var msg = this.my_message.value;
	
	ajax.ajaxSendMsg({
		"msg" : msg,
		"sid" : ""
	}).done(function(jsonObj){
		if(jsonObj["code"] == 0){
			console.log("Something isn't right here...");
		}else if(jsonObj["code"] == -1){
			console.log("Your session has expired");
		}else{
			console.log("We have sent the message");
		}//end else/if: hey!
	});
}//end function: Chat --> sendMsg







