<?php

/****************************************************************
* @desc		handles all the game functionality at the business 
*			level, making calls to the database as needed
* 
* @author	erika tobias (et5392@rit.edu)
* @date		October 18th, 2017
* @updated	December 16th, 2017
****************************************************************/



/**
* creates a game if one is needed
* @param conn {object} the connection to the database
* @param cid {int} the challenge id
* @param uid {int} the user's id
*/
function createGame($conn, $cid, $uid){
	//grab whatever details had been set for this challenge,
	//we need to pass it to the game
	$query = "select rows, cols, public, challenger from challenges
				where id=:cid";
	
	$params = array("cid"=>$cid);
	$res = $conn->getData($query, $params);
	$challenger = $res[0]["challenger"];
	//create the game instance
	$query = "insert into game (rows, cols, public) 
				values(:rows,:cols,:pub)";
	$params = array(
		"rows"=>(int)$res[0]["rows"],
		"cols"=>(int)$res[0]["cols"],
		"pub"=>(boolean)$res[0]["public"]
	);
	$conn->setData($query, $params);
	
	
	//grab the game id
	$gid = $conn->getLastId();
	
	//now give a game_id to the challenge
	$query = "update challenges set game_id=:gid 
				where id=:cid";
	$params = array("gid"=>$gid, "cid"=>$cid);
	$conn->setData($query, $params);
	
	//let's add the users to the game_players table now
	$query = "select user_id from challenged_users 
				where challenged_id=:cid";
	$params = array("cid"=>$cid);
	$res = $conn->getData($query, $params);
	//account for the challenger, add them to array
	
	$chat_id = createGameChat($conn, $gid);
	
	$res[] = array(
		"user_id"=>$challenger
	);
	
	
	$amt_players = count($res);
	for($i = 0; $i < $amt_players; $i++){
		//last user to respond is who gets to go first
		$this_user = $res[$i]["user_id"];
		$turn = ($uid == $this_user) ? true : false;
		//make the user be player + 1 of whatever iteration this is
		//+1 to avoid a player0
		addPlayer($conn, $this_user, $gid, ($i+1), $turn);
	}//end for: go through each player to add them
	
	return true;
}//end function: createGame

/**
* adds a player to the game
* @param conn {object} the connection to the database
* @param uid {int} the user id to add
* @param gid {int} the game id
* @param player {int} player 1,2,3,4?
* @param turn {boolean} whether or not it is this persons turn
* @return {boolean} whether or not it's their turn
*/
function addPlayer($conn, $uid, $gid, $player, $turn){
	$query = "insert into game_players(user_id, game_id, player, turn)
				values(:uid, :gid, :player, :turn)";
	$params = array(
		"uid"=>$uid,
		"gid"=>$gid,
		"player"=>(int)$player,
		"turn"=>(boolean)$turn
	);
	
	$conn->setData($query,$params);
	if($conn->getAffectedRows() == 1){
		return true;
	}//end if: did we add 1 user?
	
	return false;
}//end function: addPlayers


/**
* creates a game chat based on the game id
* @param conn {object} the database connection
* @param gid {int} the game id
* @return {int} the chat's id
*/
function createGameChat($conn, $gid){
	$query = "insert into chat (game_id) values (:gid)";
	$params = array("gid"=>$gid);
	$cid = "";
	
	$conn->setData($query, $params);
	if($conn->getAffectedRows() == 1){
		$cid = $conn->getLastId();
	}//end if: did we insert a row?
	
	return $cid;
}//end function: createGameChat

/**
* gives you a list of all public games without a winner
* @param conn {object} the connection to the database
* @return {array} the list of games which are public and unfinished
*/
function getPublicGames($conn){
	$query = "select g.game_id, (select GROUP_CONCAT(username SEPARATOR ' vs. ') from 
				user join game_players using (user_id) where game_id = g.game_id) as players from game as g 
				join game_players as p on p.game_id = g.game_id 
				join user as u on p.user_id = u.user_id 
				where public = :pub and winner is NULL GROUP BY g.game_id";
	$params = array("pub"=>(boolean)true);
	$res = $conn->getData($query,$params);

	return formatGameReturn($res);
}//end function: getPublicGames

