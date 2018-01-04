<?php

/*
 * FieldEntity.php
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
use ReflectionClass;
use BlueDB\Configuration\BlueDBProperties;
use BlueDB\DataAccess\MySQL;
use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Criteria\Expression;
use BlueDB\DataAccess\JoinType;
use BlueDB\DataAccess\Session;
use BlueDB\Entity\FieldTypeEnum;
use BlueDB\Entity\PropertyTypeEnum;
use BlueDB\Utility\StringUtility;

abstract class FieldEntity extends DatabaseTable implements IFieldEntity
{
	/**
	 * Used for key references in creating JSON arrays.
	 * 
	 * @var int
	 */
	private static $KEYCounter=0;
	
	/**
	 * A cache for reflection information of entity classes.
	 * 
	 * class -> [Exists(bool),IsSubentity(bool)]
	 * 
	 * @var array
	 */
	private static $reflectionCache=[];
	
	/**
	 * 
	 * @param string $class Class of which to get the data.
	 * @return array class -> [Exists(bool),IsSubentity(bool)]
	 */
	private static function getClassData($class)
	{
		// check the lookup table
		if(!isset(self::$reflectionCache[$class])){
			$reflectionData=[];
			$reflectionData[]=class_exists($class,false);
			if($reflectionData[0]){
				$reflectionData[]=is_subclass_of($class, SubEntity::class);
			}
			self::$reflectionCache[$class]=$reflectionData;
		}
		return self::$reflectionCache[$class];
	}
	
	/**
	 * Converts this field entity into an array that can be encoded to JSON.
	 * 
	 * When possible, use \BlueDB\IO\JSON::toArray().
	 * 
	 * @param array $fieldsToIgnore [optional]
	 * @return array
	 */
	public function toArray($fieldsToIgnore=null)
	{
		$session=[];
		return $this->toArrayInternal($fieldsToIgnore, $session);
	}
	
	/**
	 * Converts provided field entities into an array that can be encoded to JSON.
	 * 
	 * When possible, use \BlueDB\IO\JSON::toArray().
	 * 
	 * @param array $entities Field entities to be converted.
	 * @param array $fieldsToIgnore [optional]
	 * @return array
	 */
	public static function toArrayList($entities,$fieldsToIgnore=null)
	{
		$session=[];
		
		$elements=[];
		foreach($entities as $entity){
			/* @var $entity FieldEntity */
			$elements[]=$entity->toArrayInternal($fieldsToIgnore, $session);
		}
		
		return $elements;
	}
	
	/**
	 * Encodes this field entity into a JSON string.
	 * 
	 * When possible, use \BlueDB\IO\JSON::encode().
	 * 
	 * @param array $fieldsToIgnore [optional]
	 * @return string A JSON encoded string.
	 * @throws Exception
	 */
	public function toJson($fieldsToIgnore=null)
	{
		$json=json_encode($this->toArray($fieldsToIgnore));
		if($json===false){
			throw new Exception("Encoding a field entity to JSON was not successful.");
		}
		return $json;
	}
	
	/**
	 * Encodes provided field entities to a JSON string.
	 * 
	 * When possible, use \BlueDB\IO\JSON::encode().
	 * 
	 * @param array $entities Field entities to be encoded.
	 * @param array $fieldsToIgnore [optional]
	 * @return string A JSON encoded string.
	 * @throws Exception
	 */
	public static function toJsonList($entities,$fieldsToIgnore=null)
	{
		$json=json_encode(self::toArrayList($entities, $fieldsToIgnore));
		if($json===false){
			throw new Exception("Encoding a list of field entities to JSON was not successful.");
		}
		return $json;
	}
	
	/**
	 * Decodes provided array into entities.
	 * 
	 * Note that the array must be in a correct format.
	 * 
	 * @param array $array
	 * @return array|FieldEntity A single or an array of entities.
	 */
	public static function fromArray($array)
	{
		$session=[];
		if(isset($array["Key"])){
			// is a single entity
			return self::fromArraySingle($array,$session);
		}
		return self::fromArrayList($array,$session);
	}
	
	/**
	 * Decodes provided array into a list of entities.
	 * 
	 * @param array $array
	 * @param array $session An array of already decoded objects and their keys.
	 * @return array
	 */
	private static function fromArrayList($array,&$session)
	{
		$list=[];
		
		foreach($array as $element){
			$list[]=self::fromArraySingle($element,$session);
		}
		
		return $list;
	}
	
	/**
	 * Decodes provided array into an entity.
	 * 
	 * @param array $array
	 * @param array $session An array of already decoded objects and their keys.
	 * @return FieldEntity
	 */
	private static function fromArraySingle($array,&$session)
	{
		$key=$array["Key"];
		
		if(!isset($array["Type"])){
			// is only a key
			// should be already present in the lookup table
			if(!isset($session[$key])){
				throw new Exception("Provided array was not in a correct format: key not found.");
			}
			return $session[$key];
		}
		
		$type=$array["Type"];
		$class=BlueDBProperties::instance()->Namespace_Entities."\\$type";
		
		$classData=self::getClassData($class);
		if(!$classData[0]){
			throw new Exception("Class '$class' does not exist. Did you forget to set the namespace for entities in the configuration?");
		}
		
		$entity=new $class();
		
		// add to lookup table
		$session[$key]=$entity;
		
		foreach($array["Properties"] as $propertyName => $propertyValue){
			/*
			if(!property_exists($class, $propertyName)){
				throw new Exception("Property '$propertyName' does not exist in class '$class'.");
			}
			*/
			
			if($propertyValue===null){
				$entity->$propertyName=null;
				continue;
			}
			
			$propertyBaseConstName="$class::$propertyName";
			
			$fieldTypeConstName=$propertyBaseConstName."FieldType";
			if(defined($fieldTypeConstName)){
				$propertyFieldType=constant($fieldTypeConstName);
			}else{
				// let's assume that it's the parent field of a sub entity
				$propertyFieldType=FieldTypeEnum::MANY_TO_ONE;
			}
			
			switch($propertyFieldType){
				case FieldTypeEnum::PROPERTY:
					$propertyType=constant($propertyBaseConstName."PropertyType");
					$value=PropertySanitizer::sanitize($propertyValue,$propertyType);
					break;
				case FieldTypeEnum::MANY_TO_ONE:
					$value=self::fromArraySingle($propertyValue,$session);
					break;
				case FieldTypeEnum::ONE_TO_MANY:
				case FieldTypeEnum::MANY_TO_MANY:
					$value=self::fromArrayList($propertyValue,$session);
					break;
				default:
					throw new Exception("Unsupported field type: '$propertyFieldType'.");
			}
			
			$entity->$propertyName=$value;
		}
		
		return $entity;
	}
	
	/**
	 * Decodes provided JSON string.
	 * 
	 * Note that the JSON must be in a correct format.
	 * 
	 * @param string $json A JSON encoded string.
	 * @return array|FieldEntity A single or an array of entities.
	 * @throws Exception
	 */
	public static function fromJson($json)
	{
		$array=json_decode($json,true);
		if($array===null){
			throw new Exception("");
		}
		return self::fromArray($array);
	}
	
	/**
	 * @param array $fieldsToIgnore
	 * @param array $session An array of already used objects and their keys, grouped by class.
	 * @return array
	 */
	private function toArrayInternal($fieldsToIgnore,&$session)
	{
		$class=static::class;
		
		// check if already present in the session
		if(isset($session[$class])){
			foreach($session[$class] as $key => $object){
				if($object===$this){
					$array=[];
					$array["Key"]=$key;
					return $array;
				}
			}
		}else{
			$session[$class]=[];
		}
		
		// add it to the session
		$key=self::$KEYCounter++;
		$session[$class][$key]=$this;
		
		// add properties
		$properties=[];
		$fields=$class::getFieldList();
		foreach($fields as $field){
			if($this->$field===null){
				continue;
			}
			if($fieldsToIgnore!==null && in_array($field,$fieldsToIgnore)){
				continue;
			}
			
			$baseFieldConstName=$class."::".$field;
			$fieldType=constant($baseFieldConstName."FieldType");
			
			switch($fieldType){
				case FieldTypeEnum::PROPERTY:
					$propertyType=constant($baseFieldConstName."PropertyType");
					$propertyValue=PropertyTypeEnum::convertToString($this->$field, $propertyType);
					break;
				case FieldTypeEnum::MANY_TO_ONE:
					/* @var $fieldEntity FieldEntity */
					$fieldEntity=$this->$field;
					$propertyValue=$fieldEntity->toArrayInternal(null,$session);
					break;
				case FieldTypeEnum::ONE_TO_MANY:
				case FieldTypeEnum::MANY_TO_MANY:
					$propertyValue=[];
					foreach($this->$field as $element){
						/* @var $element FieldEntity */
						$propertyValue[]=$element->toArrayInternal(null,$session);
					}
					break;
				default:
					throw new Exception("Field type '$fieldType' is not supported.");
			}
			
			$properties[$field]=$propertyValue;
		}
		
		$classData=self::getClassData($class);
		$isSubEntity=$classData[1];
		if($isSubEntity){
			// add parent as a property
			$parentFieldName=$class::getParentFieldName();
			/* @var $parent FieldEntity */
			$parent=$this->$parentFieldName;
			
			$properties[$parentFieldName]=$parent->toArrayInternal($fieldsToIgnore, $session);
		}
		
		$array=[];
		$array["Type"]=$this->getClassName();
		$array["Key"]=$key;
		$array["Properties"]=$properties;
		
		return $array;
	}
	
	/**
	 * Lookup table for unqualified short class names without the namespace.
	 * 
	 * @var array
	 */
	private static $classNames=[];
	
	/**
	 * Gets the unqualified short class name without the namespace.
	 * 
	 * @return string
	 */
	private function getClassName()
	{
		$class=static::class;
		
		// search in lookup table
		if(isset(self::$classNames[$class])){
			return self::$classNames[$class];
		}
		
		$shortClassName=substr($class,strrpos($class,"\\")+1);
		
		// save to lookup table
		self::$classNames[$class]=$shortClassName;
		
		return $shortClassName;
	}
	
	/**
	 * Lookup table for field lists of entity classes.
	 * 
	 * @var array
	 */
	private static $fieldLists=[];
	
	/**
	 * @return array
	 */
	public static function getFieldList()
	{
		$childClassName=get_called_class();
		
		// search in lookup table
		if(isset(self::$fieldLists[$childClassName])){
			return self::$fieldLists[$childClassName];
		}
		
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
					if($constantList[$isHiddenConstant]){
						// it is hidden, do not include it
						continue;
					}
				}
				
				$fieldList[]=$constantValue;
			}
		}
		
		// save to lookup table
		self::$fieldLists[$childClassName]=$fieldList;
		
		return $fieldList;
	}
	
	/**
	 * @param int $ID
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclManyToOne [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return FieldEntity
	 */
	public static function loadByID($ID,$fields=null,$fieldsToIgnore=null,$inclManyToOne=null,$inclOneToMany=null,$inclManyToMany=null)
	{
		self::checkConfig($inclManyToOne,$inclOneToMany,$inclManyToMany);
		$childClassName=get_called_class();
		$session=new Session();
		return $childClassName::loadByIDInternal($ID,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session);
	}
	
	/**
	 * @param int $ID
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclManyToOne
	 * @param bool $inclOneToMany
	 * @param bool $inclManyToMany
	 * @param Session $session
	 * @return FieldEntity
	 */
	protected static function loadByIDInternal($ID,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session)
	{
		// this is a workaround, because PHP does not allow protected static abstract methods
		throw new Exception("This method is abstract.");
	}
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclManyToOne [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return FieldEntity
	 */
	public static function loadByCriteria($criteria,$fields=null,$fieldsToIgnore=null,$inclManyToOne=null,$inclOneToMany=null,$inclManyToMany=null)
	{
		self::checkConfig($inclManyToOne,$inclOneToMany,$inclManyToMany);
		$childClassName=get_called_class();
		$session=new Session();
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
	 * @return FieldEntity
	 */
	protected static function loadByCriteriaInternal($criteria,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany, $inclManyToMany,$session)
	{
		// this is a workaround, because PHP does not allow protected static abstract methods
		throw new Exception("This method is abstract.");
	}
	
	/**
	 * Is the same as calling loadListByCriteria with $criteria=null.
	 * 
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclManyToOne [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return array
	 */
	public static function loadList($fields=null,$fieldsToIgnore=null,$inclManyToOne=null,$inclOneToMany=null,$inclManyToMany=null)
	{
		$childClassName=get_called_class();
		return $childClassName::loadListByCriteria(null, $fields, $fieldsToIgnore, $inclManyToOne, $inclOneToMany, $inclManyToMany);
	}
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclManyToOne [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return array
	 */
	public static function loadListByCriteria($criteria, $fields=null, $fieldsToIgnore=null, $inclManyToOne=null, $inclOneToMany=null, $inclManyToMany=null)
	{
		self::checkConfig($inclManyToOne,$inclOneToMany,$inclManyToMany);
		$childClassName=get_called_class();
		$session=new Session();
		return $childClassName::loadListByCriteriaInternal($criteria,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session);
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
		// this is a workaround, because PHP does not allow protected static abstract methods
		throw new Exception("This method is abstract.");
	}
	
	/**
	 * Does not save ManyToOne fields, only sets the ID.
	 * 
	 * @param array $fieldEntities
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 * @param bool $inclOneToMany [optional]
	 */
	public static function saveList($fieldEntities, $beginTransaction=true, $commit=true)
	{
		$calledClass=get_called_class();
		
		if($beginTransaction){
			MySQL::beginTransaction();
		}
		
		foreach($fieldEntities as $fieldEntity){
			$calledClass::save($fieldEntity, false, false);
		}
		
		if($commit){
			MySQL::commitTransaction();
		}
	}
	
	/**
	 * Does not update ManyToOne fields, only sets the ID.
	 * Does not update OneToMany & ManyToMany fields.
	 * 
	 * @param array $fieldEntities
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 * @param array $fields [optional]
	 * @param bool $updateParents [optional] Only important for SubEntities. It determines whether to update parent tables.
	 */
	public static function updateList($fieldEntities,$beginTransaction=true,$commit=true,$fields=null,$updateParents=true)
	{
		$calledClass=get_called_class();
		
		if($beginTransaction){
			MySQL::beginTransaction();
		}
		
		foreach($fieldEntities as $fieldEntity){
			$calledClass::update($fieldEntity, false, false, $fields,$updateParents);
		}
		
		if($commit){
			MySQL::commitTransaction();
		}
	}
	
	/**
	 * Does not delete child ManyToOne fields.
	 * 
	 * @param FieldEntity $fieldEntity
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
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
	 * @param bool $beginTransaction
	 * @param bool $commit
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
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	public static function deleteList($fieldEntities,$beginTransaction=true,$commit=true)
	{
		$calledClass=get_called_class();
		
		if($beginTransaction){
			MySQL::beginTransaction();
		}
		
		foreach($fieldEntities as $fieldEntity){
			$calledClass::delete($fieldEntity,false,false);
		}
		
		if($commit){
			MySQL::commitTransaction();
		}
	}
	
	/**
	 * Only allowed for property type fields or for ManyToOne when the value is null or integer (to check for ID).
	 * 
	 * @param string $field
	 * @param mixed $value
	 * @param string $parentClass [optional] Actual parent class (if calling class is SubEntity) that contains the specified field.
	 * @return bool TRUE if the provided value exists in the provided fields column in the called entity table.
	 */
	public static function exists($field,$value,$parentClass=null)
	{
		$childClassName=get_called_class();
		if($parentClass===null){
			$parentClass=$childClassName;
		}
		
		$fieldBaseConstName="$parentClass::$field";
		$fieldType=constant($fieldBaseConstName."FieldType");
		
		if($fieldType==FieldTypeEnum::MANY_TO_ONE){
			if($value!==null && !is_int($value)){
				throw new Exception("Exists is only allowed either for property type fields or for ManyToOne when the value is null or integer (to check for ID). Value '$value' is neither null or int.");
			}
			$fieldPropertyType=PropertyTypeEnum::INT;
		}else if($fieldType!=FieldTypeEnum::PROPERTY){
			throw new Exception("Exists is only allowed either for property type fields or for ManyToOne when the value is null or integer (to check for ID). Field '$field' is of unsupported property type on class '$parentClass'.");
		}else{
			$fieldPropertyType=constant($fieldBaseConstName."PropertyType");
		}
		
		$childTableName=$childClassName::getTableName();
		
		$query="SELECT EXISTS(SELECT 1 FROM $childTableName";
		
		if($childClassName!==$parentClass){
			// has to join parent table
			$joinTable=$parentClass::getTableName();
			$joinBaseColumn=$childClassName::getIDColumn();
			$joinColumn=$parentClass::getIDColumn();
			$query.=" ".JoinType::INNER." JOIN $joinTable ON $childTableName.$joinBaseColumn=$joinTable.$joinColumn";
		}
		
		$fieldColumn=constant($fieldBaseConstName."Column");
		$query.=" WHERE ($fieldColumn=?)) AS result";
		
		$parameters=[];
		$parameters[]=PropertyTypeEnum::getPreparedStmtType($fieldPropertyType);
		$parameters[]=&$value;
		
		/*@var $result array*/
		$result=MySQL::prepareAndExecuteSelectSingleStatement($query, $parameters);
		
		return $result["result"]==1;
	}
	
	/**
	 * @param Criteria $criteria
	 * @return bool TRUE if an entry exists that meets criterias restrictions.
	 */
	public static function existsByCriteria($criteria)
	{
		$childClassName=get_called_class();
		$childTableName=$childClassName::getTableName();
		
		$query="SELECT EXISTS(SELECT 1 FROM ".$childTableName;
		
		$criteria->prepare();
		if(!empty($criteria->PreparedQueryJoins)){
			$query.=" ".$criteria->PreparedQueryJoins;
		}
		if(!empty($criteria->PreparedQueryRestrictions)){
			$query.=" WHERE ".$criteria->PreparedQueryRestrictions;
		}
		$query.=") AS result";
		
		if(count($criteria->PreparedParameters)>1){
			$result=MySQL::prepareAndExecuteSelectSingleStatement($query, $criteria->PreparedParameters);
		} else{
			$result=MySQL::selectSingle($query);
		}
		
		return $result["result"]==1;
	}
	
	/**
	 * Insert or update.
	 * 
	 * @param QueryTypeEnum $type
	 * @param string $calledClass
	 * @param FieldEntity $fieldEntity
	 * @param array $fields
	 * @param bool $beginTransaction
	 * @param bool $commit
	 * @param bool $isSubEntity
	 * @param bool $updateParents Should be true only for SubEntities. It determines whether to update parent tables.
	 */
	protected static function performQuery($type,$calledClass,$fieldEntity,$fields,$beginTransaction,$commit,$isSubEntity,$updateParents)
	{
		switch($type){
			case QueryTypeEnum::INSERT:
				if($fieldEntity->getID()!=null){
					throw new Exception("The provided objects ID is not null. Call Update function instead.");
				}
				break;
			case QueryTypeEnum::UPDATE:
				if($fieldEntity->getID()==null){
					throw new Exception("The provided objects ID is null. Call Save function instead.");
				}
				break;
			default:
				throw new Exception("Query of type '$type' is not supported.");
		}
		
		$childClassName=get_class($fieldEntity);
		if($childClassName!==$calledClass){
			throw new Exception("Type of the provided object '$childClassName' is not the same as the called class '$calledClass'.");
		}
		
		if($beginTransaction){
			MySQL::beginTransaction();
		}

		if($isSubEntity && $type===QueryTypeEnum::INSERT){
			// first, parent tables have to be created ...
			$parentClass=$childClassName::getParentEntityClass();
			$parentFieldName=$childClassName::getParentFieldName();
			$parentEntity=$fieldEntity->$parentFieldName;
			$parentClass::save($parentEntity,false,false);
		}
		
		$baseEntityTableName=$childClassName::getTableName();
		
		$preparedValues=[];
		$preparedValues[]="";
		$preparedValuesDirect=[];
		$preparedValuesDirectIndex=0;
		
		$useFieldsOfParent=$updateParents;
		
		if(empty($fields)){
			$fields=$childClassName::getFieldList();
			$useFieldsOfParent=false;
		}
		
		if($updateParents && $useFieldsOfParent){
			$fieldsOfParent=[];
		}
		
		switch($type){
			case QueryTypeEnum::INSERT:
				$query="INSERT INTO $baseEntityTableName (";
				break;
			case QueryTypeEnum::UPDATE:
				$query="UPDATE $baseEntityTableName SET ";
				break;
		}
		if($isSubEntity && $type===QueryTypeEnum::INSERT){
			$isFirst=false;
			// set ID to parent
			$query.=$childClassName::getIDColumn();
			$parentID=$fieldEntity->$parentFieldName->getID();
			$preparedValues[0].="i";
			$preparedValues[]=&$parentID;
		}else{
			$isFirst=true;
		}
		foreach($fields as $field){
			if($type===QueryTypeEnum::INSERT && $fieldEntity->$field==null){
				continue;
			}
			
			$fieldBaseConstName="$childClassName::$field";
			$fieldTypeConstName=$fieldBaseConstName."FieldType";
			if($useFieldsOfParent && !defined($fieldTypeConstName)){
				// this field is in parent entity ...
				$fieldsOfParent[]=$field;
				continue;
			}
			/*@var $fieldType FieldTypeEnum */
			$fieldType=constant($fieldTypeConstName);
			switch($fieldType){
				case FieldTypeEnum::PROPERTY:
					if(!$isFirst){
						$query.=",";
					} else{
						$isFirst=false;
					}

					$query.=constant($fieldBaseConstName."Column");
					if($type==QueryTypeEnum::UPDATE){
						$query.="=?";
					}
					
					/*@var $propertyType PropertyTypeEnum*/
					$propertyType=constant($fieldBaseConstName."PropertyType");
					
					$preparedValues[0].=PropertyTypeEnum::getPreparedStmtType($propertyType);
					$preparedValuesDirect[]=PropertyTypeEnum::convertToString($fieldEntity->$field, $propertyType);
					$preparedValues[]=&$preparedValuesDirect[$preparedValuesDirectIndex];
					++$preparedValuesDirectIndex;
					break;
				case FieldTypeEnum::MANY_TO_ONE:
					if(!$isFirst){
						$query.=",";
					} else{
						$isFirst=false;
					}
					
					$query.=constant($fieldBaseConstName."Column");
					if($type==QueryTypeEnum::UPDATE){
						$query.="=?";
					}

					$preparedValues[0].=PropertyTypeEnum::getPreparedStmtType(PropertyTypeEnum::INT);
					
					if($fieldEntity->$field===null){
						$preparedValues[]=&$fieldEntity->$field;
					}else{
						/* @var $object FieldEntity */
						$object=$fieldEntity->$field;
						
						$ID=$object->getID();
						if($ID==null){
							throw new Exception("Field '$field' does not have a set ID.");
						}
						
						$preparedValuesDirect[]=$ID;
						$preparedValues[]=&$preparedValuesDirect[$preparedValuesDirectIndex];
						++$preparedValuesDirectIndex;
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

		if($type===QueryTypeEnum::INSERT || $preparedValuesCount>1){
			// if updating, and no fields are to be updated, there is no need to perform any query ...
			
			switch($type){
				case QueryTypeEnum::INSERT:
					// Question marks
					$query.=") VALUES (";
					if($preparedValuesCount>1){
						$isFirst=true;
						for($i=1;$i<$preparedValuesCount;++$i){
							if(!$isFirst){
								$query.=",";
							} else{
								$isFirst=false;
							}
							$query.="?";
						}
					}
					$query.=")";
					break;
				case QueryTypeEnum::UPDATE:
					// Condition
					$query.=" WHERE $baseEntityTableName.".$childClassName::getIDColumn()."=?";
					$preparedValues[0].=PropertyTypeEnum::getPreparedStmtType(PropertyTypeEnum::INT);
					$preparedValuesDirect[]=$fieldEntity->getID();
					$preparedValues[]=&$preparedValuesDirect[$preparedValuesDirectIndex];
					++$preparedValuesCount;
					break;
			}

			try{
				if($preparedValuesCount>1){
					MySQL::prepareAndExecuteStatement($query, $preparedValues);
				} else{
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

			if(!$isSubEntity && $type===QueryTypeEnum::INSERT){
				$fieldEntity->setID(MySQL::autogeneratedID());
			}
		}
		
		if($type===QueryTypeEnum::UPDATE && $updateParents && !($useFieldsOfParent && empty($fieldsOfParent))){
			$parentEntityClass=$childClassName::getParentEntityClass();
			$parentFieldName=$childClassName::getParentFieldName();
			$fieldsForParent=$useFieldsOfParent?$fieldsOfParent:null;
			$parentEntityClass::update($fieldEntity->$parentFieldName,false,false,$fieldsForParent,true);
		}
		
		if($commit){
			MySQL::commitTransaction();
		}
	}
	
	/**
	 * Lookup table for fields that are pointing back.
	 * 
	 * @var array
	 */
	private static $pointingBack=[];
	
	/**
	 * Checks if two tables are pointing to each other, because if they are, it can come to a bizare thing: two rows pointing to each other.
	 * If that happens, the constraint must first be set to null and only then can this entity be deleted
	 * 
	 * @param string $childClassName
	 * @param FieldEntity $fieldEntity
	 * @param Session $session
	 * @param bool $beginTransaction
	 */
	protected static function prepareForDeletion($childClassName,$fieldEntity,$session,$beginTransaction)
	{
		if($beginTransaction){
			MySQL::beginTransaction();
		}
		
		if(isset(self::$pointingBack[$childClassName])){
			// already in lookup table, no need to search again
			$pointingBack=self::$pointingBack[$childClassName];
		}else{
			// first it looks for all ManyToOne fields
			$manyToOneFields=[];
			$fields=$childClassName::getFieldList();
			foreach($fields as $field){
				$fieldBaseConstName="$childClassName::$field";
				$fieldType=constant($fieldBaseConstName."FieldType");
				if($fieldType===FieldTypeEnum::MANY_TO_ONE){
					$manyToOneField=[];
					$manyToOneField["Field"]=$field;
					$manyToOneField["Class"]=constant($fieldBaseConstName."Class");
					$manyToOneFields[]=$manyToOneField;
				}
			}
			$pointingBack=[];
			if(!empty($manyToOneFields)){
				// then it checks all fields of these ManyToOne classes and checks if any of them has ManyToOne field with the current class (in other words: if any of them is pointing back)
				foreach($manyToOneFields as $manyToOneFieldArray){
					$manyToOneField=$manyToOneFieldArray["Field"];
					$class=$manyToOneFieldArray["Class"];

					$fields=$class::getFieldList();
					foreach($fields as $field){
						$fieldBaseConstName="$class::$field";
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
			}
			self::$pointingBack[$childClassName]=$pointingBack;
		}
		
		if(empty($pointingBack)){
			// there are no fields that are pointing back ...
			return;
		}
		
		// now it loads those fields and checks if any of them is actually pointing to the object that is being deleted
		// and if it is, it sets that field to null
		$ID=$fieldEntity->getID();
		/* @var $dto FieldEntity */
		$dto=new $childClassName();
		$dto->setID($ID);
		foreach($pointingBack as $pointingBackArray){
			$baseField=$pointingBackArray["BaseField"];
			$class=$pointingBackArray["Class"];
			$field=$pointingBackArray["Field"];
			$criteria=new Criteria($class);
			$criteria->add(Expression::equal($class, $field, $dto));
			$objects=$class::loadListByCriteriaInternal($criteria,[],null,false,false,false,$session);
			if(empty($objects)){
				// nobody is pointing to the entity being deleted
				continue;
			}

			if($fieldEntity->$baseField===null){
				// has to load it
				$fieldEntityWithBaseField=$childClassName::loadByIDInternal($ID,[$baseField],null,true,false,false,$session);
				$fieldEntity->$baseField=$fieldEntityWithBaseField->$baseField;
			}
			
			$neededID=$fieldEntity->$baseField->getID();
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
