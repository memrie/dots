<?php
/****************************************************************
* @desc		this file contains basic functionality for the game 
*			specific calls as a player, spectator or otherwise
* 
* @author	erika tobias (et5392@rit.edu)
* @date		November 18th, 2017
* @updated	December 16th, 2017
****************************************************************/

//import it's dependencies
require("./business/game.php");


/**
* grabs info about a give game
* @param d {array} the params we care about
* @return {JSON} the information we care about in JSON format
*/
function reqGetGame($d){
	$ip = $_SERVER['REMOTE_ADDR'];
	$unm = $d['unm'];
	$sid = $d['sid'];
	$id = (int)$d['game_id'];
	
	if(validateSessionID($ip, $sid)){
		return getGame($id, $unm);	
	}else{
		return getResultJSON(-1, "Invalid Session","");
	}//end else/if: is this even a valid session?
}//end function: reqGetGame



/**
* accepts a user's move request
* @param d {array} the information we care about
* @return {JSON} the response to the user's move in JSON format
*/
function reqMakeMove($d){
	$ip = $_SERVER['REMOTE_ADDR'];
	$unm = $d['unm'];
	$line = $d['move'];
	$sid = $d['sid'];
	$f_row = $d['f_row'];
	$f_col = $d['f_col'];
	$l_row = $d['l_row'];
	$l_col = $d['l_col'];
	$gameId = $d['game_id'];
	
	if(validateSessionID($ip, $sid)){
		$valid_frow = ($f_row < 0 || $f_row > 50)? false : true;
		$valid_lrow = ($l_row < 0 || $l_row > 50)? false : true;
		$valid_fcol = ($f_col < 0 || $f_col > 50)? false : true;
		$valid_lcol = ($l_col < 0 || $l_col > 50)? false : true;
		
		//not used now, maybe needed in future 
		//matches the exact line pattern
		//$line_pattern = '/^(dot_){1}[0-9]+(_){1}[0-9]+(\|){1}(dot_){1}[0-9]+(_){1}[0-9]+$/';
		
		if(!$valid_frow || !$valid_lrow || !$valid_fcol || !$valid_lcol){
			return getResultJSON(0, "Invalid move request","");
		}//end if: are they making weird moves?
		//we're good, so send the update request
		return makeMove($unm, $f_row, $f_col, $l_row, $l_col, $gameId);	
	}else{
		return getResultJSON(-1, "Invalid Session","");
	}//end else/if: is this even a valid session?
	
}//end function: reqMakeMove



?>