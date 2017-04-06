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
	static function getIDField()
	{
		return self::IDField;
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
		$query=self::prepareSelectQuery($childClassName, $criteria, $fields, $fieldsToIgnore,$manyToOneFieldsToLoad,$inclOneToMany,$oneToManyListsToLoad);
		
		if(count($criteria->PreparedParameters)>1)
			$loadedArray=MySQL::prepareAndExecuteSelectSingleStatement($query,$criteria->PreparedParameters);
		else
			$loadedArray=MySQL::selectSingle($query);
		
		if($loadedArray===null)
			return null;
		
		$addToSession=$fields===null && $fieldsToIgnore===null && $inclOneToMany===true;
		$loadedEntity=self::createInstance($childClassName, $loadedArray,$manyToOneFieldsToLoad,$oneToManyListsToLoad,$addToSession,$session);
		
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
		$query=self::prepareSelectQuery($childClassName, $criteria, $fields, $fieldsToIgnore,$manyToOneFieldsToLoad,$inclOneToMany,$oneToManyListsToLoad);
		
		if($criteria!==null && count($criteria->PreparedParameters)>1)
			$loadedArray=MySQL::prepareAndExecuteSelectStatement($query, $criteria->PreparedParameters);
		else
			$loadedArray=MySQL::select($query);
		
		$loadedEntities=[];
		
		$addToSession=$fields===null && $fieldsToIgnore===null && $inclOneToMany===true;
		foreach($loadedArray as $array){
			$loadedEntities[]=self::createInstance($childClassName, $array,$manyToOneFieldsToLoad,$oneToManyListsToLoad,$addToSession,$session);
		}
		
		return $loadedEntities;
	}
	
	/**
	 * Does not save ManyToOne fields, only sets the ID.
	 * Does not save OneToMany & ManyToMany fields.
	 * 
	 * @param StrongEntity $strongEntity
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 */
	public static function save($strongEntity,$beginTransaction=true,$commit=true)
	{
		if($strongEntity->ID!=null)
			throw new Exception("The provided objects ID is not null. Call Update function instead.");
		
		$calledClass=get_called_class();
		self::performQuery(QueryTypeEnum::INSERT, $calledClass, $strongEntity, null, $beginTransaction, $commit);
	}
	
	/**
	 * Does not update ManyToOne fields, only sets the ID.
	 * Does not update OneToMany & ManyToMany fields.
	 * 
	 * @param StrongEntity $strongEntity
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 * @param array $fields
	 */
	public static function update($strongEntity,$beginTransaction=true,$commit=true,$fields=null)
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
	 * @param Session $session
	 */
	protected static function deleteInternal($strongEntity,$beginTransaction,$commit,$session)
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
		
		if($beginTransaction)
			MySQL::beginTransaction();
		
		// checks if two tables are pointing to each other, because if they are, it can come to a bizare thing: two rows pointing to each other. If that happens, the constraint must first be set to null and only then can this entity be deleted
		// first is looks for all ManyToOne fields
		$manyToOneFields=[];
		$fields=$childClassName::getFieldList();
		foreach($fields as $field){
			$fieldBaseConstName=$childClassName."::".$field;
			$fieldType=constant($fieldBaseConstName."FieldType");
			if($fieldType===FieldTypeEnum::MANY_TO_ONE){
				$manyToOneField=[];
				$manyToOneField["Field"]=$field;
				$manyToOneField["Class"]=constant($fieldBaseConstName."Class");
				$manyToOneFields[]=$manyToOneField;
			}
		}
		if(!empty($manyToOneFields)){
			// then it checks all fields of these ManyToOne classes and checks if any of them has ManyToOne field with the current class (in other words: if any of them is pointing back)
			$pointingBack=[];
			foreach($manyToOneFields as $manyToOneFieldArray){
				$manyToOneField=$manyToOneFieldArray["Field"];
				$class=$manyToOneFieldArray["Class"];

				$fields=$class::getFieldList();
				foreach($fields as $field){
					$fieldBaseConstName=$class."::".$field;
					$fieldType=constant($fieldBaseConstName."FieldType");
					if($fieldType===FieldTypeEnum::MANY_TO_ONE){
						$pointingToClass=constant($fieldBaseConstName."Class");
						if($pointingToClass===$childClassName){
							// is pointing back
							$pointingBackArray=[];
							$pointingBackArray["BaseField"]=$manyToOneField;
							$pointingBackArray["Class"]=$class;
							$pointingBackArray["Field"]=$field;
							$pointingBack[]=$pointingBackArray;
						}
					}
				}
			}
			if(!empty($pointingBack)){
				// then it loads those fields and checks if any of them is actually pointing to the object that is being deleted
				// and if it is, it sets that field to null
				$ID=$strongEntity->ID;
				$dto=new $childClassName();
				$dto->setID($ID);
				foreach($pointingBack as $pointingBackArray){
					$baseField=$pointingBackArray["BaseField"];
					$class=$pointingBackArray["Class"];
					$field=$pointingBackArray["Field"];
					$criteria=new Criteria($class);
					$criteria->add(Expression::equal($class, $field, $dto));
					$objects=$class::loadListByCriteriaInternal($criteria,[StrongEntity::IDField],null,false,$session);
					if(empty($objects))
						// nobody is pointing to the entity being deleted
						continue;

					$neededID=$strongEntity->$baseField->getID();
					// find the object that the entity being deleted is pointing to
					$foundObject=false;
					foreach($objects as $object){
						/* @var $object FieldEntity */
						if($object->getID()===$neededID){
							$foundObject=true;
							// will/should always be only one, so it can break
							break;
						}
					}
					if($foundObject){
						// it found out that it is being pointed to both ways, so set the field to null
						// $dto already has all fields except ID set to null :)
						try{
							$childClassName::update($dto,false,false,[$baseField]);
						} catch (Exception $ex) {
							MySQL::rollbackTransaction();
							throw $ex;
						}
					}
				}
			}
		}
		
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
	
	/**
	 * @param string $childClassName
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param array $manyToOneFieldsToLoad
	 * @param boolean $inclOneToMany
	 * $param array $oneToManyListsToLoad
	 * @return string Query.
	 */
	private static function prepareSelectQuery($childClassName,$criteria,$fields,$fieldsToIgnore,&$manyToOneFieldsToLoad,$inclOneToMany,&$oneToManyListsToLoad)
	{
		if($criteria!==null && $childClassName!==$criteria->BaseEntityClass)
			throw new Exception("Criterias BaseEntityClass (".$criteria->BaseEntityClass.") is different than the called child class (".$childClassName.").");
		
		$baseEntityTableName=$childClassName::getTableName();
		
		if(empty($fields))
			$fields=$childClassName::getFieldList();
		
		$manyToOneFieldsToLoad=[];
		$oneToManyListsToLoad=[];
		
		$query="SELECT ";
		$isFirst=true;
		foreach($fields as $field){
			if($fieldsToIgnore!=null && in_array($field, $fieldsToIgnore))
				continue;
			
			$fieldBaseConstName=$childClassName."::".$field;
			$fieldType=constant($fieldBaseConstName."FieldType");
			
			switch($fieldType){
				case FieldTypeEnum::PROPERTY:
					if($isFirst)
						$isFirst=false;
					else
						$query.=",";
					
					$fieldColumn=constant($fieldBaseConstName."Column");
					
					$query.="$baseEntityTableName.$fieldColumn AS $field";
					break;
				case FieldTypeEnum::MANY_TO_ONE:
					if($isFirst)
						$isFirst=false;
					else
						$query.=",";
					
					$fieldColumn=constant($fieldBaseConstName."Column");
					
					$manyToOneField=[];
					$manyToOneField["Field"]=$field;
					$manyToOneField["Class"]=constant($fieldBaseConstName."Class");
					
					$manyToOneFieldsToLoad[]=$manyToOneField;
					
					$query.="$baseEntityTableName.$fieldColumn AS $field";
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
	 * @param bool $beginTransaction
	 * @param bool $commit
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
		
		if(empty($fields))
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
					if(!$isFirst)
						$query.=",";
					else
						$isFirst=false;
					
					$query.=constant($fieldBaseConstName."Column");
					if($type==QueryTypeEnum::UPDATE)
						$query.="=?";

					$preparedValues[0].=PropertyTypeEnum::getPreparedStmtType(PropertyTypeEnum::INT);
					
					if($strongEntity->$field===null){
						$preparedValues[]=&$strongEntity->$field;
					}else{
						/* @var $object FieldEntity */
						$object=$strongEntity->$field;
						
						$ID=$object->getID();
						if($ID===null || $ID===0)
							throw new Exception("Field '$field' does not have a set ID.");
						
						$preparedValuesDirect[]=$ID;
						$preparedValues[]=&$preparedValuesDirect[$preparedValuesDirectIndex];
						$preparedValuesDirectIndex++;
					}
					break;
				case FieldTypeEnum::ONE_TO_MANY:
					// ignore
					break;
				case FieldTypeEnum::MANY_TO_MANY:
					// ignore
					break;
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
	 * @param array $manyToOneFieldsToLoad
	 * @param array $oneToManyListsToLoad
	 * @param bool $addToSession
	 * @param Session $session
	 * @return StrongEntity
	 */
	private static function createInstance($entityClass,$fieldValues,$manyToOneFieldsToLoad,$oneToManyListsToLoad,$addToSession,$session)
	{
		$newEntity=new $entityClass();
		self::setFieldValues($newEntity, $fieldValues, $entityClass);
		
		$manyToOneNotEmpty=!empty($manyToOneFieldsToLoad);
		$oneToManyNotEmpty=!empty($oneToManyListsToLoad);
		
		if($addToSession)
			$session->add($newEntity, $entityClass);
		
		if($manyToOneNotEmpty)
			self::loadManyToOneFields($newEntity, $manyToOneFieldsToLoad,$session);
		if($oneToManyNotEmpty)
			self::loadOneToManyLists($entityClass, $newEntity, $oneToManyListsToLoad,$session);
		
		return $newEntity;
	}
	
	/**
	 * @param FieldEntity $entity
	 * @param array $manyToOneFieldsToLoad
	 * @param Session $session
	 */
	private static function loadManyToOneFields($entity,$manyToOneFieldsToLoad,$session)
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
	private static function loadOneToManyLists($entityClass,$entity,$oneToManyLists,$session)
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
	 * @param FieldEntity $entity
	 * @param array $fieldValues
	 * @param string $entityClass Class type of the entity. If not provided, the class is determined with get_class function.
	 */
	private static function setFieldValues($entity,$fieldValues,$entityClass=null)
	{
		if($entityClass==null)
			$entityClass=get_class($entity);
		
		$isSubEntity=is_subclass_of($entityClass, SubEntity::class);
		if($isSubEntity)
			$parentFieldName=$entityClass::getParentFieldName();
		
		foreach($fieldValues as $fieldName => $fieldValue){
			if(!property_exists($entityClass, $fieldName))
				throw new Exception("The property '".$fieldName."' does not exist in class '".$entityClass."'.");
			
			if($isSubEntity && $fieldName==$parentFieldName){
				// parent field of sub entity is definitely an entity
				$entity->$fieldName=$fieldValue;
				continue;
			}
			
			$baseFieldConstName=$entityClass."::".$fieldName;
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
					throw new Exception("The field type '".$fieldType."' is not supported.");
			}
		}
	}
}
