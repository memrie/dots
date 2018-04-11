<?php

/****************************************************************
* @desc		this file contains basic functionality that can
*			be used across several backend calls
* 
* @author	erika tobias (et5392@rit.edu)
* @date		October 18th, 2017
* @updated	December 16th, 2017
****************************************************************/



/**
* Validates a session id token
* @param ip {string} the ip that made the request
* @param sid {string} the session id that we were given
* @return {boolean} whether or not it's a valid token/session
*/
function validateSessionID($ip, $sid){
	$details = extractToken($sid);
	if($details == false){
		return false;
	}//end if: details == false means you messed with it
	
	
	$earlier = new DateTime();
	//set timestamp this way
	//it suddenly stopped working 12/18/17 to pass into DateTime
	$earlier->setTimestamp($details["dateTime"]);
	$now = new DateTime("now");
	$diff = $now->diff($earlier);
	$hours = $diff->h;
	
	if($details["ip"] == formatIP($ip) && $hours < 24){
		return true;
	}//end if: does the ip match? session over 24 hours old?
	
	return false;
}//end function: validateSessionID



/**
* takes an ip and formates it how it is in the token
* @param ip {string} the ip address
* @return {string} the ip address in the same format as the token
*/
function formatIP($ip){
	$ip_split = preg_split('/\./', $ip, -1, PREG_SPLIT_NO_EMPTY);
	$digits = count($ip_split);
	$new_ip = '';//new ip without "." and 12 characters
	
	for($i = 0; $i < $digits; $i++){
		//add preceding 0s anywhere we don't have 3 numbers in a block
		//then append it to our new ip address
		$new_ip .= str_pad($ip_split[$i], 3, '0', STR_PAD_LEFT);
	}//end for: add padding 0s
	
	return $new_ip;
}//end function: formatIP



//update this to use above function
/**
* takes a uid and ip and the timestamp to generate a token
* that token is then returned
* @param uid {string} the user's id
* @param ip {string} the ip address this user has
* @return token {string} a 76 character long string
*/
function generateToken($uid, $ip){
	//split up the ip, we don't care about "."
	$ip_split = preg_split('/\./', $ip, -1, PREG_SPLIT_NO_EMPTY);
	$digits = count($ip_split);
	$new_ip = '';//new ip without "." and 12 characters
	
	for($i = 0; $i < $digits; $i++){
		//add preceding 0s anywhere we don't have 3 numbers in a block
		//then append it to our new ip address
		$new_ip .= str_pad($ip_split[$i], 3, '0', STR_PAD_LEFT);
	}//end for: add padding 0s
	
	//convert to different base - now that the ip will remain the same
	$ip_con = base_convert($new_ip,10,18);
	$ip_padded = str_pad($ip_con, 12, '0', STR_PAD_LEFT);
	$ip_padded_split = str_split($ip_padded,4);
	
	//grab the timestamp
	$dt = new DateTime("now");
	$dt_str = $dt->getTimestamp();
	//convert it
	$dt_con = base_convert($dt_str,10,14);
	$dt_padded = str_pad($dt_con, 12, '0', STR_PAD_LEFT);
	$dt_padded_split = str_split($dt_padded,4);
	
	//convert the user id
	$uid_con = base_convert($uid,10,20);
	$uid_padded = str_pad($uid_con, 12, '0', STR_PAD_LEFT);
	$uid_padded_split = str_split($uid_padded,4);
	
	$your_token = '';
	for($i = 0; $i < 3; $i++){
		$your_token .= $ip_padded_split[$i] . $uid_padded_split[$i] . $dt_padded_split[$i];
	}//end for: go through all groups
	
	$shad_token = sha1($your_token);
	$token =  $shad_token."". $your_token;
	return $token;
}//end function: generateToken

/**
* takes a token and extracts information from it that is needed
* @param token {stirng} the 76 long character string
* @return {array} the user id, ip and timestamp assocaited with this token
*/
function extractToken($token){
	//should be 40 characters followed by 36 (12 * 3)
	$extract_token = str_split($token, 40);
	
	if($extract_token[0] == sha1($extract_token[1])){
		//split it up into groups of 4
		$items = str_split($extract_token[1],4);
		
		//put it back together
		$ip = $items[0] . $items[3] . $items[6];
		$user_id = $items[1] . $items[4] . $items[7];
		$dt = $items[2] . $items[5] . $items[8];
		
		//timestamp
		$new_dt = base_convert($dt,14,10);
		
		//ip
		$ip_re = base_convert($ip,18,10);
		//make it 12 characters
		$new_ip = str_pad($ip_re, 12, '0', STR_PAD_LEFT);
		
		//user id
		$uid = base_convert($user_id,20,10);
		//our ids for users are 11 characters, add 0s
		$new_uid = str_pad($uid, 11, '0', STR_PAD_LEFT);
		
		return array(
			"uid"=>$new_uid,
			"ip"=>$new_ip,
			"dateTime"=>$new_dt
		);
	
	}//end if: has this been messed with?
	
	return false;
}//end function: extractToken


