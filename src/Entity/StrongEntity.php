<?php

/*
 * StrongEntity.php
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
	 * @param int $ID
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @param Session $session
	 * @return StrongEntity
	 */
	protected static function loadByIDInternal($ID,$fields,$fieldsToIgnore,$inclOneToMany,$session)
	{
		$childClassName=get_called_class();
		
		$criteria=new Criteria($childClassName);
		$criteria->add(Expression::equal($childClassName, self::IDField, $ID));
		
		return $childClassName::loadByCriteriaInternal($criteria,$fields,$fieldsToIgnore,$inclOneToMany,$session);
	}
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @param Session $session
	 * @return StrongEntity
	 */
	protected static function loadByCriteriaInternal($criteria, $fields, $fieldsToIgnore, $inclOneToMany, $session)
	{
		$childClassName=get_called_class();
		
		$manyToOneFieldsToLoad=null;
		$oneToManyListsToLoad=null;
		$fieldsOfParent=null;
		$query=self::prepareSelectQuery($childClassName, $criteria, $fields, $fieldsToIgnore,$manyToOneFieldsToLoad,$inclOneToMany,$oneToManyListsToLoad,false,null,$fieldsOfParent);
		
		$loadedArray=self::executeSelectSingleQuery($query, $criteria);
		if($loadedArray===null)
			return null;
		
		$addToSession=self::shouldAddToSession($fields, $fieldsToIgnore, $inclOneToMany);
		$loadedEntity=self::createInstance($childClassName, $loadedArray,$manyToOneFieldsToLoad,$oneToManyListsToLoad,$addToSession,$session,false,null,null,null);
		
		return $loadedEntity;
	}
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @param Session $session
	 * @return array
	 */
	protected static function loadListByCriteriaInternal($criteria, $fields, $fieldsToIgnore, $inclOneToMany, $session)
	{
		$childClassName=get_called_class();
		
		$manyToOneFieldsToLoad=null;
		$oneToManyListsToLoad=null;
		$fieldsOfParent=null;
		$query=self::prepareSelectQuery($childClassName, $criteria, $fields, $fieldsToIgnore,$manyToOneFieldsToLoad,$inclOneToMany,$oneToManyListsToLoad,false,null,$fieldsOfParent);
		
		$loadedArray=self::executeSelectQuery($query, $criteria);
		if(empty($loadedArray))
			return [];
		
		$loadedEntities=[];
		
		$addToSession=self::shouldAddToSession($fields, $fieldsToIgnore, $inclOneToMany);
		foreach($loadedArray as $array){
			$loadedEntities[]=self::createInstance($childClassName, $array,$manyToOneFieldsToLoad,$oneToManyListsToLoad,$addToSession,$session,false,null,null,null);
		}
		
		return $loadedEntities;
	}
	
	/**
	 * Does not save ManyToOne fields, only sets the ID.
	 * Does not save OneToMany & ManyToMany fields.
	 * 
	 * @param StrongEntity $strongEntity
	 * @param boolean $beginTransaction [optional]
	 * @param boolean $commit [optional]
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
	 * @param boolean $beginTransaction [optional]
	 * @param boolean $commit [optional]
	 * @param array $fields [optional]
	 * @param bool $updateParents [optional] Only important for SubEntities. It determines whether to update parent tables.
	 */
	public static function update($strongEntity,$beginTransaction=true,$commit=true,$fields=null,$updateParents=true)
	{
		if($strongEntity===null){
			// at least open/close the transaction, if it needs to be
			
			if($beginTransaction&&!$commit)
				MySQL::beginTransaction();
			else if(!$beginTransaction&&$commit)
				MySQL::commitTransaction();
			
			return;
		}
		
		$calledClass=get_called_class();
		self::performQuery(QueryTypeEnum::UPDATE, $calledClass, $strongEntity, $fields, $beginTransaction, $commit,false,false);
	}
	
	/**
	 * Does not delete child ManyToOne fields.
	 * 
	 * @param StrongEntity $strongEntity
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 * @param Session $session
	 */
	protected static function deleteInternal($strongEntity,$beginTransaction,$commit,$session)
	{
		if($strongEntity===null){
			// at least open/close the transaction, if it needs to be
			
			if($beginTransaction&&!$commit)
				MySQL::beginTransaction();
			else if(!$beginTransaction&&$commit)
				MySQL::commitTransaction();
			
			return;
		}
		
		if($strongEntity->ID==null)
			throw new Exception("The provided objects ID is null. What are you trying to delete?");
		
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
		
		if($commit)
			MySQL::commitTransaction();
	}
}
