<?php

/*
 * AssociativeTrait.php
 * 
 * Copyright 2018 Grega Mohorko
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 29, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Criteria\Expression;
use BlueDB\DataAccess\MySQL;
use BlueDB\DataAccess\Session;
use Exception;

trait AssociativeTrait
{
	/**
	 * Returns the opposite side of the specified side.
	 * 
	 * @param string $side
	 * @return string
	 */
	public static function getOppositeSide($side)
	{
		$calledClass=get_called_class();
		$sideA=$calledClass::getSideA();
		$sideB=$calledClass::getSideB();
		if($side===$sideA){
			return $sideB;
		}
		if($side===$sideB){
			return $sideA;
		}
		throw new Exception("The specified side '$side' does not exist in associative entity '$calledClass'.");
	}
	
	/**
	 * Loads a list of entities for the provided origin side.
	 * 
	 * For example: If origin side A is provided, objects of type B will be loaded. And vice versa.
	 * 
	 * @param string $originSide
	 * @param int $ID
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclManyToOne [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return array
	 */
	public static function loadListForSide($originSide, $ID, $fields=null, $fieldsToIgnore=null, $inclManyToOne=null, $inclOneToMany=null, $inclManyToMany=null)
	{
		self::checkConfig($inclManyToOne,$inclOneToMany,$inclManyToMany);
		$calledClass=get_called_class();
		$session=new Session();
		return $calledClass::loadListForSideInternal($originSide,$ID,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session);
	}
	
	/**
	 * @param string $originSide
	 * @param int $ID
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclManyToOne
	 * @param bool $inclOneToMany
	 * @param bool $inclManyToMany
	 * @param Session $session
	 * @return array
	 */
	protected static function loadListForSideInternal($originSide,$ID,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session)
	{
		$calledClass=get_called_class();
		$criteria=new Criteria($calledClass);
		return $calledClass::loadListForSideByCriteriaInternal($originSide,$ID,$criteria,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session);
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
	 * @param bool $inclManyToOne [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return array
	 */
	public static function loadListForSideByCriteria($originSide, $ID, $criteria, $fields=null, $fieldsToIgnore=null, $inclManyToOne=null, $inclOneToMany=null, $inclManyToMany=null)
	{
		self::checkConfig($inclManyToOne,$inclOneToMany,$inclManyToMany);
		$calledClass=get_called_class();
		$session=new Session();
		return $calledClass::loadListForSideByCriteriaInternal($originSide,$ID,$criteria,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session);
	}
	
	/**
	 * @param string $originSide
	 * @param int $ID
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclManyToOne
	 * @param bool $inclOneToMany
	 * @param bool $inclManyToMany
	 * @param Session $session
	 * @return array
	 */
	protected static function loadListForSideByCriteriaInternal($originSide, $ID, $criteria, $fields, $fieldsToIgnore, $inclManyToOne, $inclOneToMany, $inclManyToMany, $session)
	{
		$calledClass=get_called_class();
		
		$sideToLoad=$calledClass::getOppositeSide($originSide);
		
		$toLoadBaseConstName="$calledClass::$sideToLoad";
		$toLoadClass=constant($toLoadBaseConstName."Class");
		$joinColumn=constant($toLoadBaseConstName."Column");
		
		$originColumn=constant("$calledClass::$originSide"."Column");
		$baseEntityTableName=$calledClass::getTableName();
		$criteria->add(Expression::custom($calledClass,"$baseEntityTableName.$originColumn=?",[[$ID, PropertyTypeEnum::INT]]));
		
		$parentClass=null;
		$parentFieldName=null;
		$isSubEntity=is_subclass_of($toLoadClass, SubEntity::class);
		if($isSubEntity){
			$parentClass=$toLoadClass::getParentEntityClass();
			$parentFieldName=$toLoadClass::getParentFieldName();
		}
		
		$manyToOneFieldsToLoad=null;
		$oneToManyListsToLoad=null;
		$manyToManyListsToLoad=null;
		$fieldsOfParent=null;
		$query=self::prepareSelectQuery($calledClass,$toLoadClass,$joinColumn,$criteria,$fields,$fieldsToIgnore,$manyToOneFieldsToLoad,$inclManyToOne,$inclOneToMany,$oneToManyListsToLoad,$inclManyToMany,$manyToManyListsToLoad,$isSubEntity,$parentFieldName,$fieldsOfParent);
		
		$loadedArrays=self::executeSelectQuery($query,$criteria);
		if(empty($loadedArrays)){
			return [];
		}
		
		$loadedEntities=[];
		
		foreach($loadedArrays as $array){
			$loadedEntities[]=self::createInstance($toLoadClass,$array,$manyToOneFieldsToLoad,$oneToManyListsToLoad, $manyToManyListsToLoad,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session,$isSubEntity,$parentClass,$parentFieldName,$fieldsOfParent,$fieldsToIgnore);
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
			
			if(!$isFirst){
				$query.=",";
			} else{
				$isFirst=false;
			}
			
			$query.=" (?,?)";
			$parameters[0].="ii";
			$ids[$i]=$object->getID();
			$parameters[]=&$originID;
			$parameters[]=&$ids[$i];
			++$i;
		}
		
		if($beginTransaction){
			MySQL::beginTransaction();
		}
		
		try{
			MySQL::prepareAndExecuteStatement($query, $parameters);
		} catch (Exception $ex) {
			MySQL::rollbackTransaction();
			throw $ex;
		}
		
		if($commit){
			MySQL::commitTransaction();
		}
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
		
		if($beginTransaction){
			MySQL::beginTransaction();
		}
		
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
		
		if($commit){
			MySQL::commitTransaction();
		}
	}
}
