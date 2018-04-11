<?php

/*************************************************************
* @desc		The database class to interact with the database
* @date		10/05/16
* @author	et5392[et5392@rit.edu](Erika Tobias)
*************************************************************/



class DB{
	private $dbh;
	private $last_id;
	private $affected_rows;
	private $error;
	
	function __construct(){
		require_once('../../../dbInfoPS.inc');
		
		try{
			//open connection
			$this->dbh = new PDO("mysql:host=$host;dbname=$db",$user,$pass);
			//change error reporting
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		}catch(PDOException $e){
			echo $e->getMessage();
			die();
		}//end try/catch		
		
	}//end constructor
	
	/**
	* closes the database connection
	*/
	function closeConnection(){
		$this->dbh = null;
	}//end function: closeConnection
	
	/**
	* Returns the last inserted id
	* @returns last_id {int} the id generated from an insert, update, or delete
	*/
	function getLastId(){
		return $this->last_id;
	}//end function: getLastId
	
	/**
	* Returns the amount of rows affected
	* @returns affected_rows {int} amount of rows affected by an insert, update, or delete
	*/
	function getAffectedRows(){
		return $this->affected_rows;
	}//end function: getAffectedRows
	
	
	/**
	* This gets all of any object you request
	* @param $object {String} name of object, with first letter capitalized
	* @param $query {String} any additional restrictions to add to the query
	* @param $params {array} the list of parameters to bind
	* @returns $data {array} records obtained from the query
	*/
	function getAll($object, $query, $params){
		try{
			$obj_lower = strtolower($object);
			include_once("$obj_lower.class.php");
			$data = array();
			$stmt = $this->dbh->prepare("select * from $obj_lower"."s " . $query);
			$stmt->execute($params);
			$stmt->setFetchMode(PDO::FETCH_CLASS, $object);
			while($obj = $stmt->fetch()){
				$data[] = $obj;
			}//end while
			return $data;
		}catch(PDOException $e){
			$this->error = $e->getMessage();
			die();
		}//end try/catch
	}//end function: getAll
	
	
	/**
	* This takes a insert, update, delete statement and executes it
	* this will set the affected_rows and last_id properties
	* @param $query {String} the query to run
	* @param $params {array} the list of parameters to bind
	* @returns boolean whether or not it worked
	*/
	function setData($query, $params){
		$this->affected_rows = 0;
		try{
			$stmt = $this->dbh->prepare($query);
			if(count($params) > 0){
				$stmt->execute($params);
			}//end if: did they pass in params?
			$this->last_id = $this->dbh->lastInsertId();
			$this->affected_rows = $stmt->rowCount();
			return true;
		}catch(PDOException $pdoe){
			$this->error = $pdoe->getMessage();
			die();
		}//end try/catch:
			
	}//end function: setData
	
	
	/**
	* This takes a select statement and executes it
	* @param $query {String} the query to run
	* @param $params {array} the list of parameters to bind
	* @returns $data {array} records obtained from the query
	*/
	function getData($query, $params){
		try{
			$stmt = $this->dbh->prepare($query);
			if(count($params) > 0){
				$stmt->execute($params);
			}//end if: did they pass in params?
			while($row = $stmt->fetch()){
				$data[] = $row;
			}//end while

			return $data;
		}catch(PDOException $pdoe){
			$this->error = $pdoe->getMessage();
			die();
		}//end try/catch:
	}//end function: getData

	function getError(){
		return $this->error;
	}
	
}//end class: db








