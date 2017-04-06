<?php

/*
 * FieldEntity.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

use Exception;
use ReflectionClass;
use BlueDB\DataAccess\MySQL;
use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Session;
use BlueDB\Entity\FieldTypeEnum;
use BlueDB\Entity\PropertyTypeEnum;
use BlueDB\Utility\StringUtility;

abstract class FieldEntity implements IFieldEntity
{
	/**
	 * @return array
	 */
	public static function getFieldList()
	{
		$childClassName=get_called_class();
		
		$reflectionObject=new ReflectionClass($childClassName);
		
		/*@var $constantList array */
		$constantList=$reflectionObject->getConstants();
		
		$fieldList=[];
		
		foreach($constantList as $constantName => $constantValue){
			if(StringUtility::endsWith($constantName, "Field")){
				// only include it, if it is not hidden
				$isHiddenConstant=$constantValue."IsHidden";
				// needs to be checked, because default is false and it doesn't need to be defined
				if(array_key_exists($isHiddenConstant, $constantList)){
					// if it is defined, check it's value
					if($constantList[$isHiddenConstant])
						// it is hidden, do not include it
						continue;
				}
				
				$fieldList[]=$constantValue;
			}
		}
		
		return $fieldList;
	}
	
	/**
	 * @param int $ID
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @return FieldEntity
	 */
	public static function loadByID($ID,$fields=null,$fieldsToIgnore=null,$inclOneToMany=true)
	{
		$childClassName=get_called_class();
		$session=new Session();
		return $childClassName::loadByIDInternal($ID,$fields,$fieldsToIgnore,$inclOneToMany,$session);
	}
	
	/**
	 * @param int $ID
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @param Session $session
	 * @return FieldEntity
	 */
	protected static function loadByIDInternal($ID,$fields,$fieldsToIgnore,$inclOneToMany,$session)
	{
		// this is a workaround, because PHP does not allow protected static abstract methods
		throw new Exception("This method is abstract.");
	}
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @return FieldEntity
	 */
	public static function loadByCriteria($criteria,$fields=null,$fieldsToIgnore=null,$inclOneToMany=true)
	{
		$childClassName=get_called_class();
		$session=new Session();
		return $childClassName::loadByCriteriaInternal($criteria,$fields,$fieldsToIgnore,$inclOneToMany,$session);
	}
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @param Session $session
	 * @return FieldEntity
	 */
	protected static function loadByCriteriaInternal($criteria,$fields,$fieldsToIgnore,$inclOneToMany,$session)
	{
		// this is a workaround, because PHP does not allow protected static abstract methods
		throw new Exception("This method is abstract.");
	}
	
	/**
	 * Is the same as calling loadListByCriteria with $criteria=null.
	 * 
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @return array
	 */
	public static function loadList($fields=null,$fieldsToIgnore=null,$inclOneToMany=true)
	{
		$childClassName=get_called_class();
		return $childClassName::loadListByCriteria(null, $fields, $fieldsToIgnore, $inclOneToMany);
	}
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @return array
	 */
	public static function loadListByCriteria($criteria, $fields=null, $fieldsToIgnore=null, $inclOneToMany=true)
	{
		$childClassName=get_called_class();
		$session=new Session();
		return $childClassName::loadListByCriteriaInternal($criteria,$fields,$fieldsToIgnore,$inclOneToMany,$session);
	}
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @param Session $session
	 * @return array
	 */
	protected static function loadListByCriteriaInternal($criteria,$fields,$fieldsToIgnore,$inclOneToMany,$session)
	{
		// this is a workaround, because PHP does not allow protected static abstract methods
		throw new Exception("This method is abstract.");
	}
	
	/**
	 * Does not save ManyToOne fields, only sets the ID.
	 * 
	 * @param array $fieldEntities
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 * @param bool $inclOneToMany
	 */
	public static function saveList($fieldEntities, $beginTransaction=true, $commit=true)
	{
		$calledClass=get_called_class();
		
		if($beginTransaction)
			MySQL::beginTransaction();
		
		foreach($fieldEntities as $fieldEntity)
			$calledClass::save($fieldEntity, false, false);
		
		if($commit)
			MySQL::commitTransaction();
	}
	
	/**
	 * Does not update ManyToOne fields, only sets the ID.
	 * 
	 * @param array $fieldEntities
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 * @param array $fields
	 */
	public static function updateList($fieldEntities,$beginTransaction=true,$commit=true,$fields=null)
	{
		$calledClass=get_called_class();
		
		if($beginTransaction)
			MySQL::beginTransaction();
		
		foreach($fieldEntities as $fieldEntity)
			$calledClass::update($fieldEntity, false, false, $fields);
		
		if($commit)
			MySQL::commitTransaction();
	}
	
	/**
	 * Does not delete child ManyToOne fields.
	 * 
	 * @param FieldEntity $fieldEntity
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 */
	public static function delete($fieldEntity, $beginTransaction=true, $commit=true)
	{
		$childClassName=get_called_class();
		$session=new Session();
		$childClassName::deleteInternal($fieldEntity,$beginTransaction,$commit,$session);
	}
	
	/**
	 * Does not delete child ManyToOne fields.
	 * 
	 * @param FieldEntity $fieldEntity
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 * @param Session $session
	 */
	protected static function deleteInternal($fieldEntity,$beginTransaction,$commit,$session)
	{
		// this is a workaround, because PHP does not allow protected static abstract methods
		throw new Exception("This method is abstract.");
	}
	
	/**
	 * Does not delete child ManyToOne fields.
	 * 
	 * @param array $fieldEntities
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 */
	public static function deleteList($fieldEntities,$beginTransaction=true,$commit=true)
	{
		$calledClass=get_called_class();
		
		if($beginTransaction)
			MySQL::beginTransaction();
		
		foreach($fieldEntities as $fieldEntity)
			$calledClass::delete($fieldEntity,false,false);
		
		if($commit)
			MySQL::commitTransaction();
	}
	
	/**
	 * Only allowed for property type fields.
	 * 
	 * @param string $field
	 * @param mixed $value
	 * @return boolean TRUE if the provided value exists in the provided fields column in the called entity table.
	 */
	public static function exists($field,$value)
	{
		$childClassName=get_called_class();
		$fieldBaseConstName=$childClassName."::".$field;
		$fieldType=constant($fieldBaseConstName."FieldType");
		
		if($fieldType==FieldTypeEnum::MANY_TO_ONE){
			if($value!==null && !is_int($value)){
				throw new Exception("Exists is only allowed either for property type fields or for ManyToOne when the value is null or integer (to check for ID). Value '$value' is neither null or int.");
			}
			$fieldPropertyType=PropertyTypeEnum::INT;
		}else if($fieldType!=FieldTypeEnum::PROPERTY){
			throw new Exception("Exists is only allowed either for property type fields or for ManyToOne when the value is null or integer (to check for ID). Field '$field' is of unsupported property type on class '$childClassName'.");
		}else{
			$fieldPropertyType=constant($fieldBaseConstName."PropertyType");
		}
		
		$childTableName=$childClassName::getTableName();
		$fieldColumn=constant($fieldBaseConstName."Column");
		
		$query="SELECT EXISTS(SELECT 1 FROM ".$childTableName." WHERE (".$fieldColumn."=?)) AS result";
		
		$parameters=[];
		$parameters[]=PropertyTypeEnum::getPreparedStmtType($fieldPropertyType);
		$parameters[]=&$value;
		
		/*@var $result array*/
		$result=MySQL::prepareAndExecuteSelectSingleStatement($query, $parameters);
		
		return $result["result"]==1;
	}
	
	/**
	 * @param Criteria $criteria
	 * @return boolean TRUE if an entry exists that meets criterias restrictions.
	 */
	public static function existsByCriteria($criteria)
	{
		$childClassName=get_called_class();
		$childTableName=$childClassName::getTableName();
		
		$query="SELECT EXISTS(SELECT 1 FROM ".$childTableName;
		
		$criteria->prepare();
		if(!empty($criteria->PreparedQueryJoins))
			$query.=" ".$criteria->PreparedQueryJoins;
		if(!empty($criteria->PreparedQueryRestrictions))
			$query.=" WHERE ".$criteria->PreparedQueryRestrictions;
		$query.=") AS result";
		
		if(count($criteria->PreparedParameters)>1)
			$result=MySQL::prepareAndExecuteSelectSingleStatement($query, $criteria->PreparedParameters);
		else
			$result=MySQL::selectSingle($query);
		
		return $result["result"]==1;
	}
}
