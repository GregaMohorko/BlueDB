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
	static function loadByID($ID,$fields=null,$fieldsToIgnore=null,$inclOneToMany=false)
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
	static function loadByCriteria($criteria,$fields=null,$fieldsToIgnore=null,$inclOneToMany=false)
	{
		$childClassName=get_called_class();
		if($childClassName!==$criteria->BaseEntityClass)
			throw new Exception("Criteria BaseEntityClass (".$criteria->BaseEntityClass.") is different than the called child class (".$childClassName.").");
		
		$baseEntityTableName=$childClassName::getTableName();
		
		if(!empty($fields))
			$fieldsToLoad=$fields;
		else
			$fieldsToLoad=$childClassName::getFieldList();
		
		// Columns
		$query="SELECT ";
		$isFirst=true;
		foreach($fieldsToLoad as $field){
			if($fieldsToIgnore!=null && in_array($field, $fieldsToIgnore))
				continue;
			
			$fieldBaseConstName=$childClassName."::".$field;
			$fieldType=constant($fieldBaseConstName."FieldType");
			
			switch($fieldType){
				case FieldTypeEnum::PROPERTY:
					if(!$isFirst)
						$query.=",";
					else
						$isFirst=false;

					$fieldColumn=constant($fieldBaseConstName."Column");
					
					$query.=$baseEntityTableName.".".$fieldColumn." AS ".$field;
					break;
				case FieldTypeEnum::MANY_TO_ONE:
					// TODO StrongEntity::loadByCriteria manyToOne
				case FieldTypeEnum::ONE_TO_MANY:
					// TODO StrongEntity::loadByCriteria oneToMany
				case FieldTypeEnum::MANY_TO_MANY:
					throw new Exception("ManyToMany field is currently not yet supported for loading from here.");
				default:
					throw new Exception("FieldType of type ".$fieldType." is not allowed in loadByCriteria function.");
			}
		}
		
		$query.=" FROM ".$baseEntityTableName;
		
		$criteria->prepare();
		if(!empty($criteria->PreparedQueryJoins))
			$query.=" ".$criteria->PreparedQueryJoins;
		if(!empty($criteria->PreparedQueryRestrictions))
			$query.=" WHERE ".$criteria->PreparedQueryRestrictions;
		
		if(count($criteria->PreparedParameters)>1)
			$loadedArray=MySQL::prepareAndExecuteSelectSingleStatement($query,$criteria->PreparedParameters);
		else
			$loadedArray=MySQL::selectSingle($query);
		
		if($loadedArray===null)
			return null;
		
		$loadedEntity=new $childClassName();
		FieldEntityHelper::setFieldValues($loadedEntity, $loadedArray, $childClassName);
		
		return $loadedEntity;
	}
	
	/**
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @return array
	 */
	static function loadList($fields=null,$fieldsToIgnore=null,$inclOneToMany=false)
	{
		$childClassName=get_called_class();
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
			
			$fieldBaseConstName=$childClassName."::".$field;
			$fieldType=constant($fieldBaseConstName."FieldType");
			
			switch($fieldType){
				case FieldTypeEnum::PROPERTY:
					if(!$isFirst)
						$query.=",";
					else
						$isFirst=false;

					$fieldColumn=constant($fieldBaseConstName."Column");
					
					$query.=$fieldColumn." AS ".$field;
					break;
				case FieldTypeEnum::MANY_TO_ONE:
					// TODO StrongEntity::loadList manyToOne
					break;
				case FieldTypeEnum::ONE_TO_MANY:
					// TODO StrongEntity::loadList oneToMany
					break;
				case FieldTypeEnum::MANY_TO_MANY:
					throw new Exception("ManyToMany field is currently not yet supported for loading from here.");
				default:
					throw new Exception("FieldType of type ".$fieldType." is not allowed in loadList function.");
			}
		}
		
		$query.=" FROM ".$baseEntityTableName;
		
		$loadedArray=MySQL::select($query);
		
		$loadedEntities=[];
		
		foreach($loadedArray as $array){
			$newEntity=new $childClassName();
			FieldEntityHelper::setFieldValues($newEntity, $array,$childClassName);
			
			$loadedEntities[]=$newEntity;
		}
		
		return $loadedEntities;
	}
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @return array
	 */
	static function loadListByCriteria($criteria,$fields=null,$fieldsToIgnore=null,$inclOneToMany=false)
	{
		$childClassName=get_called_class();
		if($childClassName!==$criteria->BaseEntityClass)
			throw new Exception("Criteria BaseEntityClass (".$criteria->BaseEntityClass.") is different than the called child class (".$childClassName.").");
		
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
					if(!$isFirst)
						$query.=",";
					else
						$isFirst=false;

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
					throw new Exception("FieldType of type ".$fieldType." is not allowed in loadListByCriteria function.");
			}
		}
		
		$query.=" FROM ".$baseEntityTableName;
		
		$criteria->prepare();
		if(!empty($criteria->PreparedQueryJoins))
			$query.=" ".$criteria->PreparedQueryJoins;
		if(!empty($criteria->PreparedQueryRestrictions))
			$query.=" WHERE ".$criteria->PreparedQueryRestrictions;
		
		if(count($criteria->PreparedParameters)>1)
			$loadedArray=MySQL::prepareAndExecuteSelectStatement($query, $criteria->PreparedParameters);
		else
			$loadedArray=MySQL::select($query);
		
		$loadedEntities=[];
		
		foreach($loadedArray as $array){
			$newEntity=new $childClassName();
			FieldEntityHelper::setFieldValues($newEntity, $array,$childClassName);
			
			$loadedEntities[]=$newEntity;
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
	static function save(&$strongEntity,$beginTransaction=true,$commit=true,$inclOneToMany=false)
	{
		if($strongEntity->ID!=null)
			throw new Exception("The provided object does not have a null ID. Call Update function instead.");
		
		$childClassName=get_class($strongEntity);
		if($childClassName!=get_called_class())
			throw new Exception("Object class '".$childClassName."' is not the same as the called class '".get_called_class()."'.");
		
		$baseEntityTableName=$childClassName::getTableName();
		
		$preparedValues=[];
		$preparedValues[]="";
		$preparedValuesDirect=[];
		$preparedValuesDirectIndex=0;
		
		// Columns & Values
		$query="INSERT INTO ".$baseEntityTableName." (";
		$isFirst=true;
		foreach($childClassName::getFieldList() as $field) {
			if($strongEntity->$field==null)
				continue;

			$fieldBaseConstName=$childClassName."::".$field;
			$fieldType=constant($fieldBaseConstName."FieldType");
			switch($fieldType){
				case FieldTypeEnum::PROPERTY:
					if(!$isFirst)
						$query.=",";
					else
						$isFirst=false;
					
					$query.=constant($fieldBaseConstName."Column");
					
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
					throw new Exception("FieldType of type '".$fieldType."' is not allowed in save function.");
			}
		}
		
		// Question marks
		$query.=") VALUES (";
		$preparedValuesCount=count($preparedValues);
		if($preparedValuesCount>1){
			$isFirst=true;
			for($i=1;$i<$preparedValuesCount;$i++){
				if(!$isFirst)
					$query.=",";
				else
					$isFirst=false;

				$query.="?";
			}
		}
		$query.=")";
		
		if($beginTransaction)
			MySQL::beginTransaction();
		
		try{
			if($preparedValuesCount>1)
				MySQL::prepareAndExecuteStatement($query, $preparedValues);
			else
				// if no prepared values are present, no need for prepared statement
				MySQL::insert($query);
		} catch (Exception $ex) {
			MySQL::rollbackTransaction();
			throw $ex;
		}
		
		$strongEntity->ID=MySQL::autogeneratedID();
		
		if($commit)
			MySQL::commitTransaction();
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
	static function update($strongEntity,$beginTransaction=true,$commit=true,$fields=null,$inclOneToMany=false)
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
			throw new Exception("The provided objects ID is null. Call Save function instead.");
		
		$childClassName=get_class($strongEntity);
		if($childClassName!=get_called_class())
			throw new Exception("Object class '".$childClassName."' is not the same as the called class '".get_called_class()."'.");
		
		$baseEntityTableName=$childClassName::getTableName();
		
		$preparedValues=[];
		$preparedValues[]="";
		$preparedValuesDirect=[];
		$preparedValuesDirectIndex=0;
		
		if($fields==null)
			$fields=$childClassName::getFieldList();
		
		// Columns & Values
		$query="UPDATE ".$baseEntityTableName." SET ";
		$isFirst=true;
		foreach($fields as $field){
			$fieldBaseConstName=$childClassName."::".$field;
			/*@var $fieldType FieldTypeEnum */
			$fieldType=constant($fieldBaseConstName."FieldType");
			switcH($fieldType){
				case FieldTypeEnum::PROPERTY:
					if(!$isFirst)
						$query.=",";
					else
						$isFirst=false;

					$query.=constant($fieldBaseConstName."Column")."=?";
			
					/*@var $propertyType PropertyTypeEnum*/
					$propertyType=constant($fieldBaseConstName."PropertyType");
					
					$preparedValues[0].=PropertyTypeEnum::getPreparedStmtType($propertyType);
					$preparedValuesDirect[]=PropertyTypeEnum::convertToString($strongEntity->$field, $propertyType);
					$preparedValues[]=&$preparedValuesDirect[$preparedValuesDirectIndex];
					$preparedValuesDirectIndex++;
					break;
				case FieldTypeEnum::MANY_TO_ONE:
					// TODO StrongEntity::update manyToOne
					break;
				case FieldTypeEnum::ONE_TO_MANY:
					// TODO StrongEntity::save oneToMany
				case FieldTypeEnum::MANY_TO_MANY:
					throw new Exception("ManyToMany field is currently not yet supported for saving from here.");
				default:
					throw new Exception("FieldType of type '".$fieldType."' is not allowed in update function.");
			}
		}
		
		// Condition
		$query.=" WHERE ".$baseEntityTableName.".ID=?";
		$preparedValues[0].=PropertyTypeEnum::getPreparedStmtType(PropertyTypeEnum::INT);
		$preparedValues[]=&$strongEntity->ID;
		
		if($beginTransaction)
			MySQL::beginTransaction();
		
		try{
			if(count($preparedValues)>1)
				MySQL::prepareAndExecuteStatement($query, $preparedValues);
			else
				MySQL::update($query);
		} catch (Exception $ex) {
			MySQL::rollbackTransaction();
			throw $ex;
		}
		
		if($commit)
			MySQL::commitTransaction();
	}
	
	/**
	 * Does not delete child ManyToOne fields.
	 * 
	 * @param StrongEntity $strongEntity
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 */
	static function delete($strongEntity,$beginTransaction=true,$commit=true)
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
		if($childClassName!=get_called_class())
			throw new Exception("Object class '"+$childClassName+"' is not the same as the called class '"+get_called_class()+"'.");
		
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
}
