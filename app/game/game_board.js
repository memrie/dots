

/**
* builds a gameboard based on it's game settings
* @param attrs {JSON} list of properties we need
*/
function GameBoard(attrs){
	this.id = attrs.id;//board id
	this.cols = attrs.cols;//width
	this.rows = attrs.rows;//height
	this.squares = [];//all the squares this board has
	this.dots = [];//all the dots this board has
	this.lines = (attrs.lines) ? attrs.lines : Array();
	this.game = attrs.game;//this board belongs to what game?
	
	
	//for making a line
	this.startDrag = false;
	this.endDot = "";//dot we ended on
	this.drawingLine = "";//line we are currently drawing
	this.firstDot = "";//first dot element
	
	//create some standard elements
	this.square_board = document.createElementNS(this.svgns,"g");
	this.lines_board = document.createElementNS(this.svgns,"g");
	this.circles_board = document.createElementNS(this.svgns,"g");
	
	
	this.board = this.buildBoard();
	this.board_wrapper.appendChild(this.board);
	
	
}//end function: GameBoard

GameBoard.prototype.svgns = "http://www.w3.org/2000/svg";
GameBoard.prototype.board_wrapper = document.getElementById('gameboard');


GameBoard.prototype.init = function(){
	var app = this;
	setInterval(function(){
		app.checkForMoves();
	}, 2000);
}


GameBoard.prototype.buildBoard = function(){
	
	var game_board = document.createElementNS(this.svgns, "svg");
	game_board.setAttribute('xmlns', this.svgns);
	game_board.setAttributeNS(null,'version', "1.1");
	game_board.setAttributeNS(null,'id', "dots_game");
	
	var c_w = this.board_wrapper.clientWidth;
	var c_h = this.board_wrapper.clientHeight;

	var total_avail_size = c_h;
	if(c_w > c_h){
		total_avail_size = c_w;
	}//end else/if: is the height/width smallest?
	var size = Math.floor(total_avail_size/(this.cols + 1));
	
	for(var r = 0; r < this.rows; r++){
		this.squares[r] = [];
		for(var c = 0; c < this.cols; c++){
			var id = "square_" + r + "_" + c;
			var sq = new Square({
				'id' : id,
				'row' : r,
				'col' : c,
				'size' : size
			});
			this.squares[r][c] = sq;
			this.square_board.appendChild(sq.getSquare());
			game_board.appendChild(this.square_board);
		}//end for: go through the cols
	}//end for: for through the rows
	
	game_board.appendChild(this.lines_board);
	
	for(var r = 0; r <= this.rows; r++){
		this.dots[r] = [];
		for(var c = 0; c <= this.cols; c++){
			var id = "dot_" + r + "_" + c;
			var dot = new Dot({
				'id' : id,
				'row' : r,
				'col' : c,
				'size' : size
			});
			this.dots[r][c] = sq;
			this.circles_board.appendChild(dot.getDot());
			game_board.appendChild(this.circles_board);
		}//end for: go through the cols
	}//end for: for through the rows
	
	
	var s = this.squares[0][0].getSize();
	game_board.setAttributeNS(null, 'width', (s * this.cols + 20));
	game_board.setAttributeNS(null, 'height',(s * this.rows + 20));
	
	if(!this.game.spectator){
		if(this.game.winner == ""){
			var app = this;
			game_board.addEventListener("mousedown", function(e){
				var ele = e.srcElement || e.target;
				if(e.srcElement){
					app.startLine(ele, e.offsetX, e.offsetY);
				}else{
					//we are more than likely in firefox
					var coors = app.findObjectCoords(this, e);
					app.startLine(ele, coors.x, coors.y);
				}//end else/if: are we in chrome or something?
				
			});
			
			game_board.addEventListener("mousemove", function(e){
				//Firefox can't handle this - find a fix or tell it to fuck itself
				//because even IE is okay with this (go fucking figure)
				if(e.srcElement){
					app.followMouse(e.offsetX, e.offsetY);
				}else{
					var coors = app.findObjectCoords(this, e);
					app.followMouse(coors.x, coors.y);
				}
				
			});
			
			game_board.addEventListener("mouseup", function(e){
				var ele = e.srcElement || e.target;
				app.dropLine(ele);
			});
		}//end if: do we have a winner? (i.e. you can't make moves anyway)
		
	}//end if: do we already have a winner for this game?
	
	return game_board;
}//end function: GameBoard --> buildBoard


