/************************************************
* @desc		handles a game instance
* 
* @date		October 30th, 2017
* @author	erika tobias [et5392@rit.edu]
************************************************/

/**
* @constructor
* @param id {int} the id of the game we want
*/
function Game(id){
	this.id = parseInt(id);
	this.board_attrs = {};
	
	//are you playing or watching?
	this.spectator = true;
	
	//other elements
	this.chat = null;
	this.gameboard = null;
	
	//game specific
	this.total_squares = 0;
	this.your_health = null;
	this.your_squares = [];
	this.opponents = [];
	this.your_player = 0;
	this.opp_health = null;
	this.opp_squares = [];
	this.opp_squares = [];
	this.opp_squares = [];
	this.dots = [];
	this.your_turn = true;
	this.winner = "";
	
	this.init();
}//end function: Game

//constants which will not change
Game.prototype.full_heart = '<i class="fas fa-heart"></i>';
Game.prototype.pulse_heart = '<i class="fas fa-heart pulse2"></i>';
Game.prototype.empty_heart = '<i class="far fa-heart"></i>';
Game.prototype.your_score_place = document.getElementById("you_score");
Game.prototype.your_health_palce = document.getElementById("your_health");
Game.prototype.opp_health_palce = document.getElementById("opp_health");
Game.prototype.opp_score_place = document.getElementById("opp_score");
Game.prototype.whos_turn = document.getElementById("whos_turn");
Game.prototype.turn_msg = document.getElementById("msg");
Game.prototype.colors = ["#E57E89","#9CDBD3","#DBA16E","#997BB6"];

/**
* Initialize this game
*
*/
Game.prototype.init = function(){
	var data = JSON.parse(cookie.getCookie("dots"));
	this.unm = data.unm;
	this.sid = data.sid;
	this.chat_id = "";
	this.chat = "";

	this.getGameDetails(true);
	var app = this;
	setInterval(function(){
		app.getGameDetails(false);
	}, 2000);
	
}//end function: Game --> init

/**
* Grab the game details such as turn, opponent, squares and lines
* @param init {boolean} whether or not this is an update or initializing
*/
Game.prototype.getGameDetails = function(init){
	var app = this;
	this.board_attrs['game'] = this;
	ajax.ajaxGetGame({
		"unm" : app.unm,
		"sid" : app.sid,
		"game_id" : app.id,
	}).done(function(jsonObj){
		var data = jsonObj.result;
		var code = jsonObj.code;
		if(code > 0){//looks good, lets call it
			app.setGame(jsonObj, init);
		}else if(code == 0){
			//redirect to lobby
			document.location = "./chat.php";
		}else if(code < 0){
			//redirect to login - you have an invalid session
			document.location = "../dots/";
		}//end else/if: did we get a good code back?
	});//end ajax call
	
}//end function: Game --> getGameDetails

/**
* Sets the game details for this session whether an update or initializing
* @param jsonObj {JSON} the game details we care about
* @param init {boolean} whether or not this is an initialization
*/
Game.prototype.setGame = function(jsonObj, init){
	var data = jsonObj.result;
	this.your_turn = data.your_turn;
	if(init){
		
		if(data["chat_id"]){
			var u = document.getElementById("users");
			this.chat_id = data["chat_id"];
			//, user_list, allow_challenges, parent_app)
			this.chat = new Chat(data["chat_id"],"",u, false, this);
		}//end chat_id
		
		document.title = "dots - #" + this.id + " " + data.name;
		
		if(!data['spectator']){
			
			this.spectator = false;
		}//do we have this element?
		
		var rows = parseInt(data.rows);
		var cols = parseInt(data.cols);
		this.gameboard = new GameBoard({
			'id' : this.id,
			'rows': rows,
			'cols' : cols,
			'squares' : "",
			'lines' : data.lines,
			'game' : this
		});
		this.your_player = data.player_you;
		this.total_squares = rows * cols;
		
		var p_unm = document.getElementById("player"+this.your_player+"_unm");
		if(p_unm){
			p_unm.innerHTML = "You: ";
		}//end if
		
	}//end if: are we initializing this game?
	
	//handle lines
	this.gameboard.lines = data.lines;
	this.gameboard.drawLines();
	
	if(!data['spectator']){
		this.updateTurn();
		this.spectator = false;
		this.your_squares = data.your_squares;
		this.gameboard.markSquares(this.your_squares, "player"+this.your_player, this.colors[this.your_player-1]);
	}//end if: do we even have the spectator option?
	
	this.opponents = data.opponents;
	
	this.winner = (data.winner && data.winner !== "" && data.winner !== null) ? data.winner : "";
	if(this.winner !== ""){
		this.displayWinner();
	}//end if: is there a winner?
	
	//let's set up the players areas - scores and names
	var players = this.opponents.length;
	for(var opp in this.opponents){
		var p = this.opponents[opp].player;
		this.gameboard.markSquares(this.opponents[opp].squares, opp, this.colors[p-1]);
		if(init){
			var p_unm = document.getElementById(opp+"_unm");
			if(p_unm){
				p_unm.innerHTML = this.opponents[opp].username + ": ";
			}//end if
		}//end if: is this the game loading first time?
		
		if(this.spectator && this.opponents[opp].turn){
			this.whos_turn.innerHTML = this.opponents[opp].username + "'s ";
			this.turn_msg.innerHTML = "turn";
		}//end if: spectator? this user's turn?
	}//end function: go through all players we have
	
}//end function: setGame

/**
* Displays a winner where it shows who's turn it is, normally
* Only if there is a winner
*/
Game.prototype.displayWinner = function(){
	this.whos_turn.innerHTML = this.winner;
	this.turn_msg.innerHTML = "has won.";
	//document.getElementById("turn").innerHTML = this.winner + " has won.";
	document.getElementById("game_over").style.display = "block";
	if(this.winner == this.unm){
		this.whos_turn.innerHTML = "You";
		this.turn_msg.innerHTML = "have won.";
	}//end if: is the winner you?
}//end function: Game --> displayWinner


/**
* Updates turn of the game
*/
Game.prototype.updateTurn = function(){
	if(this.whos_turn){
		this.whos_turn.innerHTML = (this.your_turn) ? "Your" : "Opponent's";
		this.turn_msg.innerHTML = "turn";
	}
}//end function: Game --> updateTurn




