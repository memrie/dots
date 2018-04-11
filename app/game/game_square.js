


function Square(attrs){
	this.id = attrs.id;
	this.size = parseInt(attrs.size);
	this.x = (attrs.col * this.size);
	this.y = (attrs.row * this.size);
	this.won = false;
	this.player_id = "";
	
	this.square = this.buildSquare();
}

Square.prototype = {
	units : "px",
	svgns : "http://www.w3.org/2000/svg",
	buildSquare: function(){
		var sq = document.createElementNS(this.svgns, "rect");
		sq.setAttributeNS(null,'x',this.x + 10 + this.units );
		sq.setAttributeNS(null,'y',this.y  + 10 + this.units);
		sq.setAttributeNS(null,'width',this.size + this.units);
		sq.setAttributeNS(null,'height',this.size + this.units);
		sq.setAttributeNS(null,'fill',"#fff");
		sq.setAttributeNS(null,'id',this.id);
		return sq;
	},
	getSquare : function(){
		return this.square;
	},
	getSize : function(){
		return this.size;
	},
	markWon: function(player,color){
		this.won = true;
		this.player_id = player;
		this.square.setAttributeNS(null, "fill",color);
	},
	isWon: function(){
		return this.won;
	}
}//end prototype