/**
* written with help from: http://www.nerdparadise.com/programming/javascriptmouseposition
* and modified by me to work with the svg element
* this is specific for FireFox - since it dislikes OffsetX/Y
* @param ele {SVG} the svg element
* @param mouse_event {Object} the mouse event
*/
GameBoard.prototype.findObjectCoords = function(ele, mouse_event){
	//find out what the difference is between the parentNode's width
	//and the SVGs width
	var width_ele = ele.getAttributeNS(null, "width");
	var w = width_ele.substr(0, width_ele.length);
	var w_2 = ele.parentNode.offsetWidth;
	var actual_width = parseFloat(w_2) - parseFloat(w);
	
	//find out what the difference is between the parentNode's height
	//and the SVGs height
	var height_ele = ele.getAttributeNS(null, "height");
	var h = height_ele.substr(0, height_ele.length);
	var h_2 = ele.parentNode.offsetHeight;

	var actual_height = parseFloat(h_2) - parseFloat(h);
	
	var obj = ele.parentNode;//set this object to the div
	//we need to offset the mouse's position
	//use the height and width difference, 
	//divided by 2 (only care about left side/top - not right and bottom)
	//as a starting point
	var obj_left = actual_width/2;
	var obj_top = actual_height/2;
	var xpos;
	var ypos;
	
	while(obj.offsetParent){
		obj_left += obj.offsetLeft;
		obj_top += obj.offsetTop;
		obj = obj.offsetParent;
	}//end while
	
	if(mouse_event){//FireFox
		//let's do some FireFox magic, since it doesn't like offsetX/Y
		xpos = mouse_event.pageX;
		ypos = mouse_event.pageY;
	}else{//IE
		xpos = window.event.x + document.body.scrollLeft - 2;
		ypos = window.event.y + document.body.scrollTop - 2;
	}//end else/if: are we in FF or IE?
	
	//subtract the difference between where this object is
	//and where we had started
	xpos -= obj_left;
	ypos -= obj_top;
	
	//give the user the CORRECT coordinates
	return {
		"x":xpos,
		"y":ypos
	};
}//end function: GameBoard --> findObjectCoords




GameBoard.prototype.drawLines = function(){
	if(this.lines.length > 0){
		for(var i = 0; i < this.lines.length; i++){
			if(this.lines[i]){
				this.drawLine(this.lines[i]);
			}
			
		}
	}
}



GameBoard.prototype.markSquares = function(wonSquares, player, color){
	var rows = this.squares.length;
	for(var i = 0; i < rows; i++){
		
		var cols = this.squares[i].length;
		for(var n = 0; n < cols; n++){
			
			var amt_won = wonSquares.length;
			var score = document.getElementById(player+"_score");
			score.innerHTML = amt_won;
			for(var x = 0; x < amt_won; x++){
				if(wonSquares[x] !== ""){
					if(this.squares[i][n].id == wonSquares[x]){
						this.squares[i][n].markWon(player,color);
						continue;//go to next iteration
					}//end if: is it the square?
				}
				
			}//end for: go through all won squares
			
		}//end for: go through all squares in row
	}//end for: go through all the rows
	
}//end function: GameBoard --> markSquares




GameBoard.prototype.drawLine = function(line_id){
	if(!document.getElementById(line_id)){
		var dots = line_id.split("|");
		var dot_one = dots[0];
		var dot_two = dots[1];
		
		if(document.getElementById(dot_one)){
			var x1 = document.getElementById(dot_one).getAttributeNS(null, 'cx');
			var y1 = document.getElementById(dot_one).getAttributeNS(null, 'cy');
		
			var x2 = document.getElementById(dot_two).getAttributeNS(null, 'cx');
			var y2 = document.getElementById(dot_two).getAttributeNS(null, 'cy');
			
			this.drawingLine = document.createElementNS(this.svgns, "line");
			this.drawingLine.setAttributeNS(null, 'x1', x1 );
			this.drawingLine.setAttributeNS(null, 'y1', y1);
			this.drawingLine.setAttributeNS(null, 'x2', x2);
			this.drawingLine.setAttributeNS(null, 'y2', y2);
			this.drawingLine.id = dot_one + "|" + dot_two;
			this.drawingLine.setAttributeNS(null, 'stroke', '#34495E');
			this.drawingLine.setAttributeNS(null, 'stroke-width', (this.squares[0][0].getSize()/10) - 2 + "px");
			this.lines_board.appendChild(this.drawingLine);
			this.drawingLine = "";
		}//end if: does the dot even exist?
	}//end if: do we already have this line?
}//end function: GameBoard --> drawLine



