<?php
/****************************************************************
* @desc		this file contains basic functionality that are for
*			chat specific calls - lobby or otherwise
* 
* @author	erika tobias (et5392@rit.edu)
* @date		October 18th, 2017
* @updated	December 16th, 2017
****************************************************************/


//include it's dependencies
require("./business/chat.php");

/**
* request to leave the chat room they are in
* @param d {array} list of params we care about
* @return {JSON} response for your leave request in JSON format
*/
function reqLeaveChatRoom($d){
	$ip = $_SERVER['REMOTE_ADDR'];
	$unm = $d["unm"];
	$cid = $d["chat_id"];
	
	if(validateSessionID($ip, $sid)){
		return leaveChatRoom($unm, $cid);
	}else{
		return getResultJSON(-1, "Invalid Session","");
	}//end else/if: is this even a valid session?
	
}//end function: reqLeaveChatRoom

/**
* request to join the chatroom
* @param d {array} list of params we care about
*/
function reqChatRoom($d){
	$ip = $_SERVER['REMOTE_ADDR'];
	$unm = $d['unm'];
	$sid = $d['sid'];
	$chat_id = $d['chat_id'];
	$chat_name = $d['chat_name'];
	$game_id = $d['game_id'];
	
	if(validateSessionID($ip, $sid)){
		return getChatRoom($chat_id, $chat_name, $game_id, $unm);
	}else{
		return getResultJSON(-1, "Invalid Session","");
	}//end else/if: is this even a valid session?
}//end function: reqChatRoom

/**
* request to get data to update the chat
* @param d {array} list of params we care about
* @return {JSON} the information you request in JSON format
*/
function reqUpdateChat($d){
	$ip = $_SERVER['REMOTE_ADDR'];
	$unm = $d['unm'];
	$sid = $d['sid'];
	$chat_id = $d['cid'];
	$timestamp = $d['time'];
	
	if(validateSessionID($ip, $sid)){
		return updateChat($unm, $chat_id, $timestamp);
	}else{
		return getResultJSON(-1, "Invalid Session","");
	}//end else/if: is this even a valid session?
	
}//end function: reqGetMsgs


/**
* Sends a message from a user to the server
* @param d {array} list of params we care about
* @return {JSON} the information for this message send in JSON format
*/
function reqSendMsg($d){
	$ip = $_SERVER['REMOTE_ADDR'];
	$unm = $d['unm'];
	$msg = $d['msg'];
	$sid = $d['sid'];
	$chat_id = $d['cid'];
	
	if(validateSessionID($ip, $sid)){
		//clean up the data
		$message = htmlentities($msg, ENT_QUOTES,'UTF-8');
		$msg = filter_var($message,FILTER_SANITIZE_STRING);
		//post whatever we got at this point - should be good
		return sendMsg($unm, $msg, $chat_id);	
	}else{
		return getResultJSON(-1, "Invalid Session","");
	}//end else/if: is this even a valid session?
}//end function: reqSendMsg


?>