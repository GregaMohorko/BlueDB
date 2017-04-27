<?php

/*
 * DatabaseTable.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 26, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

use Exception;
use BlueDB\DataAccess\MySQL;
use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Criteria\Expression;

abstract class DatabaseTable implements IDatabaseTable
{
	/**
	 * @param string $fieldEntityClass Base class.
	 * @param string $classToLoad Class from which the fields will be loaded. Often it is the same as the $fieldEntityClass.
	 * @param string $joinColumn The join column in base FieldEntity class on which to join the $classToLoad. Should only be set when $classToLoad is not equal to $fieldEntityClass.
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param array $manyToOneFieldsToLoad
	 * @param bool $inclOneToMany
	 * @param array $oneToManyListsToLoad
	 * @param bool $isSubEntity
	 * @param string $parentFieldName
	 * @param array $fieldsOfParent
	 * @return string Query.
	 */
	protected static function prepareSelectQuery($fieldEntityClass,$classToLoad,$joinColumn,$criteria,$fields,$fieldsToIgnore,&$manyToOneFieldsToLoad,$inclOneToMany,&$oneToManyListsToLoad,$isSubEntity,$parentFieldName,&$fieldsOfParent)
	{
		$toLoadTableName=$classToLoad::getTableName();
		if($isSubEntity)
			$useFieldsOfParent=true;
		
		if(empty($fields)){
			$fields=$classToLoad::getFieldList();
			if($isSubEntity)
				$useFieldsOfParent=false;
		}
		
		$manyToOneFieldsToLoad=[];
		$oneToManyListsToLoad=[];
		if($isSubEntity && $useFieldsOfParent)
			$fieldsOfParent=[];
		
		$query="SELECT ";
		if($isSubEntity){
			$isFirst=false;
			$query.="$toLoadTableName.".$classToLoad::getIDColumn()." AS $parentFieldName";
		} else
			$isFirst=true;
		foreach($fields as $field){
			if($fieldsToIgnore!=null && in_array($field, $fieldsToIgnore))
				continue;
			
			$fieldBaseConstName="$classToLoad::$field";
			$fieldTypeConstName=$fieldBaseConstName."FieldType";
			if($isSubEntity && $useFieldsOfParent && !defined($fieldTypeConstName)){
				// this field is in parent entity ...
				$fieldsOfParent[]=$field;
				continue;
			}
			$fieldType=constant($fieldTypeConstName);
			
			switch($fieldType){
				case FieldTypeEnum::PROPERTY:
					if(!$isFirst)
						$query.=",";
					else
						$isFirst=false;
					
					$fieldColumn=constant($fieldBaseConstName."Column");
					
					$query.="$toLoadTableName.$fieldColumn AS $field";
					break;
				case FieldTypeEnum::MANY_TO_ONE:
					if(!$isFirst)
						$query.=",";
					else
						$isFirst=false;
					
					$fieldColumn=constant($fieldBaseConstName."Column");
					
					$manyToOneField=[];
					$manyToOneField["Field"]=$field;
					$manyToOneField["Class"]=constant($fieldBaseConstName."Class");
					
					$manyToOneFieldsToLoad[]=$manyToOneField;
					
					$query.="$toLoadTableName.$fieldColumn AS $field";
					break;
				case FieldTypeEnum::ONE_TO_MANY:
					if(!$inclOneToMany)
						break;
					$oneToManyList=[];
					$oneToManyList["Field"]=$field;
					$oneToManyList["Class"]=constant($fieldBaseConstName."Class");
					$oneToManyList["Identifier"]=constant($fieldBaseConstName."Identifier");
					$oneToManyListsToLoad[]=$oneToManyList;
					break;
				case FieldTypeEnum::MANY_TO_MANY:
					throw new Exception("ManyToMany field is currently not yet supported for loading from here.");
				default:
					throw new Exception("FieldType of type '$fieldType' is not supported.");
			}
		}
		
		$baseEntityTableName=$fieldEntityClass::getTableName();
		$query.=" FROM $baseEntityTableName";
		
		// join
		if($fieldEntityClass!==$classToLoad){
			$toLoadTableIDColumn=$classToLoad::getIDColumn();
			$query.=" INNER JOIN $toLoadTableName ON $baseEntityTableName.$joinColumn=$toLoadTableName.$toLoadTableIDColumn";
		}
		
		if($criteria!==null){
			$criteria->prepare();
			// joins
			if(!empty($criteria->PreparedQueryJoins))
				$query.=" ".$criteria->PreparedQueryJoins;
			// conditions
			if(!empty($criteria->PreparedQueryRestrictions))
				$query.=" WHERE ".$criteria->PreparedQueryRestrictions;
		}
		
		return $query;
	}
	
	/**
	 * @param string $selectQuery
	 * @param Criteria $criteria
	 * @return array
	 */
	protected static function executeSelectQuery($selectQuery,$criteria)
	{
		if($criteria!==null && count($criteria->PreparedParameters)>1)
			return MySQL::prepareAndExecuteSelectStatement($selectQuery, $criteria->PreparedParameters);
		
		return MySQL::select($selectQuery);
	}
	
	/**
	 * @param string $selectSingleQuery
	 * @param Criteria $criteria
	 * @return array
	 */
	protected static function executeSelectSingleQuery($selectSingleQuery,$criteria)
	{
		if(count($criteria->PreparedParameters)>1)
			return MySQL::prepareAndExecuteSelectSingleStatement($selectSingleQuery,$criteria->PreparedParameters);
		
		return MySQL::selectSingle($selectSingleQuery);
	}
	
	/**
	 * Determines whether it should add loaded entities to the session.
	 * 
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @return bool
	 */
	protected static function shouldAddToSession($fields,$fieldsToIgnore,$inclOneToMany)
	{
		return $fields===null && $fieldsToIgnore===null && $inclOneToMany===true;
	}
	
	/**
	 * @param string $entityClass
	 * @param array $fieldValues
	 * @param array $manyToOneFieldsToLoad
	 * @param array $oneToManyListsToLoad
	 * @param bool $addToSession
	 * @param Session $session
	 * @param bool $isSubEntity
	 * @param string $parentClass
	 * @param string $parentFieldName
	 * @param array $fieldsOfParent
	 * @return FieldEntity
	 */
	protected static function createInstance($entityClass,$fieldValues,$manyToOneFieldsToLoad,$oneToManyListsToLoad,$addToSession,$session,$isSubEntity,$parentClass,$parentFieldName,$fieldsOfParent)
	{
		$newEntity=new $entityClass();
		self::setFieldValues($newEntity, $fieldValues,$isSubEntity, $entityClass);
		
		$manyToOneNotEmpty=!empty($manyToOneFieldsToLoad);
		$oneToManyNotEmpty=!empty($oneToManyListsToLoad);
		
		$ID=$isSubEntity?intval($newEntity->$parentFieldName):$newEntity->ID;
		
		if($addToSession)
			$session->add($newEntity, $entityClass,$ID);
		
		if($manyToOneNotEmpty)
			self::loadManyToOneFields($newEntity, $manyToOneFieldsToLoad,$session);
		if($isSubEntity)
			$newEntity->$parentFieldName=$parentClass::loadByID($ID,$fieldsOfParent);
		if($oneToManyNotEmpty)
			self::loadOneToManyLists($entityClass, $newEntity, $oneToManyListsToLoad,$session);
		
		return $newEntity;
	}
	
	/**
	 * @param FieldEntity $entity
	 * @param array $manyToOneFieldsToLoad
	 * @param Session $session
	 */
	protected static function loadManyToOneFields($entity,$manyToOneFieldsToLoad,$session)
	{
		foreach($manyToOneFieldsToLoad as $manyToOneField){
			$manyToOneFieldName=$manyToOneField["Field"];
			$manyToOneClass=$manyToOneField["Class"];
			$foreignKey=$entity->$manyToOneFieldName;
			
			if($foreignKey==null)
				continue;
			
			// first, let's try to look it up in the Session
			$lookUpResult=$session->lookUp($manyToOneClass, $foreignKey);
			if($lookUpResult!==false){
				$manyToOneEntity=$lookUpResult;
			}else{
				$manyToOneEntity=$manyToOneClass::loadByIDInternal($foreignKey,null,null,true,$session);
			}
			
			$entity->$manyToOneFieldName=$manyToOneEntity;
		}
	}
	
	/**
	 * @param string $entityClass
	 * @param FieldEntity $entity
	 * @param array $oneToManyLists
	 * @param Session $session
	 */
	protected static function loadOneToManyLists($entityClass,$entity,$oneToManyLists,$session)
	{
		$ID=$entity->getID();
		
		/* @var $entityDTO FieldEntity */
		$entityDTO=new $entityClass();
		$entityDTO->setID($ID);
		foreach($oneToManyLists as $oneToManyList){
			$oneToManyFieldName=$oneToManyList["Field"];
			$oneToManyClass=$oneToManyList["Class"];
			$identifier=$oneToManyList["Identifier"];
			
			// first, let's try to look it up in the Session
			$lookUpResult=$session->lookUpByOneToMany($oneToManyClass, $identifier, $ID);
			if($lookUpResult!==false){
				$list=$lookUpResult;
			}else{
				$criteria=new Criteria($oneToManyClass);
				$criteria->add(Expression::equal($oneToManyClass, $identifier, $entityDTO));
				$list=$oneToManyClass::loadListByCriteriaInternal($criteria,null,null,true,$session);
				foreach($list as $item)
					$item->$identifier=$entity;
			}
			
			$entity->$oneToManyFieldName=$list;
		}
	}
	
	/**
	 * @param DatabaseTable $entity
	 * @param array $fieldValues
	 * @param bool $isSubEntity
	 * @param string $entityClass [optional] Class type of the entity. If not provided, the class is determined with get_class function.
	 */
	protected static function setFieldValues($entity,$fieldValues,$isSubEntity,$entityClass=null)
	{
		if($entityClass===null)
			$entityClass=get_class($entity);
		
		$isSubEntity=is_subclass_of($entityClass, SubEntity::class);
		if($isSubEntity)
			$parentFieldName=$entityClass::getParentFieldName();
		
		foreach($fieldValues as $fieldName => $fieldValue){
			if(!property_exists($entityClass, $fieldName))
				throw new Exception("The property '$fieldName' does not exist in class '$entityClass'.");
			
			if($isSubEntity && $fieldName==$parentFieldName){
				// parent field of sub entity is definitely an entity
				$entity->$fieldName=$fieldValue;
				continue;
			}
			
			$baseFieldConstName="$entityClass::$fieldName";
			$fieldType=constant($baseFieldConstName."FieldType");
			switch($fieldType){
				case FieldTypeEnum::PROPERTY:
					$propertyType=constant($baseFieldConstName."PropertyType");
					$entity->$fieldName=PropertyTypeCreator::create($fieldValue, $propertyType);
					break;
				case FieldTypeEnum::MANY_TO_ONE:
				case FieldTypeEnum::ONE_TO_MANY:
				case FieldTypeEnum::MANY_TO_MANY:
					$entity->$fieldName=$fieldValue;
					break;
				default:
					throw new Exception("The field type '$fieldType' is not supported.");
			}
		}
	}
}