/**
* formats a game return to be the same based on array
* @param games_raw {array} a list of objects of games
* @return {JSON} reformatted list of the games
*/
function formatGameReturn($games_raw){
	$total = count($games_raw);
	$games = array();
	for($i = 0; $i < $total; $i++){
		$games[$i] = array(
			"id"=>$games_raw[$i]["game_id"],
			"name"=>$games_raw[$i]["players"]
		);
		
		if(array_key_exists("your_turn", $games_raw[$i])){
			$games[$i]["your_turn"] = (boolean)$games_raw[$i]["your_turn"];
		}//end if: only if it's their games do we care
	}//end for: go through all games
	
	return $games;
}//end function: formatGameReturn


/**
* obtain all ongoing games (games you're a player and no winner set)
* @param conn {object} the connection to the database
* @param uid {int} the user id
*/
function getYourOngoingGames($conn, $uid){
	$query = "select g.game_id, (select GROUP_CONCAT(username SEPARATOR ' vs. ') from 
				user join game_players using (user_id) where game_id = g.game_id) as players,
				p.turn as your_turn from game as g 
				join game_players as p on p.game_id = g.game_id 
				join user as u on p.user_id = u.user_id 
				where u.user_id=:uid and winner is NULL GROUP BY g.game_id;";
	$params = array("uid"=>$uid);
	$res = $conn->getData($query,$params);

	return formatGameReturn($res);
}//end function: getYourOngoingGames




/**
* obtain all ongoing games (games you're a player and no winner set)
* @param conn {object} the connection to the database
* @param uid {int} the user id
* @return {json} a list of all games
*/
function getAllYourGames($conn, $uid){
	
	$query = "select g.game_id, GROUP_CONCAT(username SEPARATOR ' vs. ') as players from game as g 
			join game_players as p on p.game_id = g.game_id 
			join user as u on p.user_id = u.user_id 
			where user_id=:uid";
	$params = array("uid"=>$uid);
	$res = $conn->getData($query,$params);

	return formatGameReturn($res);
}//end function: getAllYourGames


/**
* obtains all the stats for this user
* @param conn {object} the connection to the database
* @param uid {int} thge user's id
* @return {JSON} the user's stats
*/
function getUserGameStats($conn,$uid){
	//$conn = new DB();
	//$uid = getUserId($conn,$unm);
	$params = array("uid"=>$uid);
	//ongoing
	$query = "select count(g.game_id) from game as g join(game_players as p) using(game_id) where user_id=:uid and winner is NULL";
	$res = $conn->getData($query,$params);
	$ongoing = (int)$res[0][0];
	
	//won
	$query = "select count(g.game_id) from game as g join(game_players as p) using(game_id) where user_id=:uid and winner=:uid";
	$res = $conn->getData($query,$params);
	$won = (int)$res[0][0];
	
	//lost
	$query = "select count(g.game_id) from game as g join(game_players as p) using(game_id) where user_id=:uid and winner!=:uid and winner is not NULL";
	$res = $conn->getData($query,$params);
	$lost = (int)$res[0][0];
	
	//total
	$query = "select count(g.game_id) from game as g join(game_players as p) using(game_id) where user_id=:uid";
	$res = $conn->getData($query,$params);
	$total = (int)$res[0][0];
	
	//$conn->closeConnection();
	//$conn = null;
	
	//$code = 1;
	//$desc = "Successfully retrieved game stats.";
	$result = array(
		"ongoing"=>$ongoing,
		"won"=>$won,
		"lost"=>$lost,
		"total"=>$total
	);
	return $result;
	//return getResultJSON($code, $desc, $result);
}//end function: getUserGameStats


