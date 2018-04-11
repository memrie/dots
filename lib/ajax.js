

var ajax = {
	ajaxCall:function(method, data){
		return $.ajax({
			type: method,
			async: true,
			cache:false,
			url: "./cgis/mid.php",
			data: data,  
			dataType: "json"
		});
	},
	ajaxLogin:function(loginReqest){
		return ajax.ajaxCall("POST",{
			method:"reqLogin",
			data:loginReqest});
	},
	ajaxSignup:function(signupRequest){
		return ajax.ajaxCall("POST",{
			method:"reqSignup",
			data:signupRequest});
	},
	ajaxGetChatRoom:function(msgsRequest){
		return ajax.ajaxCall("GET",{
			method:"reqChatRoom",
			data:msgsRequest});
	},
	ajaxUpdateChat:function(msgsRequest){
		return ajax.ajaxCall("GET",{
			method:"reqUpdateChat",
			data:msgsRequest});
	},
	ajaxSendMsg:function(msgRequest){
		return ajax.ajaxCall("POST",{
			method:"reqSendMsg",
			data:msgRequest});
	},
	ajaxLeaveChatRoom:function(msgRequest){
		return ajax.ajaxCall("POST",{
			method:"reqLeaveChatRoom",
			data:msgRequest});
	},ajaxSendChallenge:function(chlngRequest){
		return ajax.ajaxCall("POST",{
			method:"reqSendChallenge",
			data:chlngRequest});
	},ajaxRespondChallenge:function(chlngRequest){
		return ajax.ajaxCall("POST",{
			method:"reqRespondChallenge",
			data:chlngRequest});
	},ajaxGetChallenge:function(chlngRequest){
		return ajax.ajaxCall("GET",{
			method:"reqGetChallenge",
			data:chlngRequest});
	},ajaxGetGame:function(gameRequest){
		return ajax.ajaxCall("GET",{
			method:"reqGetGame",
			data:gameRequest});
	},
	ajaxMakeMove:function(moveRequest){
		return ajax.ajaxCall("POST",{
			method:"reqMakeMove",
			data:moveRequest});
	},
	ajaxLogoutUser:function(logoutRequest){
		return ajax.ajaxCall("POST",{
			method:"reqLogout",
			data:logoutRequest});
	}
	
}