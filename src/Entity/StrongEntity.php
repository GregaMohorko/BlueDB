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
	
	public static function getIDColumn()
	{
		return self::IDColumn;
	}
	
	/**
	 * @param int $ID
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @return StrongEntity
	 */
	public static function loadByID($ID,$fields=null,$fieldsToIgnore=null,$inclOneToMany=false)
	{
		$childClassName=get_called_class();
		
		$criteria=new Criteria($childClassName);
		$criteria->add(Expression::equal($childClassName, self::IDField, $ID));
		
		return $childClassName::loadByCriteria($criteria,$fields,$fieldsToIgnore,$inclOneToMany);
	}
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @return StrongEntity
	 */
	public static function loadByCriteria($criteria,$fields=null,$fieldsToIgnore=null,$inclOneToMany=false)
	{
		$childClassName=get_called_class();
		
		$query=self::prepareSelectQuery($childClassName, $criteria, $fields, $fieldsToIgnore);
		
		if(count($criteria->PreparedParameters)>1)
			$loadedArray=MySQL::prepareAndExecuteSelectSingleStatement($query,$criteria->PreparedParameters);
		else
			$loadedArray=MySQL::selectSingle($query);
		
		if($loadedArray===null)
			return null;
		
		$loadedEntity=self::createInstance($childClassName, $loadedArray);
		
		return $loadedEntity;
	}
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @return array
	 */
	public static function loadListByCriteria($criteria,$fields=null,$fieldsToIgnore=null,$inclOneToMany=false)
	{
		$childClassName=get_called_class();
		
		$query=self::prepareSelectQuery($childClassName, $criteria, $fields, $fieldsToIgnore);
		
		if($criteria!==null && count($criteria->PreparedParameters)>1)
			$loadedArray=MySQL::prepareAndExecuteSelectStatement($query, $criteria->PreparedParameters);
		else
			$loadedArray=MySQL::select($query);
		
		$loadedEntities=[];
		
		foreach($loadedArray as $array){
			$loadedEntities[]=self::createInstance($childClassName, $array);
		}
		
		return $loadedEntities;
	}
	
	/**
	 * Does not save ManyToOne fields, only sets the ID.
	 * 
	 * @param StrongEntity $strongEntity
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 * @param bool $inclOneToMany
	 */
	public static function save($strongEntity,$beginTransaction=true,$commit=true,$inclOneToMany=false)
	{
		if($strongEntity->ID!=null)
			throw new Exception("The provided objects ID is not null. Call Update function instead.");
		
		$calledClass=get_called_class();
		self::performQuery(QueryTypeEnum::INSERT, $calledClass, $strongEntity, null, $beginTransaction, $commit);
	}
	
	/**
	 * Does not update ManyToOne fields, only sets the ID.
	 * 
	 * @param StrongEntity $strongEntity
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 * @param array $fields
	 * @param bool $inclOneToMany
	 */
	public static function update($strongEntity,$beginTransaction=true,$commit=true,$fields=null,$inclOneToMany=false)
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
			throw new Exception("The provided objects ID is null. Call Save function instead.");
		
		$calledClass=get_called_class();
		self::performQuery(QueryTypeEnum::UPDATE, $calledClass, $strongEntity, $fields, $beginTransaction, $commit);
	}
	
	/**
	 * Does not delete child ManyToOne fields.
	 * 
	 * @param StrongEntity $strongEntity
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 */
	public static function delete($strongEntity,$beginTransaction=true,$commit=true)
	{
		if($strongEntity==null){
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
		
		$tableName=$childClassName::getTableName();
		
		$query="DELETE FROM ".$tableName." WHERE ID=?";
		
		$parameters=[];
		$parameters[]="i";
		$parameters[]=&$strongEntity->ID;
		
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
	 * @param string $childClassName
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @return string Query.
	 */
	private static function prepareSelectQuery($childClassName,$criteria,$fields,$fieldsToIgnore)
	{
		if($criteria!==null && $childClassName!==$criteria->BaseEntityClass)
			throw new Exception("Criterias BaseEntityClass (".$criteria->BaseEntityClass.") is different than the called child class (".$childClassName.").");
		
		$baseEntityTableName=$childClassName::getTableName();
		
		if(!empty($fields))
			$fieldsToLoad=$fields;
		else
			$fieldsToLoad=$childClassName::getFieldList();
		
		$query="SELECT ";
		$isFirst=true;
		foreach($fieldsToLoad as $field){
			if($fieldsToIgnore!=null && in_array($field, $fieldsToIgnore))
				continue;
			
			$fieldsBaseConstName=$childClassName."::".$field;
			$fieldType=constant($fieldsBaseConstName."FieldType");
			
			switch($fieldType){
				case FieldTypeEnum::PROPERTY:
					if($isFirst)
						$isFirst=false;
					else
						$query.=",";
					
					$fieldColumn=constant($fieldsBaseConstName."Column");
					
					$query.=$baseEntityTableName.".".$fieldColumn." AS ".$field;
					break;
				case FieldTypeEnum::MANY_TO_ONE:
					// TODO StrongEntity::loadListByCriteria manyToOne
					break;
				case FieldTypeEnum::ONE_TO_MANY:
					// TODO StrongEntity::loadListByCriteria manyToOne
					break;
				case FieldTypeEnum::MANY_TO_MANY:
					throw new Exception("ManyToMany field is currently not yet supported for loading from here.");
				default:
					throw new Exception("FieldType of type '$fieldType' is not supported.");
			}
		}
		
		$query.=" FROM ".$baseEntityTableName;
		
		if($criteria!==null){
			$criteria->prepare();
			if(!empty($criteria->PreparedQueryJoins))
				$query.=" ".$criteria->PreparedQueryJoins;
			if(!empty($criteria->PreparedQueryRestrictions))
				$query.=" WHERE ".$criteria->PreparedQueryRestrictions;
		}
		
		return $query;
	}
	
	/**
	 * Insert or update.
	 * 
	 * @param QueryTypeEnum $type
	 * @param string $calledClass
	 * @param StrongEntity $strongEntity
	 * @param array $fields
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 */
	private static function performQuery($type,$calledClass,$strongEntity,$fields,$beginTransaction,$commit)
	{
		switch($type){
			case QueryTypeEnum::INSERT:
			case QueryTypeEnum::UPDATE:
				break;
			default:
				throw new Exception("Query of type '$type' is not supported.");
		}
		
		$childClassName=get_class($strongEntity);
		if($childClassName!==$calledClass)
			throw new Exception("Type of the provided object '$childClassName' is not the same as the called class '$calledClass'.");
		
		$baseEntityTableName=$childClassName::getTableName();
		
		$preparedValues=[];
		$preparedValues[]="";
		$preparedValuesDirect=[];
		$preparedValuesDirectIndex=0;
		
		if($fields==null)
			$fields=$childClassName::getFieldList();
		
		switch($type){
			case QueryTypeEnum::INSERT:
				$query="INSERT INTO $baseEntityTableName (";
				break;
			case QueryTypeEnum::UPDATE:
				$query="UPDATE $baseEntityTableName SET ";
				break;
		}
		$isFirst=true;
		foreach($fields as $field){
			if($type==QueryTypeEnum::INSERT && $strongEntity->$field==null)
				continue;
			
			$fieldBaseConstName=$childClassName."::".$field;
			/*@var $fieldType FieldTypeEnum */
			$fieldType=constant($fieldBaseConstName."FieldType");
			switch($fieldType){
				case FieldTypeEnum::PROPERTY:
					if(!$isFirst)
						$query.=",";
					else
						$isFirst=false;

					$query.=constant($fieldBaseConstName."Column");
					if($type==QueryTypeEnum::UPDATE)
						$query.="=?";
			
					/*@var $propertyType PropertyTypeEnum*/
					$propertyType=constant($fieldBaseConstName."PropertyType");
					
					$preparedValues[0].=PropertyTypeEnum::getPreparedStmtType($propertyType);
					$preparedValuesDirect[]=PropertyTypeEnum::convertToString($strongEntity->$field, $propertyType);
					$preparedValues[]=&$preparedValuesDirect[$preparedValuesDirectIndex];
					$preparedValuesDirectIndex++;
					break;
				case FieldTypeEnum::MANY_TO_ONE:
					// TODO StrongEntity::save manyToOne
					break;
				case FieldTypeEnum::ONE_TO_MANY:
					// TODO StrongEntity::save oneToMany
				case FieldTypeEnum::MANY_TO_MANY:
					throw new Exception("ManyToMany field is currently not yet supported for saving from here.");
				default:
					throw new Exception("FieldType of type '$fieldType' is not supported.");
			}
		}
		
		$preparedValuesCount=count($preparedValues);

		switch($type){
			case QueryTypeEnum::INSERT:
				// Question marks
				$query.=") VALUES (";
				if($preparedValuesCount>1){
					$isFirst=true;
					for($i=1;$i<$preparedValuesCount;++$i){
						if($isFirst)
							$isFirst=false;
						else
							$query.=",";
						$query.="?";
					}
				}
				$query.=")";
				break;
			case QueryTypeEnum::UPDATE:
				// Condition
				$query.=" WHERE $baseEntityTableName.ID=?";
				$preparedValues[0].=PropertyTypeEnum::getPreparedStmtType(PropertyTypeEnum::INT);
				$preparedValues[]=&$strongEntity->ID;
				++$preparedValuesCount;
				break;
		}
		
		if($beginTransaction)
			MySQL::beginTransaction();
		
		try{
			if($preparedValuesCount>1)
				MySQL::prepareAndExecuteStatement($query, $preparedValues);
			else{
				// if no prepared values are present, no need for prepared statement
				switch($type){
					case QueryTypeEnum::INSERT:
						MySQL::insert($query);
						break;
					case QueryTypeEnum::UPDATE:
						MySQL::update($query);
						break;
				}
			}
		} catch (Exception $ex) {
			MySQL::rollbackTransaction();
			throw $ex;
		}
		
		if($type==QueryTypeEnum::INSERT)
			$strongEntity->ID=MySQL::autogeneratedID();
		
		if($commit)
			MySQL::commitTransaction();
	}
	
	/**
	 * @param string $entityClass
	 * @param array $fieldValues
	 * @return StrongEntity
	 */
	private static function createInstance($entityClass,$fieldValues)
	{
		$newEntity=new $entityClass();
		FieldEntityHelper::setFieldValues($newEntity, $fieldValues, $entityClass);
		return $newEntity;
	}
}