/**
* determines if there is a winner or not
* @param conn {object} the connection to the database
* @param gid {int} the game's id
* @return {boolean} whether or not there is a winner
*/
function haveWinner($conn, $gid){
	$p = getGamePlayers($conn, $gid);
	$g = getGameDetails($conn, $gid);
	
	$rows = (int)$g[0]["rows"];
	$cols = (int)$g[0]["cols"];
	$total = $rows * $cols;
	$player_square_total = 0;
	$most_squares = 0;
	$most_squares_player = "";
	
	$player_amt = count($p);
	
	for($i = 0; $i < $player_amt; $i++){
		$sqs = $p[$i]["squares"];
		$all_sqs = preg_split('/,/', $sqs, -1, PREG_SPLIT_NO_EMPTY);
		$this_players_squares = count($all_sqs);
		$player_square_total += $this_players_squares;
		if($most_squares == 0 || $this_players_squares > $most_squares){
			$most_squares = $this_players_squares;
			$most_squares_player = $p[0]["user_id"];
		}//end if: is it zero or larger than what we have?
	}//end for: go through all players
	
	if($player_square_total == $total){
		return updateWinner($conn, $most_squares_player, $gid);
	}//end if: are there any squares even left?
	return false;
}//end function: haveWinner

/**
* determines if there is a winner or not
* @param conn {object} the connection to the database
* @param uid {int} the user id we care about
* @param gid {int} the game's id
* @return {boolean} whether or not the winner was set
*/
function updateWinner($conn, $uid, $gid){
	$query = "update game set winner=:uid where game_id=:gid";
	$params = array("uid"=>$uid,"gid"=>$gid);
	
	$conn->setData($query,$params);
	if($conn->getAffectedRows() == 1){
		//no ones turn now - there is a winner
		$query = "update game_players set turn=:turn where game_id=:gid";
		$params = array("turn"=>$false,"gid"=>$gid);
		$conn->setData($query,$params);
		if($conn->getAffectedRows() > 0){
			return true;
		}//end if: did we affect some rows?
	}//end if: did we update 1 record?
	return false;
}//end function: updateWinner


/**
* obtains the game's details
* @param id {int} the game's id
* @param unm {string} the username
* @return {json} this game's detail
*/
function getGame($id, $unm){
	$code = 0;
	$desc = "Invalid Game Request";
	$result = "";
	$conn = new DB();
	
	$user_id = getUserId($conn,$unm);
	$res = getGameDetails($conn, $id);
	$players = getGamePlayers($conn, $id);
	$chat_id = getChatId($conn, $id);
	
	$player_count = count($players);
	if($player_count > 0){
		$have_match = false;
		$you_are_player = "";
		$your_turn = false;
		$player_num = 0;
		$your_squares = array();
		$opponents = array();
		$all_players = array();
		
		for($p = 0; $p < $player_count; $p++){
			$sqs = $players[$p]["squares"];
			$all_sqs = array();
			if(!empty($sqs)){
				$all_sqs = preg_split('/,/', $sqs, -1, PREG_SPLIT_NO_EMPTY);
				//$all_sqs = explode(",",$sqs);
			}
			if($players[$p]["user_id"] == $user_id){
				$you_are_player = $players[$p]["player"];
				$your_turn = ($players[$p]["turn"] == 0)? false:true;
				$your_squares = $all_sqs;
				$have_match = true;
				$all_players[] = $unm;
			}else{
				$opp_unm = getUsername($conn, $players[$p]["user_id"]);
				$opponents["player".$players[$p]["player"]] = array(
					"turn"=>($players[$p]["turn"] == 0)? false:true,
					"player"=>$players[$p]["player"],
					"squares"=>$all_sqs,
					"username"=>$opp_unm
				);
				$all_players[] = $opp_unm;
			}//end else/if: is it you?
			
		}//end for: go through all players
		
		if(count($res) == 1){
			$lines = $res[0]["all_lines"];
			$all_lines = array();
			if(!empty($lines)){
				$all_lines = preg_split('/,/', $lines, -1, PREG_SPLIT_NO_EMPTY);
				//$all_lines = explode(",",$lines);
			}
			
			$is_public = $res[0]['public'];
			
			if($is_public || (!$is_public && $have_match)){
				$code = 1;
				$desc = "Valid Game Request";
				//update this later to not be so... repetitive
				if($have_match){
					$result = array(
						"player_you"=>$you_are_player, 
						"rows"=>$res[0]["rows"],
						"cols"=>$res[0]["cols"],
						"lines"=>$all_lines,
						"your_turn" => $your_turn,
						"your_squares" => $your_squares,
						"opponents"=> $opponents,
						"winner"=>getUsername($conn, $res[0]["winner"]),
						"name"=>implode(" vs. ", $all_players),
						"chat_id"=>$chat_id
					);
				}else{
					$result = array(
						"spectator"=>true,
						"rows"=>$res[0]["rows"],
						"cols"=>$res[0]["cols"],
						"lines"=>$all_lines,
						"opponents"=> $opponents,
						"winner"=>getUsername($conn, $res[0]["winner"]),
						"name"=>implode(" vs. ", $all_players),
						"chat_id"=>$chat_id
					);
				}
			}//end if: is this a public/private game?
			
		}//end if: do we have a game with that id?
	}//end if: do we have any players?
	
	
	
	$conn->closeConnection();
	$conn = null;
	
	return getResultJSON($code, $desc, $result);
}//end function: getGame

