


function Dot(attrs){
	this.id = attrs.id;
	this.size = parseInt(attrs.size);
	this.x = (attrs.col * this.size);
	this.y = (attrs.row * this.size);
	
	this.dot = this.buildDot();
}



Dot.prototype = {
	units : "px",
	svgns : "http://www.w3.org/2000/svg",
	buildDot: function(){
		var dot = document.createElementNS(this.svgns, "circle");
		dot.setAttributeNS(null,'cx',this.x + 10 + this.units);
		dot.setAttributeNS(null,'cy',this.y + 10 + this.units);
		dot.setAttributeNS(null,'r', (this.size/10) + this.units);
		dot.setAttributeNS(null,'fill',"#2E4053");
		dot.setAttributeNS(null,'id',this.id);
		
		
		
		return dot;
	},
	getDot: function(){
		return this.dot;
	}
}//end Dot --> prototype