GameBoard.prototype.startLine = function(ele, x, y){
	if(ele && ele.tagName.toLowerCase() !== "svg" /**&& this.game.your_turn*/){
		if(ele.tagName.toLowerCase() == "circle"){
			this.startDrag = true;
			this.firstDot = ele;
			this.drawingLine = document.createElementNS(this.svgns, "line");
			this.drawingLine.setAttributeNS(null, 'x1', ele.getAttributeNS(null, 'cx') );
			this.drawingLine.setAttributeNS(null, 'y1', ele.getAttributeNS(null, 'cy') );
			this.drawingLine.setAttributeNS(null, 'x2', x  + "px");
			this.drawingLine.setAttributeNS(null, 'y2', y  + "px");
			
			this.drawingLine.setAttributeNS(null, 'stroke', '#34495E');
			this.drawingLine.setAttributeNS(null, 'stroke-width', (this.squares[0][0].getSize()/10) - 2 + "px");
			this.lines_board.appendChild(this.drawingLine);
		}else{
			this.startDrag = false;
		}//end else/if: are we clicking on a circle?
	}
}//end function: GameBoard --> startLine

GameBoard.prototype.followMouse = function(x, y){
	if(this.startDrag){
		this.drawingLine.setAttributeNS(null, 'x2', x  + "px");
		this.drawingLine.setAttributeNS(null, 'y2', y  + "px");
	}//end if: were we drawing a line?
}//end function: GameBoard --> followMouse

GameBoard.prototype.dropLine = function(ele){
	if(ele && ele.tagName.toLowerCase() !== "svg"){
		if(ele.tagName.toLowerCase() == "circle"){
			var first_id = this.firstDot.id.split("_");
			var second_id = ele.id.split("_");
			var valid = this.validatedLineDrop(parseInt(first_id[1]),parseInt(first_id[2]), parseInt(second_id[1]), parseInt(second_id[2]));
			
			this.drawingLine.setAttributeNS(null, 'x2', ele.getAttributeNS(null, 'cx') );
			this.drawingLine.setAttributeNS(null, 'y2', ele.getAttributeNS(null, 'cy') );
			this.drawingLine.id = this.firstDot.id + "|" + ele.id;
			
			//make sure they aren't adding the same line
			var all_lines = this.lines.length;
			var exists = false;
			for(var l = 0; l < all_lines; l++){
				var l_id = this.lines[l];
				if(l_id == this.firstDot.id + "|" + ele.id || 
					l_id == ele.id + "|" + this.firstDot.id){
					exists = true;
				}
			}//end for: go through all lines we know about
			
			
			if(!valid || exists){
				this.lines_board.removeChild(this.drawingLine);	
			}else{
				this.makeMove(parseInt(first_id[1]),parseInt(first_id[2]), parseInt(second_id[1]), parseInt(second_id[2]));
				//this.lines.push(this.drawingLine);
				//this.checkForSquare(parseInt(first_id[1]),parseInt(first_id[2]), parseInt(second_id[1]), parseInt(second_id[2]));
			}
		}else{
			if(this.drawingLine){
				this.lines_board.removeChild(this.drawingLine);
			}
				
		}//end else/if: is it a circle?
		this.startDrag = false;
		this.firstDot = "";
		//this.drawingLine = "";
		//this.game.updateTurn();
	}
}//end function: GameBoard --> dropLine