/**
* determines if it is their turn or not
* @param conn {object} the database connection
* @param gid {int} the game's id we care about
* @return {int} the chat id
*/
function getChatId($conn, $gid){
	$query = "select chat_id from chat where game_id=:gid";
	$params = array("gid"=>$gid);
	$res = $conn->getData($query, $params);
	return $res[0][0];
}//end function: getChatId




/**
* grabs the players for this game
* @param conn {object} the database connection
* @param id {int} the game's id
* @return {JSON} the game players in JSON format
*/
function getGamePlayers($conn, $id){
	$query = "select * from game_players where game_id = :gameid";
	$params = array("gameid"=>$id);
	$res = $conn->getData($query, $params);
	return $res;
}//end function: getGamePlayers

/**
* gives you the game's details
* @param conn {object} the database connection
* @param id {int} the game's id
* @return {JSON} the details in JSON format
*/
function getGameDetails($conn, $id){
	$query = "select * from game where game_id = :gameid";
	$params = array("gameid"=>$id);
	$res = $conn->getData($query, $params);
	return $res;
}//end function: getGameDetails



/**
* determines if it is their turn or not
* @param conn {object} the database connection
* @param uid {int} the user's id
* @param gid {int} the game's id
* @return {boolean} whether or not it's their turn
*/
function isPlayersTurn($conn, $uid, $gid){
	$query = "select turn from game_players where user_id=:uid and game_id=:gid";
	$params = array(
		"uid"=>$uid,
		"gid"=>$gid
	);
	$res = $conn->getData($query,$params);
	return ($res[0][0] == 0)? false : true;
}//end function: isPlayersTurn



/**
* updates whose turn it is
* @param conn {object} the connection to the database
* @param gameId {int} the game's id
* @param player_who_moved {int} id of the person who moved just now
* @return {boolean} whether or not the turn was update
*/
function changeTurn($conn, $gameId, $player_who_moved){
	//determine how many players we have
	$query = "select count(player) from game_players where game_id = :gameid";
	$params = array("gameid" => $gameId);
	$res = $conn->getData($query, $params);
	$amt = (int)$res[0][0];
	
	//determine what player the one who moved is
	$query = "select player from game_players where game_id = :gameid and user_id = :uid";
	$params = array("gameid" => $gameId, "uid"=>$player_who_moved);
	$res = $conn->getData($query, $params);
	$last_player = (int)$res[0]["player"];
	
	//it's no longer this players turn
	$query = "update game_players set turn = :turn where player = :player and game_id=:gameid";
	$params = array(
		"gameid" => $gameId, 
		"turn"=>false,
		"player"=>$last_player
	);
	$res = $conn->setData($query, $params);
	
	if($conn->getAffectedRows() == 1){
		if($last_player == $amt){//they are the last player
			$next_player = 1;//go back to player 1
		}else{//they are some player inbetween
			$next_player = $last_player + 1;//increment to next player
		}//end else/if: who is next?
		
		$params = array(
			"gameid" => $gameId, 
			"turn"=>true,
			"player"=>$next_player
		);
		$res = $conn->setData($query, $params);
		if($conn->getAffectedRows() == 1){
			return true;
		}//end if: did we update 1 record?
	}//end if: did we update 1 record?
	
	return false;
}//end function: changeTurn


