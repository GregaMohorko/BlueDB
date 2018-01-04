<?php

/*
 * StrongEntity.php
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
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

use Exception;
use BlueDB\DataAccess\MySQL;
use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Criteria\Expression;
use BlueDB\DataAccess\Session;

abstract class StrongEntity extends FieldEntity
{
	/**
	 * @var int
	 */
	public $ID;
	const IDField="ID";
	const IDFieldType=FieldTypeEnum::PROPERTY;
	const IDColumn=self::IDField;
	const IDPropertyType=PropertyTypeEnum::INT;
	
	/**
	 * @return int
	 */
	public function getID()
	{
		return $this->ID;
	}
	
	/**
	 * @param int $ID
	 */
	public function setID($ID)
	{
		$this->ID=$ID;
	}
	
	/**
	 * @return string
	 */
	public static function getIDColumn()
	{
		return self::IDColumn;
	}
	
	/**
	 * Creates an empty instance of this StrongEntity. Simply creates a new object of the called entity class.
	 * 
	 * @return StrongEntity
	 */
	public static function createEmpty()
	{
		$calledClass=get_called_class();
		$entity=new $calledClass();
		return $entity;
	}
	
	/**
	 * @param int $ID
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclManyToOne
	 * @param bool $inclOneToMany
	 * @param bool $inclManyToMany
	 * @param Session $session
	 * @return StrongEntity
	 */
	protected static function loadByIDInternal($ID,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session)
	{
		$childClassName=get_called_class();
		
		$criteria=new Criteria($childClassName);
		$criteria->add(Expression::equal($childClassName, self::IDField, $ID));
		
		return $childClassName::loadByCriteriaInternal($criteria,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session);
	}
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclManyToOne
	 * @param bool $inclOneToMany
	 * @param bool $inclManyToMany
	 * @param Session $session
	 * @return StrongEntity
	 */
	protected static function loadByCriteriaInternal($criteria, $fields, $fieldsToIgnore, $inclManyToOne, $inclOneToMany, $inclManyToMany, $session)
	{
		$childClassName=get_called_class();
		
		$manyToOneFieldsToLoad=null;
		$oneToManyListsToLoad=null;
		$manyToManyListsToLoad=null;
		$fieldsOfParent=null;
		$query=self::prepareSelectQuery($childClassName, $childClassName, null, $criteria, $fields, $fieldsToIgnore,$manyToOneFieldsToLoad,$inclManyToOne,$inclOneToMany,$oneToManyListsToLoad,$inclManyToMany,$manyToManyListsToLoad,false,null,$fieldsOfParent);
		
		$loadedArray=self::executeSelectSingleQuery($query, $criteria);
		if($loadedArray===null){
			return null;
		}
		
		$loadedEntity=self::createInstance($childClassName, $loadedArray,$manyToOneFieldsToLoad,$oneToManyListsToLoad,$manyToManyListsToLoad,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session,false,null,null,null,null);
		
		return $loadedEntity;
	}
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclManyToOne
	 * @param bool $inclOneToMany
	 * @param bool $inclManyToMany
	 * @param Session $session
	 * @return array
	 */
	protected static function loadListByCriteriaInternal($criteria, $fields, $fieldsToIgnore, $inclManyToOne, $inclOneToMany, $inclManyToMany, $session)
	{
		$childClassName=get_called_class();
		
		$manyToOneFieldsToLoad=null;
		$oneToManyListsToLoad=null;
		$manyToManyListsToLoad=null;
		$fieldsOfParent=null;
		$query=self::prepareSelectQuery($childClassName, $childClassName, null, $criteria, $fields, $fieldsToIgnore,$manyToOneFieldsToLoad,$inclManyToOne,$inclOneToMany,$oneToManyListsToLoad,$inclManyToMany,$manyToManyListsToLoad,false,null,$fieldsOfParent);
		
		$loadedArrays=self::executeSelectQuery($query, $criteria);
		if(empty($loadedArrays)){
			return [];
		}
		
		$loadedEntities=[];
		
		foreach($loadedArrays as $array){
			$loadedEntities[]=self::createInstance($childClassName, $array,$manyToOneFieldsToLoad,$oneToManyListsToLoad,$manyToManyListsToLoad,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session,false,null,null,null,null);
		}
		
		return $loadedEntities;
	}
	
	/**
	 * Does not save ManyToOne fields, only sets the ID.
	 * Does not save OneToMany & ManyToMany fields.
	 * 
	 * @param StrongEntity $strongEntity
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	public static function save($strongEntity,$beginTransaction=true,$commit=true)
	{
		$calledClass=get_called_class();
		self::performQuery(QueryTypeEnum::INSERT, $calledClass, $strongEntity, null, $beginTransaction, $commit,false,false);
	}
	
	/**
	 * Does not update ManyToOne fields, only sets the ID.
	 * Does not update OneToMany & ManyToMany fields.
	 * 
	 * @param StrongEntity $strongEntity
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 * @param array $fields [optional]
	 * @param bool $updateParents [optional] Only important for SubEntities. It determines whether to update parent tables.
	 */
	public static function update($strongEntity,$beginTransaction=true,$commit=true,$fields=null,$updateParents=true)
	{
		if($strongEntity===null){
			// at least open/close the transaction, if it needs to be
			
			if($beginTransaction&&!$commit){
				MySQL::beginTransaction();
			} else if(!$beginTransaction&&$commit){
				MySQL::commitTransaction();
			}
			
			return;
		}
		
		$calledClass=get_called_class();
		self::performQuery(QueryTypeEnum::UPDATE, $calledClass, $strongEntity, $fields, $beginTransaction, $commit,false,false);
	}
	
	/**
	 * Does not delete child ManyToOne fields.
	 * 
	 * @param StrongEntity $strongEntity
	 * @param bool $beginTransaction
	 * @param bool $commit
	 * @param Session $session
	 */
	protected static function deleteInternal($strongEntity,$beginTransaction,$commit,$session)
	{
		if($strongEntity===null){
			// at least open/close the transaction, if it needs to be
			
			if($beginTransaction&&!$commit){
				MySQL::beginTransaction();
			} else if(!$beginTransaction&&$commit){
				MySQL::commitTransaction();
			}
			
			return;
		}
		
		if($strongEntity->ID==null){
			throw new Exception("The provided objects ID is null. What are you trying to delete?");
		}
		
		$childClassName=get_class($strongEntity);
		
		self::prepareForDeletion($childClassName,$strongEntity,$session,$beginTransaction);
		
		$tableName=$childClassName::getTableName();
		
		$query="DELETE FROM ".$tableName." WHERE ID=?";
		
		$parameters=[];
		$parameters[]="i";
		$parameters[]=&$strongEntity->ID;
		
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
}
