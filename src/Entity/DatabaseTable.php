<?php

/*
 * DatabaseTable.php
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
 * @copyright Apr 26, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

use Exception;
use BlueDB\Configuration\BlueDBProperties;
use BlueDB\DataAccess\MySQL;
use BlueDB\DataAccess\Session;
use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Criteria\Expression;

abstract class DatabaseTable implements IDatabaseTable
{
	/**
	 * Returns the number of rows in this database table.
	 * 
	 * @return int The number of rows in this database table.
	 */
	public static function loadRowCount()
	{
		$query = 'select count(*) from '.self::getTableName().';';
		$result = MySQL::selectSingle($query);
		var_dump($result);
		die();
	}
	
	/**
	 * Checks if values are set or if they have to be set to default values as specified in the configuration file.
	 * 
	 * @param bool $inclOneToMany
	 * @param bool $inclManyToMany
	 */
	protected static function checkConfig(&$inclManyToOne,&$inclOneToMany,&$inclManyToMany)
	{
		if($inclManyToOne!==null && $inclOneToMany!==null && $inclManyToMany!==null){
			return;
		}
		
		$config=BlueDBProperties::instance();
		if($inclManyToOne===null){
			$inclManyToOne=$config->includeManyToOne;
		}
		if($inclOneToMany===null){
			$inclOneToMany=$config->includeOneToMany;
		}
		if($inclManyToMany===null){
			$inclManyToMany=$config->includeManyToMany;
		}
	}
	
	/**
	 * @param string $fieldEntityClass Base class.
	 * @param string $classToLoad Class from which the fields will be loaded. Often it is the same as the $fieldEntityClass.
	 * @param string $joinColumn The join column in base FieldEntity class on which to join the $classToLoad. Should only be set when $classToLoad is not equal to $fieldEntityClass.
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param array $manyToOneFieldsToLoad
	 * @param bool $inclManyToOne
	 * @param bool $inclOneToMany
	 * @param array $oneToManyListsToLoad
	 * @param bool $inclManyToMany
	 * @param array $manyToManyListsToLoad
	 * @param bool $isSubEntity
	 * @param string $parentFieldName
	 * @param array $fieldsOfParent
	 * @return string Query.
	 */
	protected static function prepareSelectQuery($fieldEntityClass,$classToLoad,$joinColumn,$criteria,$fields,$fieldsToIgnore,&$manyToOneFieldsToLoad,$inclManyToOne,$inclOneToMany,&$oneToManyListsToLoad,$inclManyToMany,&$manyToManyListsToLoad,$isSubEntity,$parentFieldName,&$fieldsOfParent)
	{
		$toLoadTableName=$classToLoad::getTableName();
		if($isSubEntity){
			$useFieldsOfParent=true;
		}
		
		if($fields===null){
			$fields=$classToLoad::getFieldList();
			if($isSubEntity){
				$useFieldsOfParent=false;
			}
		}
		
		$manyToOneFieldsToLoad=[];
		$oneToManyListsToLoad=[];
		$manyToManyListsToLoad=[];
		if($isSubEntity && $useFieldsOfParent){
			$fieldsOfParent=[];
		}
		
		$query="SELECT ";
		if($isSubEntity){
			$query.="$toLoadTableName.".$classToLoad::getIDColumn()." AS '$parentFieldName'";
		} else{
			$query.="$toLoadTableName.".StrongEntity::IDColumn." AS ".StrongEntity::IDField;
		}
		foreach($fields as $field){
			if($fieldsToIgnore!=null && in_array($field, $fieldsToIgnore)){
				continue;
			}
			if($field===StrongEntity::IDField){
				continue;
			}
			
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
					$fieldColumn=constant($fieldBaseConstName."Column");
					
					$query.=",$toLoadTableName.$fieldColumn AS '$field'";
					break;
				case FieldTypeEnum::MANY_TO_ONE:
					if(!$inclManyToOne){
						break;
					}
					$fieldColumn=constant($fieldBaseConstName."Column");
					
					$manyToOneField=[];
					$manyToOneField["Field"]=$field;
					$manyToOneField["Class"]=constant($fieldBaseConstName."Class");
					
					$manyToOneFieldsToLoad[]=$manyToOneField;
					
					$query.=",$toLoadTableName.$fieldColumn AS '$field'";
					break;
				case FieldTypeEnum::ONE_TO_MANY:
					if(!$inclOneToMany){
						break;
					}
					$oneToManyList=[];
					$oneToManyList["Field"]=$field;
					$oneToManyList["Class"]=constant($fieldBaseConstName."Class");
					$oneToManyList["Identifier"]=constant($fieldBaseConstName."Identifier");
					$oneToManyListsToLoad[]=$oneToManyList;
					break;
				case FieldTypeEnum::MANY_TO_MANY:
					if(!$inclManyToMany){
						break;
					}
					$manyToManyList=[];
					$manyToManyList["Field"]=$field;
					$manyToManyList["Class"]=constant($fieldBaseConstName."Class");
					$manyToManyList["Side"]=constant($fieldBaseConstName."Side");
					$manyToManyListsToLoad[]=$manyToManyList;
					break;
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
			if(!empty($criteria->PreparedQueryJoins)){
				$query.=' '.$criteria->PreparedQueryJoins;
			}
			// conditions
			if(!empty($criteria->PreparedQueryRestrictions)){
				$query.=' WHERE '.$criteria->PreparedQueryRestrictions;
			}
			// ordering
			if($criteria->OrderingFields!==null){
				$query.=' ORDER BY ';
				$count=count($criteria->OrderingFields);
				for($i=0;$i<$count;++$i){
					$orderingField=$criteria->OrderingFields[$i];
					if($i>0){
						$query.=', ';
					}
					switch(count($orderingField)){
						case 2:
							$fieldToOrderBy = constant("$classToLoad::$orderingField[0]Column");
							$ascending=$orderingField[1];
							break;
						case 3:
							$orderByOperator=$orderingField[0];
							$fieldsToOrderBy=$orderingField[1];
							$ascending=$orderingField[2];
							$fieldToOrderBy='('.constant("$classToLoad::$fieldsToOrderBy[0]Column");
							$countJ=count($fieldsToOrderBy);
							for($j=1;$j<$countJ;++$j){
								$fieldToOrderBy.=" $orderByOperator ".constant("$classToLoad::$fieldsToOrderBy[$j]Column");
							}
							$fieldToOrderBy.=')';
							break;
						default:
							throw new Exception('Invalid ordering field in criteria.');
					}
					$query.=$fieldToOrderBy;
					if(!$ascending){
						$query.=' DESC';
					}
				}
			}
			// limit
			if($criteria->Limit!==null){
				$query.=' LIMIT ';
				$offset=$criteria->Limit[0];
				$count=$criteria->Limit[1];
				if($offset===0){
					$query.=$count;
				}else{
					$query.=$offset.', '.$count;
				}
			}
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
		if($criteria!==null && count($criteria->PreparedParameters)>1){
			return MySQL::prepareAndExecuteSelectStatement($selectQuery, $criteria->PreparedParameters);
		}
		
		return MySQL::select($selectQuery);
	}
	
	/**
	 * @param string $selectSingleQuery
	 * @param Criteria $criteria
	 * @return array
	 */
	protected static function executeSelectSingleQuery($selectSingleQuery,$criteria)
	{
		if(count($criteria->PreparedParameters)>1){
			return MySQL::prepareAndExecuteSelectSingleStatement($selectSingleQuery,$criteria->PreparedParameters);
		}
		
		return MySQL::selectSingle($selectSingleQuery);
	}
	
	/**
	 * @param string $entityClass
	 * @param array $fieldValues
	 * @param array $manyToOneFieldsToLoad
	 * @param array $oneToManyListsToLoad
	 * @param bool $inclManyToOne
	 * @param bool $inclOneToMany
	 * @param bool $inclManyToMany
	 * @param Session $session
	 * @param bool $isSubEntity
	 * @param string $parentClass
	 * @param string $parentFieldName
	 * @param array $fieldsOfParent
	 * @param array $fieldsToIgnore
	 * @return FieldEntity
	 */
	protected static function createInstance($entityClass,$fieldValues,$manyToOneFieldsToLoad,$oneToManyListsToLoad,$manyToManyListsToLoad,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session,$isSubEntity,$parentClass,$parentFieldName,$fieldsOfParent,$fieldsToIgnore)
	{
		$newEntity=new $entityClass();
		self::setFieldValues($newEntity, $fieldValues,$isSubEntity, $entityClass);
		
		$ID=$isSubEntity?intval($newEntity->$parentFieldName):$newEntity->ID;
		
		if(!$session->add($newEntity,$entityClass,$ID)){
			return $session->lookUp($entityClass, $ID);
		}
		
		if(!empty($manyToOneFieldsToLoad)){
			self::loadManyToOneFields($newEntity, $manyToOneFieldsToLoad,$inclManyToOne,false,false,$session);
		}
		if($isSubEntity){
			$newEntity->$parentFieldName=$parentClass::loadByIDInternal($ID,$fieldsOfParent,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session);
		}
		if(!empty($oneToManyListsToLoad)){
			self::loadOneToManyLists($entityClass, $newEntity, $oneToManyListsToLoad,$inclManyToOne,false,false,$isSubEntity,$session);
		}
		if(!empty($manyToManyListsToLoad)){
			self::loadManyToManyLists($entityClass, $newEntity, $manyToManyListsToLoad,$inclManyToOne, false, false, $session);
		}
		
		return $newEntity;
	}
	
	/**
	 * @param FieldEntity $entity
	 * @param array $manyToOneFieldsToLoad
	 * @param bool $inclManyToOne
	 * @param bool $inclOneToMany
	 * @param bool $inclManyToMany
	 * @param Session $session
	 */
	protected static function loadManyToOneFields($entity,$manyToOneFieldsToLoad,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session)
	{
		foreach($manyToOneFieldsToLoad as $manyToOneField){
			$manyToOneFieldName=$manyToOneField["Field"];
			$manyToOneClass=$manyToOneField["Class"];
			$foreignKey=$entity->$manyToOneFieldName;
			
			if($foreignKey==null){
				continue;
			}
			
			if(is_string($foreignKey)){
				$foreignKey=intval($foreignKey);
			} else if(!is_int($foreignKey)){
				// is an object and has been loaded already (because of references)
				continue;
			}
			
			// first, let's try to look it up in the Session
			$lookUpResult=$session->lookUp($manyToOneClass, $foreignKey);
			if($lookUpResult!==false){
				$manyToOneEntity=$lookUpResult;
			}else{
				$manyToOneEntity=$manyToOneClass::loadByIDInternal($foreignKey,null,null,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session);
			}
			
			$entity->$manyToOneFieldName=$manyToOneEntity;
		}
	}
	
	/**
	 * @param string $entityClass
	 * @param FieldEntity $entity
	 * @param array $oneToManyLists
	 * @param bool $inclManyToOne
	 * @param bool $inclOneToMany
	 * @param bool $inclManyToMany
	 * @param bool $isSubEntity
	 * @param Session $session
	 */
	protected static function loadOneToManyLists($entityClass,$entity,$oneToManyLists,$inclManyToOne,$inclOneToMany,$inclManyToMany,$isSubEntity,$session)
	{
		$ID=$entity->getID();
		
		/* @var $entityDTO FieldEntity */
		if($isSubEntity){
			$entityDTO=$entityClass::createEmpty();
		}else{
			$entityDTO=new $entityClass();
		}
		$entityDTO->setID($ID);
		foreach($oneToManyLists as $oneToManyList){
			$oneToManyFieldName=$oneToManyList["Field"];
			$oneToManyClass=$oneToManyList["Class"];
			$identifier=$oneToManyList["Identifier"];
			
			// first, let's try to look it up in the Session
			$lookUpResult=&$session->lookUpOneToManyList($entityClass, $oneToManyFieldName, $ID);
			if($lookUpResult!==false){
				$list=&$lookUpResult;
			}else{
				$list=&$session->reserveOneToManyList($entityClass, $oneToManyFieldName, $ID);
				$criteria=new Criteria($oneToManyClass);
				$criteria->add(Expression::equal($oneToManyClass, $identifier, $entityDTO));
				$loadedEntities=$oneToManyClass::loadListByCriteriaInternal($criteria,null,null,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session);
				foreach($loadedEntities as $loadedEntity){
					$loadedEntity->$identifier=$entity;
					$list[]=$loadedEntity;
				}
			}
			
			$entity->$oneToManyFieldName=&$list;
		}
	}
	
	/**
	 * @param string $entityClass
	 * @param FieldEntity $entity
	 * @param array $manyToManyLists
	 * @param bool $inclManyToOne
	 * @param bool $inclOneToMany
	 * @param bool $inclManyToMany
	 * @param Session $session
	 */
	protected static function loadManyToManyLists($entityClass,$entity,$manyToManyLists,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session)
	{
		$ID=$entity->getID();
		
		/* @var $entityDTO FieldEntity */
		$entityDTO=new $entityClass();
		$entityDTO->setID($ID);
		foreach($manyToManyLists as $manyToManyList){
			$manyToManyFieldName=$manyToManyList["Field"];
			
			// first, let's try to look it up in the Session
			$lookUpResult=&$session->lookUpManyToManyList($entityClass, $manyToManyFieldName, $ID);
			if($lookUpResult!==false){
				$list=&$lookUpResult;
			}else{
				$list=&$session->reserveManyToManyList($entityClass, $manyToManyFieldName, $ID);
				
				$manyToManyClass=$manyToManyList["Class"];
				$manyToManySide=$manyToManyList["Side"];
				
				$loadedEntities=$manyToManyClass::loadListForSideInternal($manyToManySide,$ID,null,null,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session);
				foreach($loadedEntities as $loadedEntity){
					$list[]=$loadedEntity;
				}
			}
			
			$entity->$manyToManyFieldName=&$list;
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
		if($entityClass===null){
			$entityClass=get_class($entity);
		}
		
		$isSubEntity=is_subclass_of($entityClass, SubEntity::class);
		if($isSubEntity){
			$parentFieldName=$entityClass::getParentFieldName();
		}
		
		foreach($fieldValues as $fieldName => $fieldValue){
			if(!property_exists($entityClass, $fieldName)){
				throw new Exception("The property '$fieldName' does not exist in class '$entityClass'.");
			}
			
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
					$entity->$fieldName=PropertyCreator::create($fieldValue, $propertyType);
					break;
				case FieldTypeEnum::MANY_TO_ONE:
					$entity->$fieldName=$fieldValue;
					break;
				case FieldTypeEnum::ONE_TO_MANY:
				case FieldTypeEnum::MANY_TO_MANY:
					throw new Exception("WTF? This is a bug, please report it.");
				default:
					throw new Exception("The field type '$fieldType' is not supported.");
			}
		}
	}
}