/**
* determines if this is a valid move or not and records it
* @param r_row, col, l_row, col{int} the coordinates for this line
* @param gameId {int} the game's id
* @return {JSON} whether or not this move was made and the database response
*/
function makeMove($unm, $f_row, $f_col, $l_row, $l_col, $gameId){
	$code = 0;
	$desc = "Either you have performed an invalid move or it is not your turn.";
	$result = "";
	$conn = new DB();
	
	$uid = getUserId($conn, $unm);
	
	if(isPlayersTurn($conn, $uid, $gameId)){
		$line = "dot_".$f_row."_".$f_col."|dot_".$l_row."_".$l_col;
		
		if(validDrop($f_row, $f_col, $l_row, $l_col, $conn, $gameId)){
			if(addLine($conn, $line, $gameId)){
				
				$code = 1;
				$desc = "Your move has been made";
				$squares = "";
				$squares = madeSquares($f_row, $f_col, $l_row, $l_col, $conn, $gameId);
				if(!empty($squares)){
					$amt = count($squares);
					$sqs = "";
					for($s = 0; $s < $amt; $s++){
						if(!empty($squares[$s])){
							$sqs .= $squares[$s] . ",";
						}
					}//end for:
					
					if(!addSquares($conn, $sqs, $uid, $gameId)){
						$desc .= "Your move was made, but something went wrong adding your square";
					}//end if: did we add it successfully?
				}//end if: did we have a square?
				
				
				$result = array(
					'valid'=>true,
					'added_line'=>$line,
					'squares_won'=>$squares,
					'your_turn'=>false
				);
				
				//update who's turn it is
				changeTurn($conn, $gameId, $uid);
				haveWinner($conn, $gameId);
				
				
			}//end if: did we add the line to the DB?
		}//end if: was it a valid linedrop?
	}//end if: is it this players turn?
	
	$conn->closeConnection();
	$conn = null;
	return getResultJSON($code, $desc, $result);
}//end function: makeMove

/**
* Adds a line to the list of lines in this game
* 
*/
function addLine($conn, $line, $gid){
	$query = "select all_lines from game where game_id=:gid";
	$params = array("gid"=>$gid);
	$res = $conn->getData($query,$params);
	
	$lines = $res[0]["all_lines"];
	if(!empty($lines)){
		$lines .= $line . ",";
	}else{
		$lines = $line.",";
	}//end else/if: do we have a line?
	
	$query = "update game set all_lines = :lines where game_id=:gid";
	$params = array("lines"=>$lines,"gid"=>$gid);
	$conn->setData($query,$params);
	
	if($conn->getAffectedRows() == 1){
		return true;
	}//end if: did we add it?
	
	return false;
}//end function: addLine

