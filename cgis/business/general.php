<?php

/**
* Logs a user out by deleting their session id
* @param unm {string} the username of who is logging out
* @param sid {string} the session id
*/
function logout($unm, $sid){
	$code = 0;
	$desc = "An unknown error has occurred.";
	$result = "";
	
	$conn = new DB();
	$query = "update user set session_id =:n 
				where session_id=:sid and username=:unm";
	$params = array(
		"n"=>"",
		"sid"=>$sid,
		"unm"=>$unm
	);
	
	$conn->setData($query, $params);
	
	$token = extractToken($sid);
	$uid = $token["uid"];
	
	$query = "delete from chat_users where user_id=:uid";
	$params = array("uid"=>$uid);
	
	$conn->setData($query, $params);
	
	if($conn->getAffectedRows() == 1){
		$code = 1;
		$desc = "Successfully logged out $unm";
	}//end if: did we log out the user?
	
	$conn->closeConnection();
	$conn = null;
	
	
	return getResultJSON($code, $desc, $res);
}//end function: logout

/**
* Signs a user up to play with the dots game
* @param unm {string} username oof this new user
* @param pwd {string} the password they wanna use
* @param email {string} the email for this user
* @param ip {string} the ip they are using to signup
*/
function signup($unm, $pwd, $email, $ip){
	$conn = new DB();
	if(!checkUsers($unm, $conn)){
		//don't bother with anything else
			//kill connection, return failure
		$conn->closeConnection();
		$conn = null;
		return json_encode(array(
			"code" => 0,
			"desc" => "Please choose a different username."
		));
	}//end if: does the username already exist?
	
	$query = "insert into user (username, password, email) values(:unm, SHA2(:pwd, 224), :email)";
	$params = array(
		"unm" => $unm,
		"pwd" => $pwd,
		"email" => $email
	);
	
	$conn->setData($query, $params);
	
	//find out how many rows were affected
	$amt = $conn->getAffectedRows();
	
	//default to bad result
	$code = 0;
	$desc = "Something has gone wrong. Please try again.";
	
	if($amt == 1){
		$uid = $conn->getLastId();
		$token = generateToken($uid, $ip);
		
		if(updateLoginDetails($conn, $token)){
			
			
		}
		
		
		$code = 1;
		$desc = "Successfully signed up.";
		$res = array(
			"unm" => $unm,
			"icon" => md5(strtolower(trim($email))),
			"sid" => $token
		);
	}//end if: did we have 1 row affected?
	
	$conn->closeConnection();
	$conn = null;
	
	//return whatever we know from here
	return getResultJSON($code, $desc, $res);
}//end function: signup

/**
* Signs a user up to play with the dots game
* @param conn {object} the database connection
* @param ip {string} the ip they are using to signup
*/
function updateLoginDetails($conn, $token){
	//ip, uid, dateTime
	$details = extractToken($token);
	$query = "update user set session_id=:sid where user_id=:uid";
	$params = array("sid"=>$token, "uid"=>$details["uid"]);
	$conn->setData($query, $params);
	if($conn->getAffectedRows() == 1){
		return true;
	}//end if: did we update 1 record?
	return false;
}//end function: update LoginDetails


/**
* Check if a username already is in use
* @param unm {String} the username to check
* @param conn {object} the database connection object
*/
function checkUsers($unm, $conn){	
	$query = "select username from user where username = :unm";
	$params = array("unm" => $unm);
	$amt = $conn->getData($query,$params);
	
	if(count($amt) !== 0){
		return false;
	}//end if: do we have results that match?
	
	return true;
}//end function: checkUsername


/**
* Checks to see if the information you requested
* is a valid login for an existing user
* @param $unm {String} the username
* @param $pwd {String} the password
* @param $ip {string} the ip they used to login
*/
function login($unm, $pwd, $ip){
	$conn = new DB();
	$code = 0;
	$desc = "";
	$token = "xxxx";

	$query = "select username, email, user_id from user where username = :user and password = SHA2(:pw,224)";
	$params = array('user'=>$unm,'pw'=>$pwd);
	$user = $conn->getData($query,$params);
	
	$token = generateToken($user[0]["user_id"],$ip);
	
	$res = array();
	if(count($user) == 1){
		if(updateLoginDetails($conn, $token)){
			$code = 1;
			$desc = "successfully logged in";
			$res = array(
				"unm" => $user[0]["username"],
				"icon" => md5(strtolower(trim($user[0]["email"]))),
				"sid" => $token
			);
		}//end if: did we update the record?
		
	}else{
		$desc = "Something went wrong, please check your credentials.";
	}//end else/if: do we have just one result?
	
	$conn->closeConnection();
	$conn = null;
	
	
	return getResultJSON($code, $desc, $res);
	
}//end function login








?>