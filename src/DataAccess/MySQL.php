<?php

/* 
 * MySQLConnection.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\DataAccess;

use mysqli;
use Exception;
use BlueDB\Configuration\BlueDBProperties;

/**
 * A mysqli wrapper.
 */
class MySQL
{
	// all error codes from MySQL:
	// https://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html
	const ERROR_DUPLICATEENTRY=1062;
	const ERROR_FOREIGNKEYCONSTRAINTS=1451;
	
	/**
	 * @var MySQL
	 */
	private static $instance;
	
	/**
	 * Gets the current connection or opens a new one.
	 * 
	 * Do not use this function, use other static functions to work with the database.
	 * 
	 * @return MySQL
	 */
	public static function Instance()
	{
		if(self::$instance===null)
			self::$instance=new MySQL();
		return self::$instance;
	}
	
	/**
	 * Closes the current connection.
	 */
	public static function close()
	{
		if(self::$instance===null)
			return;
		
		self::$instance->Source->close();
		self::$instance=null;
	}
	
	/**
	 * Source connection between PHP and a MySQL database.
	 * @var mysqli
	 */
	public $Source;
	
	private function __construct()
	{
		$properties=BlueDBProperties::Instance();
		
		$this->Source=new mysqli($properties->MySQL_host, $properties->MySQL_username, $properties->MySQL_password, $properties->MySQL_databaseName);
		
		if($this->Source->connect_errno)
			throw new Exception("Failed to connect to the '".$properties->MySQL_databaseName."' database: [".$this->Source->errno."] ".$this->Source->connect_error,$this->Source->errno);
		
		// tell the server that it should expect UTF-8 encoding from the client, and not just pure ASCII
		$this->Source->set_charset("utf8");
	}
	
	/**
	 * Starts a transaction.
	 */
	public static function beginTransaction()
	{
		$instance=self::Instance();
		
		if(!$instance->Source->begin_transaction())
			throw new Exception("Could not begin transaction: [".$instance->Source->errno."] ".$instance->Source->error,$instance->Source->errno);
	}
	
	/**
	 * Commits the current transaction.
	 */
	public static function commitTransaction()
	{
		$instance=self::Instance();
		
		if(!$instance->Source->commit())
			throw new Exception("Could not commit: [".$instance->Source->errno."] ".$instance->error,$instance->Source->errno);
	}
	
	/**
	 * Rolls back current transaction.
	 */
	public static function rollbackTransaction()
	{
		$instance=self::Instance();
		
		if(!$instance->Source->rollback())
			throw new Exception("Could not roll back transaction: [".$instance->Source->errno."] ".$instance->Source->error,$instance->Source->errno);
	}
	
	/**
	 * Use this function when selecting multiple rows.
	 * 
	 * @param string $selectQuery
	 * @param int $resultMode [optional]
	 * @param int $resultType [optional] The possible values for this parameter are the constants MYSQLI_ASSOC, MYSQLI_NUM, or MYSQLI_BOTH.
	 * @return array
	 * @throws Exception
	 */
	public static function select($selectQuery,$resultMode = MYSQLI_STORE_RESULT,$resultType=MYSQLI_ASSOC)
	{
		$instance=self::Instance();
		
		$result=$instance->Source->query($selectQuery,$resultMode);
		if(!$result)
			throw new Exception("Error while executing select query '".$selectQuery."': [".$instance->Source->errno."] ".$instance->Source->error,$instance->Source->errno);
		
		$array=self::arrayFromResult($result,$resultType);
		return $array;
	}
	
	/**
	 * Use this function when selecting only one row.
	 * 
	 * @param string $selectQuery
	 * @param int $resultMode [optional]
	 * @param int $resultType [optional] The possible values for this parameter are the constants MYSQLI_ASSOC, MYSQLI_NUM, or MYSQLI_BOTH.
	 * @return array
	 * @throws Exception
	 */
	public static function selectSingle($selectQuery,$resultMode = MYSQLI_STORE_RESULT,$resultType=MYSQLI_ASSOC)
	{
		$instance=self::Instance();
		
		$result=$instance->Source->query($selectQuery, $resultMode);
		if(!$result)
			throw new Exception("Error while executing select query '".$selectQuery."': [".$instance->Source->errno."] ".$instance->Source->error,$instance->Source->errno);
		
		if($result->num_rows>1){
			$result->free();
			throw new Exception("The select single statement '".$selectQuery."' did not return only one row.");
		}
		
		$array=self::arrayFromResult($result,$resultType);
		return $array;
	}
	
	/**
	 * @param string $insertQuery
	 */
	public static function insert($insertQuery)
	{
		$instance=self::Instance();
		
		if(!$instance->Source->real_query($insertQuery))
			throw new Exception("Error while executing insert query '".$insertQuery."': [".$instance->Source->errno."] ".$instance->Source->error,$instance->Source->errno);
	}
	
	/**
	 * @param string $updateQuery
	 */
	public static function update($updateQuery)
	{
		$instance=self::Instance();
		
		if(!$instance->Source->real_query($updateQuery))
			throw new Exception("Error while executing update query '".$updateQuery."': [".$instance->Source->errno."] ".$instance->Source->error,$instance->Source->errno);
	}
	
	/**
	 * Returns the ID generated by a query on a table with a column having the AUTO_INCREMENT attribute. If the last query wasn't an INSERT or UPDATE statement or if the modified table does not have a column with the AUTO_INCREMENT attribute, this function will return zero.
	 * 
	 * @return int
	 */
	public static function autogeneratedID()
	{
		$instance=self::Instance();
		
		return $instance->Source->insert_id;
	}
	