/**
* validates whether a line drop can be done or not
* @param r_row, col, l_row, col{int} the coordinates for this line\
* @param conn {object} the connection to the database
* @param gameId {int} the game's id
* @return {boolean} whether or not this is a valid line
*/
function validDrop($f_row, $f_col, $l_row, $l_col, $conn, $gameId){
	
	//check if the line already exists
	//lineExists is true if it exists
	if(!lineExists($f_row, $f_col, $l_row, $l_col, $conn, $gameId)){
		$data = getGameDetails($conn, $gameId);
		$rows = $data[0]["rows"];
		$cols = $data[0]["cols"];
		
		if($f_row > $rows || $l_row > $rows
			|| $f_col > $cols || $l_col > $cols){
			return false;
		}//end if: are they adding a line which can't exist?
		
		if($f_row == $l_row){
			if($f_col + 1 == $l_col || $f_col - 1 == $l_col){
				return true;
			}//end if: it can only be +/- 1 for a col
		}//end if: are they in the same row?
		
		if($f_col == $l_col){
			if($f_row + 1 == $l_row || $f_row - 1 == $l_row){
				return true;
			}//end if: it can only be +/- 1 for a row
		}//end if: is it in the same column?
	}//end if: does the line exist?
	
	//matched nothing? invlaid
	return false;
}//end function: validDrop


/**
* Checks if the line they are trying to draw already exists
* @param r_row, col, l_row, col{int} the coordinates for this line\
* @param conn {object} the connection to the database
* @param gameId {int} the game's id
* @return {boolean} whether or not this line exists or not
*/
function lineExists($f_row, $f_col, $l_row, $l_col, $conn, $gameId){
	//assume they didn't give coordients and passed in 2 ids
	$id_one = $f_row;
	$id_two = $f_col;
	if(!empty($l_col) && !empty($l_row)){
		//they passed in coordients, build the possible ids
		$id_one = "dot_" .$f_row."_".$f_col . "|dot_" . $l_row . "_" . $l_col;
		$id_two = "dot_" .$l_row."_".$l_col . "|dot_" . $f_row . "_" . $f_col;
	}
	
	//make call to database for all lines
	$query = "select all_lines from game where game_id = :gameId";
	$params = array('gameId' =>$gameId);
	$res = $conn->getData($query,$params);
	$ls = $res[0]['all_lines'];
	
	if(!empty($ls)){
		$lines = preg_split('/,/', $ls, -1, PREG_SPLIT_NO_EMPTY);
		//$lines = explode(",", $ls);
		$amt = count($lines);
		
		for($i = 0; $i < $amt; $i++){
			if($lines[$i] == $id_one || $lines[$i] == $id_two){
				return true;
			}//end if: do we have a match?
		}//end for: go through all lines
	}//end if: do we have any lines?
	
	return false;
}//end function: lineExists


/**
* can remove this funciton later....
* it just calls whereCheckSquares
*/
function madeSquares($f_row, $f_col, $l_row, $l_col, $conn, $gameId){
	return whereCheckSquares($f_row, $f_col, $l_row, $l_col, $conn, $gameId);
}//end function: madeSquares



function whereCheckSquares($f_row, $f_col, $l_row, $l_col, $conn, $gid){
	$data = getGameDetails($conn, $gid);
	$rows = $data[0]["rows"];
	$cols = $data[0]["cols"];
	
	$coors = array(
		'frow' => $f_row,
		'fcol' => $f_col, 
		'lrow' => $l_row, 
		'lcol' => $l_col
	);
	
	
	//return 'rows: ' . $rows . ", cols: " . $cols . ' | l_col: ' . $l_col . 'f_col: ' . $f_col;
	if($f_col == 0 && $l_col == 0){
		//we only need to check for one square - first column
		//top, bottom, left, right
		return checkSquares(false, false, false, true, $coors, $conn, $gid);
	}//end if: first col?
	
	if($f_col == $cols && $l_col == $cols){
		//we only need to check for one square - last column
		//top, bottom, left, right
		return checkSquares(false, false, true, false, $coors, $conn, $gid);
	}//end if: last col?
	
	if($f_row == 0 && $l_row == 0){
		//we only need to check for one square - first row
		//top, bottom, left, right
		return checkSquares(false, true, false, false, $coors, $conn, $gid);
	}//end if: first row?
	
	if($f_row == $rows && $l_row == $rows){
		//we only need to check for one square - last row
		//top, bottom, left, right
		return checkSquares(true, false, false, false, $coors, $conn, $gid);
	}//end if: last row?
	
	if($f_row == $l_row){
		//they are in the same row which means horizontal - check top and bottom squares
		//top, bottom, left, right
		return checkSquares(true, true, false, false, $coors, $conn, $gid);
	}//end if: same row?
	
	if($f_col == $l_col){
		//they are in the same col which means vertical - check left and right squares
		//top, bottom, left, right
		return checkSquares(false, false, true, true, $coors, $conn, $gid);
	}//end if: same column?
}//end function: whereCheckSquares

