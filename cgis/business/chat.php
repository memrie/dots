<?php

$HAS_JOINED = "has joined.";
$HAS_LEFT = "has left.";


/**
* Obtain the chat id and name based on whichever one was given.
* @param id {int} the id of the chat room
* @param name {string} the name of the chat room
* @param gid {int} id of the game
* @param unm {string} username of who wants to connect
* @return {JSON} information about the chatroom they are connecting to
*/
function getChatRoom($id, $name, $game_id, $unm){
	$code = 0;
	$desc = "Invalid ChatRoom Request";
	$result = "";
	$conn = new DB();
	
	if(empty($id)){
		$result = "we are not empty";
		$query ="select chat_id, name, public from chat where 
			chat_id = :id or name = :name";
		$params = array(
			"id" => $id,
			"name" => $name
		);
		
		$data = $conn->getData($query, $params);
		$chatroom_id = $data[0]["chat_id"];
	}else{
		$chatroom_id = $id;
	}//end else/if: were we given a chatroom id or not?
	
	
	$chatroom_name = (empty($data[0]["name"])) ? "game chat" : $data[0]["name"];
	$user_id = getUserId($conn, $unm);
	
	

	if(joinChatRoom($conn, $unm, $user_id, $chatroom_id)){
		$time = obtainTimestamp($conn, $chatroom_id, $user_id);
		$code = 1;
		$desc = "Joined ChatRoom {$chatroom_name} - $time";
		$result = array(
			"id" => $chatroom_id,
			"name" => $chatroom_name,
			"timestamp" => $time
		);
	}//end if: did we get a good result?
		

	$conn->closeConnection();
	$conn = null;
	return getResultJSON($code, $desc, $result);
}//end function: getChatRoom


/**
* Joins a user to a given chat room
* @param conn {object} the database connection object
* @param unm {string} username of the person joining chat room
* @param uid {int} this user's id
* @param chat_id {int} the id of the chatroom we want
*/
function joinChatRoom($conn, $unm, $user_id, $chatroom_id){
	global $HAS_JOINED;
	
	$params = array(
		"uid"=>$user_id, 
		"cid"=>$chatroom_id
	);
	
	//fix an issue where on refresh it caused the chat
	//to break because the user hadn't been deleted
	$query = "select user_id from chat_users
				where user_id=:uid and chat_id=:cid";
	$res = $conn->getData($query, $params);
	
	
	//only add the user if they don't already exist
	//Fixes Refresh Bug
	if(count($res) == 0){
		//return "we are in the insert statement";
		$query = "insert into chat_users values(:uid,:cid)";
		$conn->setData($query, $params);
	}//if: are they still in the table?
	
	return postMessage($conn, $user_id, "$unm $HAS_JOINED", $chatroom_id);
	if(postMessage($conn, $user_id, "$unm $HAS_JOINED", $chatroom_id)){
		return true;
	}//end if: did we post the joined message?
	
	return false;
}//end function: joinChatRoom


/**
* Obtains a timestamp of the last message from a given user&chatroom
* @param conn {object} the database connection object
* @param chat_id {int} the id of the chatroom we want
* @param uid {int} this user's id
* @return {timestamp} the last timestamp
*/
function obtainTimestamp($conn, $chat_id, $user_id){
	$query = 'select timestamp from chat_messages where chat_id=:cid and user_id=:uid order by timestamp desc limit 1';
	$params = array(
		"cid" => $chat_id,
		"uid" => $user_id
	);
	//return "$chat_id, $user_id";
	$res = $conn->getData($query,$params);
	//return $conn->getError();
	//return count($res);
	$date = date_create();
	
	return ($res[0]["timestamp"])?$res[0]["timestamp"]: date_timestamp_get($date);;
}//end function: obtainTimestamp

/**
* Removes a user from the current chat room
* @param unm {string} username of the person leaving the chat room
* @param chat_id {int} the id of the chatroom we want
* @return {boolean} whether or not we left chat room
*/
function leaveChatRoom($unm, $chatroom_id){
	global $HAS_LEFT;
	$code = 0;
	$desc = "Invalid ChatRoom Request";
	$result = "";
	$conn = new DB();
	$user_id = getUserId($conn, $unm);
	
	
	if(postMessage($conn, $user_id, "$unm $HAS_LEFT", $chatroom_id)){
		$code = 1;
		$desc = "You have left this chat room.";
	}//end if: did we post the left message?
	
	
	$query = "delete from chat_users where user_id = :uid and chat_id=:cid";
	$params = array(
		"uid" => $user_id,
		"cid" => $chatroom_id
	);
	
	$conn->setData($query,$params);
	
	
	$conn->closeConnection();
	$conn = null;
	return getResultJSON($code, $desc, $result);
	
}//end function: leaveChatRoom