	/**
	 * @param string $setQuery
	 */
	public static function set($setQuery)
	{
		$instance=self::Instance();
		
		if(!$instance->Source->real_query($setQuery))
			throw new Exception("Error while executing set query '".$setQuery."': [".$instance->Source->errno."] ".$instance->Source->error,$instance->Source->errno);
	}
	
	/**
	 * Try to use other functions as much as possible.
	 * 
	 * @param string $query
	 */
	public static function query($query)
	{
		$instance=self::Instance();
		
		if(!$instance->Source->real_query($query))
			throw new Exception("Error while executing query '".$query."': [".$instance->Source->errno."] ".$instance->Source->error,$instance->Source->errno);
	}
	
	/**
	 * Try to use other functions as much as possible.
	 * 
	 * @param string $queries Multiple queries which are concatenated by a semicolon.
	 */
	public static function queryMulti($queries)
	{
		$instance=self::Instance();
		
		if(!$instance->Source->multi_query($queries))
			throw new Exception("Error while executing the first statement of multi queries '$queries': [".$instance->Source->errno."] ".$instance->Source->error,$instance->Source->errno);
		
		while($instance->Source->more_results()){
			if($instance->Source->next_result())
				continue;
			
			throw new Exception("Error while executing one of the statements of multi queries '$queries': [".$instance->Source->errno."] ".$instance->Source->error,$instance->Source->errno);
		}
	}
	
	/**
	 * @param string $deleteQuery
	 */
	public static function delete($deleteQuery)
	{
		$instance=self::Instance();
		
		if(!$instance->Source->real_query($deleteQuery))
			throw new Exception("Error while executing delete query '".$deleteQuery."': [".$instance->Source->errno."] ".$instance->Source->error,$instance->Source->errno);
	}
	
	/**
	 * @param string $sqlPreparedStatement
	 * @param array $parameters
	 * @return mysqli_stmt
	 * @throws Exception
	 */
	public static function prepareAndExecuteStatement($sqlPreparedStatement,$parameters)
	{
		$instance=self::Instance();
		
		$stmt=$instance->Source->prepare($sqlPreparedStatement);
		if(!$stmt)
			throw new Exception("Error while preparing statement '".$sqlPreparedStatement."': [".$instance->Source->errno."] ".$instance->Source->error,$instance->Source->errno);
		
		// bind parameter array
		if(!call_user_func_array(array($stmt,"bind_param"), $parameters))
			throw new Exception("Error while binding parameters to the prepared statement.");
		
		// execute
		if(!$stmt->execute())
			throw new Exception("Error while executing prepared statement '".$sqlPreparedStatement."': [".$stmt->errno."] ".$stmt->error,$stmt->errno);
		
		return $stmt;
	}
	
	/**
	 * Use this function for SELECT statements which can return multiple rows.
	 * 
	 * @param string $sqlPreparedSelectStatement
	 * @param array $parameters
	 * @param int $resultType [optional] The possible values for this parameter are the constants MYSQLI_ASSOC, MYSQLI_NUM, or MYSQLI_BOTH.
	 * @return array
	 */
	public static function prepareAndExecuteSelectStatement($sqlPreparedSelectStatement,$parameters,$resultType=MYSQLI_ASSOC)
	{
		$instance=self::Instance();
		
		$result=$instance->prepareAndExecuteStatementGetResult($sqlPreparedSelectStatement, $parameters);
		$retArray=self::arrayFromResult($result, $resultType);
		return $retArray;
	}
	
	/**
	 * Use this function for SELECT statements which want only a single row returned.
	 * 
	 * @param string $sqlPreparedSelectStatement
	 * @param array $parameters
	 * @param int $resultType [optional] The possible values for this parameter are the constants MYSQLI_ASSOC, MYSQLI_NUM, or MYSQLI_BOTH.
	 * @return array
	 */
	public static function prepareAndExecuteSelectSingleStatement($sqlPreparedSelectStatement,$parameters,$resultType=MYSQLI_ASSOC)
	{
		$instance=self::Instance();
		
		$result=$instance->prepareAndExecuteStatementGetResult($sqlPreparedSelectStatement, $parameters);
		if($result->num_rows>1){
			$result->free();
			throw new Exception("The single statement '".$sqlPreparedSelectStatement."' did not return only one row.");
		}else if($result->num_rows==0)
			return null;
		
		$retArray=self::arrayFromResult($result, $resultType);
		return $retArray[0];
	}
	
	/**
	 * @param string $sqlPreparedStatement
	 * @param array $parameters
	 * @return mysqli_result
	 */
	private function prepareAndExecuteStatementGetResult($sqlPreparedStatement,$parameters)
	{
		$stmt=self::prepareAndExecuteStatement($sqlPreparedStatement, $parameters);
		// get result
		$result=$stmt->get_result();
		if(!$result)
			throw new Exception("Error while getting result for prepared statement '".$sqlPreparedStatement."': [".$stmt->errno."] ".$stmt->error);
		
		return $result;
	}
	
	/**
	 * @param mysqli_result $mysqliResult
	 * @param int $resultType The possible values for this parameter are the constants MYSQLI_ASSOC, MYSQLI_NUM, or MYSQLI_BOTH.
	 * @return array
	 */
	private static function arrayFromResult($mysqliResult,$resultType)
	{
		$array=$mysqliResult->fetch_all($resultType);
		$mysqliResult->free();
		return $array;
	}
}
