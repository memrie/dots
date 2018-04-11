
/**
* @constructor 
*
*/
function Lobby(){
	
	this.challenges = [];
	this.init();
}//end function: Lobby

//places items are listed
Lobby.prototype.games_list = document.getElementById("games-list");
Lobby.prototype.your_games_list = document.getElementById("your-games-list");
Lobby.prototype.challenges_list = document.getElementById("challenges-list");
Lobby.prototype.users_list = document.getElementById("users-list");
//display the counts 
Lobby.prototype.user_amt = document.getElementById("users_amt");
Lobby.prototype.your_game_amt = document.getElementById("your_game_amt");
Lobby.prototype.pub_game_amt = document.getElementById("pub_game_amt");
Lobby.prototype.chlng_amt = document.getElementById("chlng_amt");

//your user stats for games
Lobby.prototype.won_games = document.getElementById("won_games");
Lobby.prototype.lost_games = document.getElementById("lost_games");
Lobby.prototype.ongoing_games = document.getElementById("ongoing_games");
Lobby.prototype.total_games = document.getElementById("total_games");


/**
* update this user's stats 
* @param stats {JSON} the stats to display
*/
Lobby.prototype.updateStats = function(stats){
	this.won_games.innerHTML = stats.won;
	this.lost_games.innerHTML = stats.lost;
	this.ongoing_games.innerHTML = stats.ongoing;
	this.total_games.innerHTML = stats.total;
	
}//end function: updateStats

/**
* handles the min/max of the different user controls
* on the right hand side of the screen
*/
Lobby.prototype.maxMinEvent = function(){
	var items = document.getElementsByClassName("min-max");
	var item_amt = items.length;
	
	for(var i = 0; i < item_amt; i++){
		items[i].addEventListener("click",function(e){
			var max = '<i class="far fa-plus"></i>';
			var min = '<i class="far fa-minus"></i>';
			
			var current_mode = this.getAttribute("data-mode");
			this.innerHTML = "";
			this.innerHTML = (current_mode == "max") ? max : min;
			var next_mode = (current_mode == "min") ? "max" : "min";
			this.setAttribute("data-mode", next_mode);
			
			var ele = document.getElementById(this.getAttribute("data-show"));
			var cur_display = ele.style.display;
			var display_setting = (cur_display == "block" || cur_display == "") ? "none" : "block";
			ele.style.display = display_setting;
		});//end addEventListener
		
	}//end for: go through all elements
}//end function: Lobby --> maxMinEvent

/**
* Initialize this app
*/
Lobby.prototype.init = function(){
	this.maxMinEvent();
	var data = JSON.parse(cookie.getCookie("dots"));
	this.unm = data.unm;
	this.sid = data.sid;
	
	this.chat = new Chat("","lobby", this.users_list, true, this);
	
}//end function: Lobby --> init

/**
* Updates the list of public games OR your games
* @param games {JSON} the list of games
* @param public_games {boolean} is this public games? false = your games
*/
Lobby.prototype.updatePublicGames = function(games, public_games){
	var list = "";
	var amt = games.length;
	var your_turn = false;
	
	if(public_games){
		list = this.games_list;
		this.pub_game_amt.innerHTML = amt;
	}else{
		list = this.your_games_list;
		this.your_game_amt.innerHTML = amt;
	}//end else/if: your games or just public ones?
	
	list.innerHTML = "";
	if(amt == 0){
		list.innerHTML = "No games at this time.";
		return false;//don't bother with anything else
	}//end if: do we even have any?
	
	for(game in games){
		var game_id = games[game].id;
		var your_turn = (games[game].your_turn) ? "<i class='your_turn'>-- your turn</i>" : "";
		
		var li = document.createElement("li");
		li.innerHTML = "#" + game_id + " " + games[game].name + " " + your_turn;
		li.id = game_id;
		li.addEventListener("click", function(e){
			document.location = "./game.php?id=" + this.id;
		});
		list.appendChild(li);
	}//end for: go through all public games
	
}//end function: Lobby --> updatePublicGames

/**
* Update your list of challenges
* @param challenges {JSON} the list of challenges (all)
*/
Lobby.prototype.updateYourChallenges = function(challenges){
	this.challenges = challenges;
	this.challenges_list.innerHTML = "";
	var amt = this.challenges.length;
	this.chlng_amt.innerHTML = amt;
	if(amt == 0){
		this.challenges_list.innerHTML = "<i>No challenges at this time.</i>";
		return false;//don't bother with anything else
	}
	
	for(challenge in challenges){
		var li = document.createElement("li");
		var txt = challenges[challenge].challenger + " has challenged you";
		var amt = challenges[challenge].challenged.length;
		
		if(amt > 1){
			txt += " and " + (amt - 1) + " other(s).";
		}else{
			txt += ".";
		}//end else/if: do we have more than you challenged?
		
		var details = document.createElement("span");
		details.setAttribute("data-challenge-id", challenges[challenge].id);
		details.innerHTML = "view";
		
		var app = this;
		details.addEventListener("click",function(e){
			app.getChallengeRequest(this.getAttribute("data-challenge-id"));
		});
		
		li.innerHTML = txt + " | ";
		li.appendChild(details);
		
		
		this.challenges_list.appendChild(li);
	}//end for: go through all public games
	
}//end function: Lobby --> updateYourChallenges


