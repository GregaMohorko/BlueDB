<?php

/*
 * AssociativeEntity.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 26, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

use Exception;
use BlueDB\DataAccess\MySQL;
use BlueDB\DataAccess\Session;
use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Criteria\Expression;

abstract class AssociativeEntity extends DatabaseTable implements IAssociativeEntity
{
	/**
	 * Loads a list of entities for the provided origin side.
	 * 
	 * For example: If origin side A is provided, objects of type B will be loaded. And vice versa.
	 * 
	 * @param string $originSide
	 * @param int $ID
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclOneToMany [optional]
	 * @return array
	 */
	public static function loadListForSide($originSide, $ID, $fields=null, $fieldsToIgnore=null, $inclOneToMany=true)
	{
		$calledClass=get_called_class();
		$session=new Session();
		return $calledClass::loadListForSideInternal($originSide,$ID,$fields,$fieldsToIgnore,$inclOneToMany,$session);
	}
	
	/**
	 * @param string $originSide
	 * @param int $ID
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @param Session $session
	 * @return array
	 */
	private static function loadListForSideInternal($originSide,$ID,$fields,$fieldsToIgnore,$inclOneToMany,$session)
	{
		$calledClass=get_called_class();
		$criteria=new Criteria($calledClass);
		return $calledClass::loadListForSideByCriteriaInternal($originSide,$ID,$criteria,$fields,$fieldsToIgnore,$inclOneToMany,$session);
	}

	/**
	 * Loads a list of entities by criteria for the provided origin side.
	 * 
	 * For example: If origin side A is provided, objects of type B will be loaded. And vice versa.
	 * 
	 * @param string $originSide
	 * @param int $ID
	 * @param Criteria $criteria
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclOneToMany [optional]
	 * @return array
	 */
	public static function loadListForSideByCriteria($originSide, $ID, $criteria, $fields=null, $fieldsToIgnore=null, $inclOneToMany=true)
	{
		$calledClass=get_called_class();
		$session=new Session();
		return $calledClass::loadListForSideByCriteriaInternal($originSide,$ID,$criteria,$fields,$fieldsToIgnore,$inclOneToMany,$session);
	}
	
	/**
	 * @param string $originSide
	 * @param int $ID
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @param Session $session
	 * @return array
	 */
	private static function loadListForSideByCriteriaInternal($originSide, $ID, $criteria, $fields, $fieldsToIgnore, $inclOneToMany,$session)
	{
		$calledClass=get_called_class();
		
		$sideA=$calledClass::getSideA();
		$sideB=$calledClass::getSideB();
		
		// determine which side to load
		if($originSide===$sideA)
			$sideToLoad=$sideB;
		else if($originSide===$sideB)
			$sideToLoad=$sideA;
		else
			throw new Exception("The specified side '$originSide' does not exist in associative entity '$calledClass'.");
		
		$toLoadBaseConstName="$calledClass::$sideToLoad";
		$toLoadClass=constant($toLoadBaseConstName."Class");
		$joinColumn=constant($toLoadBaseConstName."Column");
		
		$originColumn=constant("$calledClass::$originSide"."Column");
		$baseEntityTableName=$calledClass::getTableName();
		$criteria->add(Expression::custom($calledClass,"$baseEntityTableName.$originColumn=?",[[$ID, PropertyTypeEnum::INT]]));
		
		$manyToOneFieldsToLoad=null;
		$oneToManyListsToLoad=null;
		$fieldsOfParent=null;
		$query=self::prepareSelectQuery($calledClass,$toLoadClass,$joinColumn,$criteria,$fields,$fieldsToIgnore,$manyToOneFieldsToLoad,$inclOneToMany,$oneToManyListsToLoad,false,null,$fieldsOfParent);
		
		$loadedArrays=self::executeSelectQuery($query,$criteria);
		if(empty($loadedArrays))
			return [];
		
		$loadedEntities=[];
		
		$addToSession=self::shouldAddToSession($fields, $fieldsToIgnore, $inclOneToMany);
		foreach($loadedArrays as $array){
			$loadedEntities[]=self::createInstance($toLoadClass,$array,$manyToOneFieldsToLoad,$oneToManyListsToLoad,$addToSession,$session,false,null,null,null);
		}
		
		return $loadedEntities;
	}
	
	/**
	 * Links two objects.
	 * 
	 * @param IFieldEntity $AObject
	 * @param IFieldEntity $BObject
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	public static function link($AObject, $BObject, $beginTransaction=true, $commit=true)
	{
		$calledClass=get_called_class();
		$tableName=$calledClass::getTableName();
		
		$query="INSERT INTO $tableName VALUES(?,?)";
		
		self::executeQuery($query, $AObject, [$BObject], $beginTransaction, $commit);
	}

	/**
	 * Links a B object with multiple A objects.
	 * 
	 * @param IFieldEntity $BObject
	 * @param array $AList
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	public static function linkMultipleA($BObject, $AList, $beginTransaction=true, $commit=true)
	{
		$calledClass=get_called_class();
		$tableName=$calledClass::getTableName();
		$sideA=$calledClass::getSideA();
		$sideB=$calledClass::getSideB();
		$columnA=constant("$calledClass::$sideA"."Column");
		$columnB=constant("$calledClass::$sideB"."Column");
		
		$query="INSERT INTO $tableName ($columnB,$columnA) VALUES";
		
		self::linkMultiple($query,$BObject,$AList,$beginTransaction,$commit);
	}
	
	/**
	 * Links an A object with multiple B objects.
	 * 
	 * @param IFieldEntity $AObject
	 * @param array $BList
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	public static function linkMultipleB($AObject, $BList, $beginTransaction=true, $commit=true)
	{
		$calledClass=get_called_class();
		$tableName=$calledClass::getTableName();
		$sideA=$calledClass::getSideA();
		$sideB=$calledClass::getSideB();
		$columnA=constant("$calledClass::$sideA"."Column");
		$columnB=constant("$calledClass::$sideB"."Column");
		
		$query="INSERT INTO $tableName ($columnA,$columnB) VALUES";
		
		self::linkMultiple($query, $AObject, $BList, $beginTransaction, $commit);
	}
	
	/**
	 * Unlinks two objects.
	 * 
	 * @param IFieldEntity $AObject
	 * @param IFieldEntity $BObject
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	public static function unlink($AObject, $BObject, $beginTransaction=true, $commit=true)
	{
		$calledClass=get_called_class();
		$tableName=$calledClass::getTableName();
		$sideA=$calledClass::getSideA();
		$sideB=$calledClass::getSideB();
		$columnA=constant("$calledClass::$sideA"."Column");
		$columnB=constant("$calledClass::$sideB"."Column");
		
		$query="DELETE FROM $tableName WHERE $columnA=? AND $columnB=?";
		
		self::executeQuery($query, $AObject, [$BObject], $beginTransaction, $commit);
	}

	/**
	 * Unlinks a B object with multiple A objects.
	 * 
	 * @param IFieldEntity $BObject
	 * @param array $AList
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	public static function unlinkMultipleA($BObject, $AList, $beginTransaction=true, $commit=true)
	{
		$calledClass=get_called_class();
		$tableName=$calledClass::getTableName();
		$sideA=$calledClass::getSideA();
		$sideB=$calledClass::getSideB();
		$columnA=constant("$calledClass::$sideA"."Column");
		$columnB=constant("$calledClass::$sideB"."Column");
		
		$query="DELETE FROM $tableName WHERE $columnB=? AND $columnA=?";
		
		self::executeQuery($query, $BObject, $AList, $beginTransaction, $commit);
	}

	/**
	 * Unlinks an A object with multiple B objects.
	 * 
	 * @param IFieldEntity $AObject
	 * @param array $BList
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	public static function unlinkMultipleB($AObject, $BList, $beginTransaction=true, $commit=true)
	{
		$calledClass=get_called_class();
		$tableName=$calledClass::getTableName();
		$sideA=$calledClass::getSideA();
		$sideB=$calledClass::getSideB();
		$columnA=constant("$calledClass::$sideA"."Column");
		$columnB=constant("$calledClass::$sideB"."Column");
		
		$query="DELETE FROM $tableName WHERE $columnA=? AND $columnB=?";
		
		self::executeQuery($query, $AObject, $BList, $beginTransaction, $commit);
	}

	/**
	 * @param string $query
	 * @param IFieldEntity $originObject
	 * @param array $objects
	 * @param bool $beginTransaction
	 * @param bool $commit
	 */
	private static function linkMultiple($query,$originObject,$objects,$beginTransaction,$commit)
	{
		$originID=$originObject->getID();
		$ids=[];
		$i=0;
		
		$parameters=[];
		$parameters[0]="";
		
		$isFirst=true;
		foreach($objects as $object){
			/* @var $object IFieldEntity */
			
			if(!$isFirst)
				$query.=",";
			else
				$isFirst=false;
			
			$query.=" (?,?)";
			$parameters[0].="ii";
			$ids[$i]=$object->getID();
			$parameters[]=&$originID;
			$parameters[]=&$ids[$i];
			++$i;
		}
		
		if($beginTransaction)
			MySQL::beginTransaction();
		
		try{
			MySQL::prepareAndExecuteStatement($query, $parameters);
		} catch (Exception $ex) {
			MySQL::rollbackTransaction();
			throw $ex;
		}
		
		if($commit)
			MySQL::commitTransaction();
	}
	
	/**
	 * @param string $query
	 * @param IFieldEntity $originObject
	 * @param array $objects
	 * @param bool $beginTransaction
	 * @param bool $commit
	 */
	private static function executeQuery($query,$originObject,$objects,$beginTransaction,$commit)
	{
		$originID=$originObject->getID();
		
		// parameters for prepared statement
		$parameters=[];
		$parameters[]="ii";
		$parameters[]=&$originID;
		
		if($beginTransaction)
			MySQL::beginTransaction();
		
		try{
			$ids=[];
			$i=0;
			foreach($objects as $object){
				/* @var $object IFieldEntity */
				$ids[$i]=$object->getID();
				$parameters[2]=&$ids[$i];
				++$i;
				MySQL::prepareAndExecuteStatement($query, $parameters);
			}
		} catch (Exception $ex) {
			MySQL::rollbackTransaction();
			throw $ex;
		}
		
		if($commit)
			MySQL::commitTransaction();
	}
}
