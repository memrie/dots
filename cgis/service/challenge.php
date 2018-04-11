<?php
/****************************************************************
* @desc		this file contains the basic calls for the challenge
*			system of the dots application
* 
* @author	erika tobias (et5392@rit.edu)
* @date		December 15th, 2017
* @updated	December 16th, 2017
****************************************************************/
require("./business/challenge.php");



/**
* sends a challenge from one user to another user/users
* @param d {array} list of parameters we care about
* @return {JSON} the response from the request as JSON
*/
function reqSendChallenge($d){
	$ip = $_SERVER['REMOTE_ADDR'];
	$from = $d['unm'];
	$users = $d['challenge'];
	$sid = $d['sid'];
	$rows = $d["rows"];
	$cols = $d["cols"];
	$public = $d["public"];
	
	if(validateSessionID($ip, $sid)){
		//technically the system can handle larger rows x cols BUT
		//lets restrict to 50x50, after all... that IS 2500 squares...
		$valid_rows = ($rows > 50 || $rows < 5) ? false : true;
		$valid_cols = ($cols > 50 || $cols < 5) ? false : true;
		
		if(!$valid_rows || !$valid_cols){
			return getResultJSON(0, "Invalid challenge request.","");
		}//end if: did they give us a bad row?		
		return addChallenge($from, $cols, $rows, $users, $public);
	}else{
		return getResultJSON(-1, "Invalid Session","");
	}//end else/if: is this even a valid session?
}//end function: reqSendChallenge

/**
* Obtain information about the challenge
* @param d {array} the list of params we care about
* @return {JSON} the response as JSON
*/
function reqGetChallenge($d){
	$ip = $_SERVER['REMOTE_ADDR'];
	$unm = $d['unm'];
	$cid = $d['cid'];
	$sid = $d['sid'];
	
	if(validateSessionID($ip, $sid)){
		//clean up the data
		
		$conn = new DB();
		$uid = getUserId($conn, $unm);
		
		$code = 0;
		$desc = "invalid challenge request";
		$result = "";
		
		$result = getAChallenge($conn, $cid, $uid);
		if(count($result) > 0){
			$code = 1;
			$desc = "successfully located challenge.";
		}//end if: did we get one?
		
		$conn->closeConnection();
		$conn = null;
		return getResultJSON($code, $desc, $result);
	}else{
		//log out
		
		//invalid
		return getResultJSON(-1, "Invalid Session","");
	}//end else/if: is this even a valid session?
	
}//end function: reqGetChallenge


/**
* allows the user to respond to a challenge
* @param $d {array} list of params we care about
* @return {JSON} the response, formatted as JSON
*/
function reqRespondChallenge($d){
	$ip = $_SERVER['REMOTE_ADDR'];
	$accept = $d['accept'];
	$unm = $d['unm'];
	$cid = $d['cid'];
	$sid = $d['sid'];
	
	//check for valid session
	if(validateSessionID($ip, $sid)){
		return repsondToChallenge($cid, $unm, $accept);
	}else{
		return getResultJSON(-1, "Invalid Session","");
	}//end else/if: is this even a valid session?
	
}//end function: reqRespondChallenge



?>