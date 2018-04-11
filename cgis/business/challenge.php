<?php


/**
* adds a challenge
* @param conn {object} the database connection object
* @param cols/rows {int} how many?
* @param users {array} list of users challenged
* @param public {boolean} whether or not it's public
* @return {JSON} result of adding challenge
*/
function addChallenge($unm, $cols, $rows, $users, $public){
	$code = 0;
	$desc = "invalid challenge request  $public ";
	$result = "";
	
	
	$conn = new DB();
	
	$uid = getUserId($conn,$unm);
	
	
	$query = "insert into challenges(challenger, rows, cols, public) 
			values(:challenger,:rows,:cols,:pub)";
	$params = array(
		"challenger"=>$uid,
		"rows"=>$rows,
		"cols"=>$cols,
		"pub"=>$public
	);
	$conn->setData($query, $params);
	
	$cid = $conn->getLastId();

	$all_users = preg_split('/,/', $users, -1, PREG_SPLIT_NO_EMPTY);
	$amt = count($all_users);
	$user_ids = array();
	for($u = 0; $u < $amt; $u++){
		$user_ids[] = getUserId($conn,$all_users[$u]);
	}
	
	if(addChallengedUsers($conn, $user_ids, $cid)){
		$code = 1;
		$desc = "You have challenged user(s)!";
	}else{
		$desc = "idk what is wrong...";
	}//end if: did we successfully add the users?
	
	
	//close db connection
	$conn->closeConnection();
	$conn = null;
	
	return getResultJSON($code, $desc, $result);
	
}//end function: addChallenge

/**
* adds users to a challenge
* @param conn {object} the database connection object
* @param users {array} list of users challenged
* @param cid {int} challenge id
*/
function addChallengedUsers($conn, $users, $cid){
	
	$amt = count($users);
	$query = "insert into challenged_users values(:cid,:uid,:acc, :resp)";
	$count = 0;
	
	
	for($i = 0; $i < $amt; $i++){
		$params = array(
			"cid"=>$cid,
			"uid"=>$users[$i],
			"acc"=>false,
			"resp"=>false
		);
		$conn->setData($query, $params);
		$affected = (int)$conn->getAffectedRows();
		$count = $affected + $count;
	}//end for: go through all users we want to challenge
	
	if($count == $amt){
		return true;
	}//end if: did we add a user?
	return false;
}//end function: addChallengedUsers



/**
* obtains all challenges for you
* @param conn {object} the database connection object
* @param uid {int} this user's id
*/
function getYourChallenges($conn, $uid){
	$query = "select id from challenges as c join
				challenged_users as cu on cu.challenged_id = c.id 
				where cu.user_id = :uid and responded=:res";
	$params = array("uid"=>$uid, "res"=>false);
	$res = $conn->getData($query, $params);
	
	$amt = count($res);
	$challenges = array();
	for($i = 0; $i < $amt; $i++){
		$id = $res[$i]["id"];
		$challenges[] = getAChallenge($conn,$id, $uid);
	}//end for: go through all the id's we have
	
	return $challenges;
}//end function: getYourChallenges

/**
* obtains a challenge based on it's id and yours
* @param conn {object} the database connection object
* @param uid {int} this user's id
*/
function getAChallenge($conn, $cid, $uid){
	$query = "select GROUP_CONCAT(CONCAT(username,':',accepted,':',responded) SEPARATOR ',') as players,
				(select username from user as u join 
				challenges as c on u.user_id=c.challenger where id=:cid) as challenger, id, rows, cols, public
				from challenges as c 
				join challenged_users as cu on cu.challenged_id = c.id 
				join user as u on cu.user_id = u.user_id 
				where c.id = :cid";
	
	$params = array("cid"=>$cid, "uid"=>$uid);
	$res = $conn->getData($query, $params);
	
	$challenged = array();
	
	$players = preg_split('/,/', $res[0]["players"], -1, PREG_SPLIT_NO_EMPTY);
	$amt = count($players);
	for($i = 0; $i < $amt; $i++){
		$player = preg_split('/:/', $players[$i], -1, PREG_SPLIT_NO_EMPTY);
		$challenged[] = array(
			"username"=>$player[0],
			"accepted"=>($player[1] == 0) ? (boolean)false : (boolean)true,
			"responded"=>($player[2] == 0) ? (boolean)false : (boolean)true
		);
	}//end for: go through all players
	
	$challenge = array(
		"id"=>$res[0]["id"],
		"rows"=>(int)$res[0]["rows"],
		"cols"=>(int)$res[0]["cols"],
		"public"=>(boolean)$res[0]["public"],
		"challenger"=>$res[0]["challenger"],
		"challenged"=>$challenged
	);
	
	return $challenge;
	
}//end function: getYourChallenges





/**
* allows a user to respond to a challenge
* @param cid {int} challenge id
* @param unm {string} the username
* @param accept {boolean} whether or not they have accepted it
* @return {JSON} the response from the server/database
*/
function repsondToChallenge($cid, $unm, $accept){
	$code = 0;
	$desc = "invalid challenge request.";
	$result = "";
	//updated responded field
	$conn = new DB();
	$uid = getUserId($conn,$unm);
	$query = "update challenged_users set responded=:res, accepted=:acc 
				where user_id=:uid and challenged_id=:cid";
	$params = array(
		"res"=>1,
		"acc"=>($accept) ? 1 : 0,
		"uid"=>$uid,
		"cid"=>$cid
	);
	
	$conn->setData($query,$params);
	
	if($conn->getAffectedRows() == 1){
		//cool, were they the last user to respond?
		$code = 1;
		$desc = "successfully responded to challenge.";
		if(needGameCreated($conn, $cid, $uid, $accept)){
			if(createGame($conn, $cid, $uid)){
				$result = "Game has been created";
			}else{
				$code = 0;
				$desc = "An unknown error has occurred.";
			}//end else/if: did we create the game?
		}//end if: do we need a game?
		
	}//end if: did we affect 1 row?
	
	$conn->closeConnection();
	$conn = null;
	
	$result =$params;
	return getResultJSON($code, $desc, $result);
}//end function: acceptChallenge

/**
* decides whether a new game needs to be created based on this
* challenge response
* @param conn {object} the database connection object
* @param cid {int} the challenge id 
* @param uid {int} the user's id who is responding
* @param accept {boolean} true if they accepted it
* @return {boolean} whether or not they need to create a new game
*/
function needGameCreated($conn, $cid, $uid, $accept){
	$query = "select count(user_id) as responded, 
				(select count(user_id) from challenged_users 
				where challenged_id=:cid) as total,
				(select count(user_id) from challenged_users 
				where challenged_id=:cid and accepted=:acc) as accepted from 
				challenged_users where challenged_id=:cid and responded=:res";

	$params = array(
		"cid"=>$cid,
		"res"=>true,
		"acc"=>true
	);

	$res = $conn->getData($query,$params);

	if($res[0]["responded"] == $res[0]["total"]){
		if(($res[0]["total"] == 1 && !$accept) || (int)$res[0]["accepted"] == 0){
			//we need to reject the challenge
			$query = "update challenges set rejected=:reject where id=:cid";
			$params = array("cid"=>$cid,"reject"=>true);
			$conn->setData($query,$params);
			return false;
		}//end if: 
		return true;
	}//end if: do we have all responses?
	
	return false;
}//end function: needGameCreated

?>