/**
* Posts a message to the chat's messages list
* @param unm {string} username of the person sending messages
* @param msg {string} the message to post
* @param chat_id {int} the id of the chatroom we want
* @return {boolean} whether or not message posted
*/
function sendMsg($unm, $msg, $chat_id){
	$code = 0;
	$desc = "Invalid ChatRoom Request";
	$result = "";
	
	$conn = new DB();
	$user_id = getUserId($conn, $unm);
	
	if(postMessage($conn, $user_id, $msg, $chat_id)){
		$code = 1;
		$desc = "message posted";
	}
	
	$conn->closeConnection();
	$conn = null;
	return getResultJSON($code, $desc, $result);
}//end function: sendMsg


/**
* posts a message
* DB column updated - TIMESTAMP(6) - fix primary key violation on refresh
* @param conn {object} the database connection object
* @param user_id {int} this user's id
* @param msg {string} the message to post
* @param chat_id {int} the id of the chatroom we want
* @return {boolean} whether or not message posted
*/
function postMessage($conn, $user_id, $msg, $chat_id){
	$query = "insert into chat_messages(user_id, chat_id, message) values(:uid,:cid,:msg)";
	$params = array(
		"uid" => $user_id,
		"cid" => $chat_id,
		"msg" => $msg
	);
	
	//return "$user_id, $msg, $chat_id";
	$conn->setData($query, $params);
	
	//return $conn->getAffectedRows();
	
	if($conn->getAffectedRows() == 1){
		return true;
	}//end if: did we add the single message?
	return false;
}//end function: postMessage


/**
* Gathers all information needed to update the chat window
* @param unm {string} username of the person grabbing chat messages
* @param chat_id {int} the id of the chatroom we want
* @return timestamp {timestamp} the last timestamp
*/
function updateChat($unm, $chat_id, $timestamp){
	$conn = new DB();
	$code = 1;
	$desc = "you are online";
	$new_timestamp = $timestamp;
	
	$uid = getUserId($conn,$unm);
	
	$users = getOnlineUsers($conn, $chat_id);
	$msgs = getMessages($conn, $timestamp, $chat_id, $unm);
	$msgs_amt = count($msgs);
	if($msgs_amt > 0){
		$new_timestamp = $msgs[$msgs_amt-1]['time'];
	}//end if: do we have any new messages?
	
	
	$public_games = getPublicGames($conn);
	$your_games = getYourOngoingGames($conn, $uid);
	$challenges = getYourChallenges($conn, $uid);
	$stats = getUserGameStats($conn,$uid);
	
	$conn->closeConnection();
	$conn = null;
	
	$result = array(
		"online_users" => $users,
		"timestamp" => $new_timestamp,
		"messages" => $msgs,
		"games"=>$public_games,
		"your_games"=>$your_games,
		"challenges"=>$challenges,
		"stats"=>$stats
	);
	
	return getResultJSON($code, $desc, $result);
}//end function: updateChat

/**
* Gather all the new messages (if any)
* based on the chat id and the timestamp
* @param conn {object} the database connection object
* @param timestamp {date} the timestamp of the last message
* @param chat_id {int} id of the chatroom
* @param unm {string} username of the person grabbing chat messages
* @reutnr {JSON} list of all messages fitting criteria
*/
function getMessages($conn, $timestamp, $chat_id, $unm){
	global $HAS_JOINED;
	global $HAS_LEFT;
	$query = 'select username, timestamp, message from chat_messages
			join user using(user_id) where timestamp > :timestamp and chat_id=:cid
			order by timestamp';
	$params = array(
		"timestamp" => $timestamp,
		"cid" => $chat_id
	);
	$results = $conn->getData($query, $params);
	$data = array();
	for($i = 0; $i < count($results); $i++){
		$u = $results[$i]['username'];
		$m = $results[$i]['message'];
		if($unm == $u){
			$u = "You";
		}//end if: is it you?
		
		if($m == "$u $HAS_LEFT" or $m == "$u $HAS_JOINED"){
			$u = "system";
		}//end if: is it just a notice from the system?
		
		$data[$i]["user"] = $u;
		$data[$i]["time"] = $results[$i]['timestamp'];
		$data[$i]["message"] = $m;
	}//end for: go through all the usernames for users in this chat
	
	return $data;
}//end function: getMessages

/**
* Gather all users who are online in this chatroom
* @param conn {object} the database connection object
* @param chat_id {int} the id of the chatroom we want
* @return {JSON} list of users online in this chat room
*/
function getOnlineUsers($conn, $chat_id){
	$query = "select username from user join chat_users using(user_id) where chat_id = :cid";
	$params = array("cid"=>$chat_id);
	$results = $conn->getData($query, $params);
	$data = array();
	
	for($i = 0; $i < count($results); $i++){
		$data[$i] = $results[$i]['username'];
	}//end for: go through all the usernames for users in this chat
	
	return $data;
}//end function: getOnlineUsers



?>


