<?php

/*
 * SubEntity.php
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
 * @copyright Mar 15, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Criteria\Expression;
use BlueDB\DataAccess\MySQL;
use BlueDB\DataAccess\Session;
use BlueDB\Utility\ArrayUtility;
use Exception;

abstract class SubEntity extends FieldEntity implements ISubEntity
{
	/**
	 * @return int
	 */
	public function getID()
	{
		$baseStrongEntity=$this->getBaseStrongEntity();
		if($baseStrongEntity===null){
			throw new Exception("Trying to get ID of a sub entity without parent.");
		}
		return $baseStrongEntity->ID;
	}
	
	/**
	 * @param int $ID
	 */
	public function setID($ID)
	{
		$baseStrongEntity=$this->getBaseStrongEntity();
		if($baseStrongEntity===null){
			return;
		}
		$baseStrongEntity->ID=$ID;
	}
	
	/**
	 * Lookup table for base strong entity classes of SubEntity classes.
	 * 
	 * @var array
	 */
	private static $baseStrongEntityClasses=[];
	
	/**
	 * Returns the base super class of this sub-entity. A base class is always a StrongEntity.
	 * 
	 * @return string Name of the StrongEntity class.
	 */
	public static function getBaseStrongEntityClass()
	{
		$childClassName=get_called_class();
		
		// search in lookup table
		if(isset(self::$baseStrongEntityClasses[$childClassName])){
			return self::$baseStrongEntityClasses[$childClassName];
		}
		
		$current=$childClassName::getParentEntityClass();
		while(!is_subclass_of($current, StrongEntity::class)){
			$current=$current::getParentEntityClass();
		}
		
		// save to lookup table
		self::$baseStrongEntityClasses[$childClassName]=$current;
		
		return $current;
	}
	
	/**
	 * @return StrongEntity
	 */
	private function getBaseStrongEntity()
	{
		$childClassName=get_called_class();
		
		$currentClass=$childClassName::getParentEntityClass();
		$parentFieldName=$childClassName::getParentFieldName();
		$currentValue=$this->$parentFieldName;
		
		while(true){
			if(is_subclass_of($currentClass, StrongEntity::class)){
				return $currentValue;
			}
			
			// is still a SubEntity, go further up
			$parentFieldName=$currentClass::getParentFieldName();
			$currentValue=$currentValue->$parentFieldName;
			$currentClass=$currentClass::getParentEntityClass();
		}
	}
	
	/**
	 * Creates an empty instance of this SubEntity, with initialized parents.
	 * 
	 * @return SubEntity
	 */
	public static function createEmpty()
	{
		$calledClass=get_called_class();
		
		$entity=new $calledClass();
		$parentFieldName=$calledClass::getParentFieldName();
		$currentClass=$calledClass::getParentEntityClass();
		$currentValue=new $currentClass();
		$entity->$parentFieldName=$currentValue;
		
		while(true){
			if(is_subclass_of($currentClass, StrongEntity::class)){
				break;
			}
			
			$parentFieldName=$currentClass::getParentFieldName();
			$currentClass=$currentClass::getParentEntityClass();
			$newValue=new $currentClass();
			$currentValue->$parentFieldName=$newValue;
			$currentValue=$newValue;
		}
		
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
	 * @return SubEntity
	 */
	protected static function loadByIDInternal($ID,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session)
	{
		$childClassName=get_called_class();
		
		$criteria=new Criteria($childClassName);
		$criteria->add(Expression::equal($childClassName, StrongEntity::IDField, $ID, $childClassName::getBaseStrongEntityClass()));
		
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
	 * @return SubEntity
	 */
	protected static function loadByCriteriaInternal($criteria, $fields, $fieldsToIgnore, $inclManyToOne, $inclOneToMany, $inclManyToMany, $session)
	{
		$childClassName=get_called_class();
		
		$parentFieldName=$childClassName::getParentFieldName();
		
		$manyToOneFieldsToLoad=null;
		$oneToManyListsToLoad=null;
		$manyToManyListsToLoad=null;
		$fieldsOfParent=null;
		$query=self::prepareSelectQuery($childClassName, $childClassName, null, $criteria, $fields, $fieldsToIgnore, $manyToOneFieldsToLoad, $inclManyToOne, $inclOneToMany, $oneToManyListsToLoad, $inclManyToMany, $manyToManyListsToLoad,true,$parentFieldName,$fieldsOfParent);
		
		$loadedArray=self::executeSelectSingleQuery($query, $criteria);
		if($loadedArray===null){
			return null;
		}
		if(count($loadedArray)===1 && is_array(ArrayUtility::first($loadedArray))){
			// this happens on rare cases... I don't know why
			$loadedArray=ArrayUtility::first($loadedArray);
		}
		
		$parentClass=$childClassName::getParentEntityClass();
		$loadedEntity=self::createInstance($childClassName, $loadedArray, $manyToOneFieldsToLoad, $oneToManyListsToLoad, $manyToManyListsToLoad, $inclManyToOne, $inclOneToMany, $inclManyToMany, $session, true, $parentClass, $parentFieldName, $fieldsOfParent, $fieldsToIgnore);
		
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
	protected static function loadListByCriteriaInternal($criteria,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session)
	{
		$childClassName=get_called_class();
		
		$parentFieldName=$childClassName::getParentFieldName();
		
		$manyToOneFieldsToLoad=null;
		$oneToManyListsToLoad=null;
		$manyToManyListsToLoad=null;
		$fieldsOfParent=null;
		$query=self::prepareSelectQuery($childClassName, $childClassName, null, $criteria, $fields, $fieldsToIgnore, $manyToOneFieldsToLoad, $inclManyToOne, $inclOneToMany, $oneToManyListsToLoad, $inclManyToMany, $manyToManyListsToLoad,true,$parentFieldName,$fieldsOfParent);
		
		if($fields!==null && empty($fieldsOfParent)){
			// if fields were specified and fields of parent are empty, always include the ID of the parent ...
			$fieldsOfParent=[StrongEntity::IDField];
		}
		
		$loadedArrays=self::executeSelectQuery($query, $criteria);
		if(empty($loadedArrays)){
			return [];
		}
		
		$loadedSubEntities=[];
		
		$parentClass=$childClassName::getParentEntityClass();
		foreach($loadedArrays as $array){
			$loadedSubEntities[]=self::createInstance($childClassName, $array, $manyToOneFieldsToLoad, $oneToManyListsToLoad, $manyToManyListsToLoad, $inclManyToOne, $inclOneToMany, $inclManyToMany, $session, true, $parentClass,$parentFieldName,$fieldsOfParent,$fieldsToIgnore);
		}
		
		return $loadedSubEntities;
	}

	/**
	 * Saves parent tables too.
	 * Does not save ManyToOne fields, only sets the ID.
	 * Does not save OneToMany & ManyToMany fields.
	 * 
	 * @param SubEntity $subEntity
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	public static function save($subEntity, $beginTransaction=true, $commit=true)
	{
		$calledClass=get_called_class();
		self::performQuery(QueryTypeEnum::INSERT, $calledClass, $subEntity, null, $beginTransaction, $commit, true, false);
	}

	/**
	 * Does not update ManyToOne fields, only sets the ID.
	 * Does not update OneToMany & ManyToMany fields.
	 * 
	 * @param SubEntity $subEntity
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 * @param array $fields [optional]
	 * @param bool $updateParents [optional] Only important for SubEntities. It determines whether to update parent tables.
	 */
	public static function update($subEntity, $beginTransaction=true, $commit=true, $fields=null,$updateParents=true)
	{
		if($subEntity===null){
			// at least open/close the transaction, if it needs to be
			
			if($beginTransaction&&!$commit){
				MySQL::beginTransaction();
			} else if(!$beginTransaction&&$commit){
				MySQL::commitTransaction();
			}
			
			return;
		}
		
		$calledClass=get_called_class();
		self::performQuery(QueryTypeEnum::UPDATE, $calledClass, $subEntity, $fields, $beginTransaction, $commit,true,$updateParents);
	}
	
	/**
	 * Does not delete child ManyToOne fields.
	 * 
	 * @param SubEntity $subEntity
	 * @param bool $beginTransaction
	 * @param bool $commit
	 * @param Session $session
	 */
	protected static function deleteInternal($subEntity,$beginTransaction,$commit,$session)
	{
		if($subEntity===null){
			// at least open/close the transaction, if it needs to be
			
			if($beginTransaction&&!$commit){
				MySQL::beginTransaction();
			} else if(!$beginTransaction&&$commit){
				MySQL::commitTransaction();
			}
			
			return;
		}
		
		$childClassName=get_called_class();
		
		self::prepareForDeletion($childClassName,$subEntity,$session,$beginTransaction);
		
		// delete cascades down from the base parent, so just route up to the base StrongEntity
		$parentClass=$childClassName::getParentEntityClass();
		$parentFieldName=$childClassName::getParentFieldName();
		$parentEntity=$subEntity->$parentFieldName;
		$parentClass::deleteInternal($parentEntity,false,$commit,$session);
	}
}