/**
* determines which square to check for
* @param {object} the coordinates of the square corners\
* @param conn {object} the database connection
* @param gid {int} the id of the game we care about
* @return {object} any squares you have won
*/
function checkSquares($t, $b, $l, $r, $coors, $conn, $gid){
	$f_row  = (int)$coors['frow'];
	$f_col  = (int)$coors['fcol'];
	$l_row  = (int)$coors['lrow'];
	$l_col  = (int)$coors['lcol'];
	
	$new_squares = "";
	
	if($t){
		//top left dot has same coors as the square
		//so whichever col is smaller is the one we want
		$sq = ($f_col < $l_col) ? "square_" . ($f_row - 1) ."_" . $f_col : "square_" .($l_row - 1) ."_" . $l_col;
		if(!squareAlreadyWon($conn, $gid, $sq)){
			$top_left = "dot_" . ($f_row - 1) . "_" . $f_col;
			$top_right = "dot_" . $f_row . "_" . $f_col;
			$bottom_left = "dot_" . ($l_row - 1) . "_" . $l_col;
			$bottom_right = "dot_" . $l_row . "_" . $l_col;

			if(allSides($top_left, $top_right, $bottom_left, $bottom_right, $conn, $gid)){
				$new_squares .= $sq . ",";
			}
		}//end if: has it been won already?
	}//end if: are we checking for squares on the top?
	
	if($b){
		//top left dot has same coors as the square
		//so whichever col is smaller is the one we want
		$sq = ($f_col < $l_col) ? "square_" . $f_row ."_" . $f_col : "square_" .$l_row ."_" . $l_col;
		if(!squareAlreadyWon($conn, $gid, $sq)){
			$top_left = "dot_" . $f_row . "_" . $f_col;
			$top_right = "dot_" . ($f_row +1) . "_" . $f_col;
			$bottom_left = "dot_" . $l_row . "_" . $l_col;
			$bottom_right = "dot_" . ($l_row + 1) . "_" . $l_col;

			if(allSides($top_left, $top_right, $bottom_left, $bottom_right, $conn, $gid)){
				$new_squares .= $sq . ",";
			}		
		}//end if: has it been won already?
	}//end if: are we checking for squares on the bottom?
	
	if($l){
		//top left dot has same coors as the square
		//so whichever col is smaller is the one we want
		$sq = ($f_row < $l_row) ? "square_" . $f_row ."_" . ($f_col - 1) : "square_" .$l_row ."_" . ($l_col - 1);
		if(!squareAlreadyWon($conn, $gid, $sq)){
			//row, col-1, 		row, col	
			//row, col-1		row, col
			$top_left = "dot_" . $f_row . "_" . ($f_col - 1);
			$top_right = "dot_" . $f_row . "_" . $f_col;
			$bottom_left = "dot_" . $l_row . "_" . ($l_col-1);
			$bottom_right = "dot_" . $l_row . "_" . $l_col;

			if(allSides($top_left, $top_right, $bottom_left, $bottom_right, $conn, $gid)){
				$new_squares .= $sq . ",";
			}
		}//end if: has it been won already?
	}//end if: are we checking for squares on the left?
	
	if($r){
		//top left dot has same coors as the square
		//so whichever col is smaller is the one we want
		$sq = ($f_row < $l_row) ? "square_" . $f_row ."_" . $f_col : "square_" . $l_row ."_" . $l_col;
		if(!squareAlreadyWon($conn, $gid, $sq)){
			//row, col		row, col+1
			//row, col		row, col+1
			$top_right = "dot_" . $f_row . "_" . ($f_col + 1);
			$top_left = "dot_" . $f_row . "_" . $f_col;
			$bottom_right = "dot_" . $l_row . "_" . ($l_col+1);
			$bottom_left = "dot_" . $l_row . "_" . $l_col;
			
			if(allSides($top_left, $top_right, $bottom_left, $bottom_right, $conn, $gid)){
				$new_squares .= $sq . ",";
			}
		}//end if: has it been won already?
	}//end if: are we checking for squares on the right?
	
	if(!empty($new_squares)){
		return preg_split('/,/', $new_squares, -1, PREG_SPLIT_NO_EMPTY);
		//return explode(",",$new_squares);
	}
	return $new_squares;
	
}//end function: checkSquares