/**
* takes in two strings and compares their values
* @return {boolean} whether or not they matched
*/
function matchValues($str_one, $str_two){
	return (strcmp($str_one,$str_two) == 0) ? true : false;
}//end function: matchValues

/**
* checks if a username matches our restrictions
* @return {boolean} whether or not it's acceptable
*/
function checkUsername($unm){
	
	$clean_unm = preg_replace("/[^a-zA-Z0-9\-_.]/","",$unm);//whitelisting characters
	$errors = "";
	
	if(strlen($unm) < 4){
		$errors .= "<li>Username is not long enough</li>";
	}//end if: long enough?
	
	if(!matchValues($clean_unm, $unm)){
		$errors .= "<li>Only alphanumeric characters or . - _ are allowed in your username.</li>";
	}//end if: does it include invalid characters?
	
	return $errors;
}//end function: checkUsername

/**
* checks if a email matches our restrictions
* @return {boolean} whether or not it's acceptable
*/
function checkEmail($email,$email_second){
	$errors = "";
	if(!matchValues($email,$email_second)){
		$errors .= "<li>Your emails do not match.</li>";
	}//end if: do they even match?
	
	if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		 $errors .= "<li>Invalid email address.</li>";
	}//end if: do we have a valid email?
	
	return $errors ;
}//end function: checkEmails


/**
* checks if a password matches our restrictions
* @return {boolean} whether or not it's acceptable
*/
function checkPassword($pwd,$pwd_second){
	$pwd_clean = preg_replace("/[^a-zA-Z0-9.!]/","",$pwd);//whitelisting characters
	$errors = "";
	if(strlen($pwd) < 6){
		$errors .= "<li>Password is not long enough</li>";
	}//end if: password long enough?
	
	if(!matchValues($pwd_clean, $pwd)){
		$errors .= "<li>Only alphanumeric characters or . and ! are allowed in your password.</li>";
	}//end if: password contain invalid characters?
	
	if(!matchValues($pwd,$pwd_second)){
		$errors .= "<li>Your Passwords do not match.</li>";
	}
	
	
	return $errors;
}//end function: checkPasswords

/**
* Clean up whatever string we get to strip of
* spaces and any restricted characters
*/
function cleanData($data){
	$cleanData = filter_var($data, FILTER_SANITIZE_STRING);
	return $cleanData;
}//end function: cleanData

/**
* generic error JSON response
*/
function errorResp($desc){
	if(!empty($desc)){
		return json_encode(array(
			"code" => 0,
			"desc" => $desc
		));
	}//end if: do we even have a desc?
}//end function: errorResp

/**
* Formats the JSON response for backend calls
* @param code {int} the code (-1, 0, 1)
* @param desc {string} a description of the response
* @param result {JSON} the data the front-end is interested in
* @return {JSON} all params in json format for the front end
*/
function getResultJSON($code, $desc, $result){
	return json_encode(array(
		"code" => $code,
		"desc" => $desc,
		"result" => $result
	));
}//end function: getResultJSON


/**
* Gives you a user_id based on a username
* @param unm {string} the username
* @param conn {object} the database connection
* @return {string} the user id associated with the username
*/
function getUserId($conn, $unm){
	$query = "select user_id from user where username=:unm";
	$params=array("unm"=>$unm);
	$data = $conn->getData($query,$params);
	return $data[0]["user_id"];
}//end function: getUserId


/**
* Gives you a user id based on a username
* @param conn {object} the database connection
* @param {int} the user_id
* @return {string} the username associated with the user id
*/
function getUsername($conn, $uid){
	$query = "select username from user where user_id=:uid";
	$params=array("uid"=>$uid);
	$data = $conn->getData($query,$params);
	return $data[0]["username"];
}//end function: getUserId


/**
* Gives you a icon based on a username
* @param conn {object} the database connection
* @param {string} the username associated with this icon
* @return {string} the icon associated with the username
*/
function getIcon($conn, $unm){
	$query = "select email from user where username=:unm";
	$params=array("unm"=>$unm);
	$data = $conn->getData($query, $params);
	return md5(strtolower(trim($data[0]["email"])));
}//end function: getIcon


?>