/**
* Obtains the details of the request challenge request
* @param cid {number} the id of this challenge request
*/
Lobby.prototype.getChallengeRequest = function(cid){
	var app = this;
	ajax.ajaxGetChallenge({
		"cid" : cid,
		"unm" : app.unm,
		"sid" : app.sid
	}).done(function(jsonObj){
		if(jsonObj["code"] == 0){
			var item = document.createElement("div");
			var d = new Date();
			var this_timestamp =  d.getFullYear() + "-" + (d.getMonth() + 1)
								+ "-" + d.getDate() + " " + d.getHours() 
								+ ":" + d.getMinutes();
			
			
			item.innerHTML = "<b class='system'>system [" 
							+ this_timestamp + "]:</b> Failed to obtain challenge.";
			app.chat.all_messages.appendChild(item);

		}else if(jsonObj["code"] == -1){
			document.location = "../dots/";
		}else{
			var data = jsonObj["result"];
			
			app.displayChallenge(data.id, data.challenger, data.rows +"x"+ data.cols, data["public"], "");
		}//end else/if: hey!
	});//end ajax - done
	
}//end function: Lobby --> getChallengeRequest


/**
* Displays the details of a clicked on challenge
* @param cid {number} the challenge request id
* @param challenger {string} the username of who challenged you
* @param board {string} dimensions of the board
* @param is_public {boolean} is a public/private game
* @param players {JSON} array of all who were challenged and whether responded or not
* @see getChallengeRequest
*/ 
Lobby.prototype.displayChallenge = function(cid, challenger, board, is_public, players){
	var app = this;
	var body_tag = document.getElementsByTagName("body")[0];
	var main_div = document.createElement("div");
	main_div.className = "dialog table";
	main_div.id = "challenge_user";
	
	var public_or_private = (is_public) ? "public" : "private";
	
	var inner_div = document.createElement("div");
	inner_div.className = "dialog_content";
	inner_div.innerHTML = "<p>You have been challenged by " + challenger + "</p>" 
							+ "Board Size: " + board + "<br/>"
							+ "This game will be " + public_or_private + ".<br/><br/>";
	
	var accept_btn = document.createElement("button");
	accept_btn.innerHTML = "accept";
	
	var decline_btn = document.createElement("button");
	decline_btn.innerHTML = "decline";
	
	var cancel_btn = document.createElement("button");
	cancel_btn.innerHTML = "cancel";
	cancel_btn.className = "cancel";
	
	inner_div.appendChild(accept_btn);
	inner_div.appendChild(decline_btn);
	inner_div.appendChild(cancel_btn);
	
	cancel_btn.addEventListener('click',function(){
		body_tag.removeChild(main_div);
	});
	
	accept_btn.addEventListener('click',function(){
		app.respondToChallenge(cid, true);
		body_tag.removeChild(main_div);
	});
	
	decline_btn.addEventListener('click',function(){
		app.respondToChallenge(cid, false);
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
	
}//end function: Lobby --> displayChallenge


/**
* Sends you response to a challenge
* @param cid {number} the challenge id you are responding to
* @param accept {boolean} did you accept it? false = reject
*/
Lobby.prototype.respondToChallenge = function(cid, accept){
	var app = this;
	ajax.ajaxRespondChallenge({
		"cid" : cid,
		"unm" : app.unm,
		"sid" : app.sid,
		"accept" : accept
	}).done(function(jsonObj){
		if(jsonObj["code"] == 0){
			var item = document.createElement("div");
			var d = new Date();
			var this_timestamp =  d.getFullYear() + "-" + (d.getMonth() + 1)
								+ "-" + d.getDate() + " " + d.getHours() 
								+ ":" + d.getMinutes();
			
			
			item.innerHTML = "<b class='system'>system [" 
							+ this_timestamp + "]:</b> Failed to respond to challenge. Please try again.";
			app.chat.all_messages.appendChild(item);
		}else if(jsonObj["code"] < 0){
			document.location = "../dots/";
		}else{
			var data = jsonObj["result"];
		}//end else/if: hey!
	});
}//end function: Lobby --> respondToChallenge






