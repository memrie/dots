<?php
/****************************************************************
* @desc		this file contains basic functionality for login/signup
*			actions as well as logout
* 
* @author	erika tobias (et5392@rit.edu)
* @date		October 18th, 2017
* @updated	December 16th, 2017
****************************************************************/

//import dependencies
require("./business/general.php");
	
	
/**
* accepts a login request
* @param d {array} the param we care about
* @return {JSON} the 
*/
function reqLogin($d){
	$ip = $_SERVER['REMOTE_ADDR'];

	$res = login($d['unm'], $d['pwd'], $ip);
	return $res;
}//end function: reqLogin

/**
* handles a signup request
* @param d {array} the param we care about
* @return {JSON} the response from the database
*/
function reqSignup($d){
	$ip = $_SERVER['REMOTE_ADDR'];
	$desc;
	$unm = $d[unm];
	$pwd = $d[pwd];
	$pwd_again = $d[pwd_again];
	$email = $d[email];
	$email_again = $d[email_again];
	
	$unm_errors = checkUsername($unm);
	$email_errors = checkEmail($email, $email_again);
	$pwd_errors = checkPassword($pwd, $pwd_again);
	
	if(!empty($email_errors) or !empty($pwd_errors) or !empty($unm_errors)){
		return errorResp($email_errors . $pwd_errors . $unm_errors);
	}//end if: does something have errors?
	
	return signup($unm, $pwd, $email,$ip);
}//end function: reqSignup

/**
* handles a logout request
* @param d {array} the param we care about
* @return {JSON} the response from the database
*/
function reqLogout($d){
	$unm = $d['unm'];
	$sid = $d['sid'];
	return logout($unm, $sid);
}//end function: Logout



	
?>