//perhaps move this to game instead of gameboard
GameBoard.prototype.makeMove = function(f_row, f_col, l_row, l_col, line){
	var app = this;
	ajax.ajaxMakeMove({
		"unm" : app.game.unm,
		"sid" : app.game.sid,
		"chat_id" : app.game.chat_id,
		"game_id" : app.game.id,
		"move" : line, //the line
		"f_row" : f_row,
		"f_col" : f_col,
		"l_row" : l_row,
		"l_col" : l_col
	}).done(function(jsonObj){
		var code = jsonObj.code;
		var data = jsonObj.result;
		//expect back:
		//valid: true/false,
		//squares_won: [square_0_0]
		
		if(data.valid && code > 0){
			app.lines.push(app.drawingLine.id);
			app.checkForSquare(f_row, f_col, l_row, l_col);
			app.game.your_turn = false;
			app.game.updateTurn();
			
		}else{
			app.lines_board.removeChild(app.drawingLine);
			
			if(code < 0){
				document.location = "/dots/";
			}else{
				var item = document.createElement("div");
				var d = new Date();
				var this_timestamp =  d.getFullYear() + "-" + (d.getMonth() + 1)
									+ "-" + d.getDate() + " " + d.getHours() 
									+ ":" + d.getMinutes();
				
				
				item.innerHTML = "<b class='system'>system [" 
								+ this_timestamp + "]:</b> Invalid move.";
				app.game.chat.all_messages.appendChild(item);
				
			}
			
		}//end else/if
		
		app.drawingLine = "";
		
	});
}//end function: GameBoard --> makeMove




/**
* 
* 
*/
GameBoard.prototype.validatedLineDrop = function(f_row, f_col, l_row, l_col){
	if(f_row == l_row){
		if(f_col + 1 == l_col || f_col - 1 == l_col){
			return true;
		}//end if: it can only be +/- 1 for a col
	}//end if: are they in the same row?
	
	if(f_col == l_col){
		if(f_row + 1 == l_row || f_row - 1 == l_row){
			return true;
		}//end if: it can only be +/- 1 for a row
	}//end if: is it in the same column?
	
	//matched nothing? invlaid
	return false;
}//end function: GameBoard --> validatedLineDrop



/// come back and fix this function....
GameBoard.prototype.checkForSquare = function(f_row, f_col, l_row, l_col){
	var line_coor = {
		frow : f_row,
		fcol : f_col, 
		lrow : l_row, 
		lcol : l_col
	};
	
	if(f_col == 0 && l_col == 0){
		//we only need to check for one square - first column
		this.checkSquares(false, false, false, true, line_coor);
		return true;
	}//end if: first col?
	
	if(f_col == this.cols && l_col == this.cols){
		//we only need to check for one square - last column
		this.checkSquares(false, false, true, false, line_coor);
		return true;
	}//end if: last col?
	
	if(f_row == 0 && l_row == 0){
		//we only need to check for one square - first row
		this.checkSquares(false, true, false, false, line_coor);
		return true;
	}//end if: first row?
	
	if(f_row == this.rows && l_row == this.rows){
		//we only need to check for one square - last row
		this.checkSquares(true, false, false, false, line_coor);
		return true;
	}//end if: last row?
	
	if(f_row == l_row){
		//they are in the same row which means horizontal - check top and bottom squares
		this.checkSquares(true, true, false, false, line_coor);
		return true;
	}//end if: same row?
	
	if(f_col == l_col){
		//they are in the same col which means vertical - check left and right squares
		this.checkSquares(false, false, true, true, line_coor);
		return true;
	}//end if: same column?
	
}//end function: GameBoard --> checkForSquare