/**
* add's a squre based on whether or not there is a square
* @param conn {object} the connection to the database
* @param squares {array} the squares we wanna add
* @param uid {int} the user's id
* @param gid {int} the game's id
* @return {boolean} whether or not the squares were added
*/
function addSquares($conn, $squares, $uid, $gid){
	$query = "select squares from game_players where game_id=:gid and user_id=:uid";
	$params = array("gid"=>$gid, "uid"=>$uid);
	$res = $conn->getData($query,$params);
	$sqs = $res[0][0] . $squares;
	$query = "update game_players set squares=:sqs where game_id=:gid and user_id=:uid";
	$params = array("gid"=>$gid, "uid"=>$uid,"sqs"=>$sqs);
	$conn->setData($query,$params);
	if($conn->getAffectedRows() == 1){
		return true;
	}//end if: did we affect 1 record? (only should be 1)
	return false;
}//end function: addSquares

/**
* Makes sure that the square in question is not already won
* @param conn {object} the database connection_aborted
* @param gid {int} the game id
* @param square_id {string} id of the string
* @return {boolean} whether or not it was won
*/
function squareAlreadyWon($conn, $gid, $square_id){
	$query = "select squares from game_players where game_id=:gid";
	$params = array("gid"=>$gid);
	$res = $conn->getData($query,$params);
	
	$records = count($res);
	
	for($i = 0; $i < $records; $i++){
		$squares = preg_split('/,/', $records[$i], -1, PREG_SPLIT_NO_EMPTY);
		//$squares = explode(",",$records[$i]);
		$sqs = count($squares);
		for($s = 0; $s < $sqs; $s++){
			if($squares[$s] == $square_id){
				return true;
			}//end if: is the id the same?
		}//end for: go through this players squares
	}//end for: go through all players
	
	return false;
}//end function: squareAlreadyWon


/**
* determines if there is a line on all 4 sides of a square
* @param top_left {string} top left dot
* @param top_right {string} top right dot
* @param bottom_left {string} bottom left dot
* @param bottom_right {string} bottom right dot
* @return {boolean} whether or not there is a square
*/
function allSides($top_left,$top_right,$bottom_left,$bottom_right,$conn, $gid){
	
	//all possible lines we could have - account for reversed line ids
	$lines = [
		$top_left."|".$top_right,
		$top_right."|".$top_left,
		$top_left."|".$bottom_left,
		$bottom_left."|".$top_left,
		$top_right."|".$bottom_right,
		$bottom_right."|".$top_right,
		$bottom_left."|".$bottom_right,
		$bottom_right."|".$bottom_left
	];

	
	$lines_total = count($lines);
	$track_amt = 0;//how many lines we have
	for($i = 0; $i < $lines_total; $i=$i+2){
		if(lineExists($lines[$i], $lines[$i+1], "", "", $conn, $gid)){
			$track_amt++;
		}//end if
	}//end for: go through all
	
	if($track_amt == 4){//4 lines == 1 square
		return true;
	}//end if: if we have 4, it's a square

	return false;
}//end function: allSides



?>