/**
* 
* 
*/
GameBoard.prototype.checkSquares = function(t, b, l, r, params){
	var f_row = parseInt(params.frow);
	var f_col = parseInt(params.fcol);
	var l_row = parseInt(params.lrow);
	var l_col = parseInt(params.lcol);
	
	
	if(t){
		var top_left = "dot_" + (f_row - 1) + "_" + f_col;
		var top_right = "dot_" + f_row + "_" + f_col;
		var bottom_left = "dot_" + (l_row - 1) + "_" + l_col;
		var bottom_right = "dot_" + l_row + "_" + l_col;
		var sq = "";
		if(f_col < l_col){
			sq = this.squares[f_row-1][f_col];
		}else{
			sq = this.squares[l_row-1][l_col];
		}
		
		if(!sq.isWon()){
			var res = this.allSides(top_left,top_right,bottom_left,bottom_right);
			if(res){
				sq.markWon("temp", "#B67B9B");
				//this.game.your_squares.push(sq);
			}
		}
	}//end if: are we checking for squares on the top?
	
	if(b){
		var top_left = "dot_" + f_row + "_" + f_col;
		var top_right = "dot_" + (f_row +1) + "_" + f_col;
		var bottom_left = "dot_" + l_row + "_" + l_col;
		var bottom_right = "dot_" + (l_row + 1) + "_" + l_col;
		var sq = "";
		if(f_col < l_col){
			sq = this.squares[f_row][f_col];
		}else{
			sq = this.squares[l_row][l_col];
		}
	
		if(!sq.isWon()){
			var res = this.allSides(top_left,top_right,bottom_left,bottom_right);
			if(res){
				sq.markWon("temp", "#B67B9B");
				//this.game.your_squares.push(sq);
			}
		}
	}//end if: are we checking for squares on the bottom?
	
	if(l){
		//row, col-1, 		row, col	
		//row, col-1		row, col
		
		var top_left = "dot_" + f_row + "_" + (f_col - 1);
		var top_right = "dot_" + f_row + "_" + f_col;
		var bottom_left = "dot_" + l_row + "_" + (l_col-1);
		var bottom_right = "dot_" + l_row + "_" + l_col;
		var sq = "";
		if(f_row < l_row){
			sq = this.squares[f_row][f_col-1];
		}else{
			sq = this.squares[l_row][l_col-1];
		}
		
		if(!sq.isWon()){
			var res = this.allSides(top_left,top_right,bottom_left,bottom_right);
			if(res){
				sq.markWon("temp", "#B67B9B");
				//this.game.your_squares.push(sq);
			}
		}
	}//end if: are we checking for squares on the left?
	
	if(r){
		//row, col		row, col+1
		//row, col		row, col+1
		
		var top_right = "dot_" + f_row + "_" + (f_col + 1);
		var top_left = "dot_" + f_row + "_" + f_col;
		var bottom_right = "dot_" + l_row + "_" + (l_col+1);
		var bottom_left = "dot_" + l_row + "_" + l_col;
		var sq = "";
		if(f_row < l_row){
			var sq = this.squares[f_row][f_col];
		}else{
			var sq = this.squares[l_row][l_col];
		}
		
		if(!sq.isWon()){
			var res = this.allSides(top_left,top_right,bottom_left,bottom_right);
			if(res){
				sq.markWon("temp", "#B67B9B");
				//this.game.your_squares.push(sq);
			}
		}
	}//end if: are we checking for squares on the right?
}//end function: GameBoard --> checkSquares

GameBoard.prototype.allSides = function(top_left,top_right,bottom_left,bottom_right){
	//all possible lines we could have - account for reversed line ids
	var lines = [
		top_left+"|"+top_right,
		top_right+"|"+top_left,
		top_left+"|"+bottom_left,
		bottom_left+"|"+top_left,
		top_right+"|"+bottom_right,
		bottom_right+"|"+top_right,
		bottom_left+"|"+bottom_right,
		bottom_right+"|"+bottom_left
	];
	
	var lines_total = lines.length;
	var track_amt = 0;
	for(var i = 0; i < lines_total; i=i+2){
		if(document.getElementById(lines[i]) || document.getElementById(lines[i+1])){
			track_amt++;
		}
	}//end for: go through all
	
	if(track_amt == 4){
		return true;
	}//end if: if we have 4, it's a square
	
	return false;
}//end function: GameBoard --> allSides

GameBoard.prototype.determineSize = function(){
	var w = this.board_wrapper.clientWidth;
		console.log(w);
		var h = this.board_wrapper.offsetWidth;
		console.log(h);
	var gb = this;
	
	/*
	this.board_wrapper.addEventListener("resize",function(gb){
		var w = this.board_wrapper.clientWidth;
		console.log(w);
		var h = this.board_wrapper.offsetWidth;
		console.log(h);
		console.log("hi");
	});
	*/
	
}







