<?php

/*
 * EntityUtility.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright May 23, 2017 Grega Mohorko
 */

namespace BlueDB\Utility;

use Exception;
use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Criteria\Expression;
use BlueDB\Entity\FieldEntity;
use BlueDB\Entity\FieldTypeEnum;
use BlueDB\Entity\PropertyComparer;

abstract class EntityUtility
{
	/**
	 * Determines whether both entities are equal. They must be of the same type. This function searches in depth to the end, so it can be slow.
	 * 
	 * @param FieldEntity $entity1
	 * @param FieldEntity $entity2
	 * @return bool
	 */
	public static function areEqual($entity1,$entity2)
	{
		$session=[];
		return self::areEqualInternal($entity1, $entity2, $session);
	}
	
	/**
	 * @param FieldEntity $entity1
	 * @param FieldEntity $entity2
	 * @param array $session An array of already compared pairs of entities, grouped by class.
	 * @return bool
	 */
	private static function areEqualInternal($entity1,$entity2,&$session)
	{
		if($entity1===null && $entity2===null){
			return true;
		}
		if($entity1===null || $entity2===null){
			return false;
		}
		if($entity1===$entity2){
			// the same reference
			return true;
		}
		$class1=get_class($entity1);
		$class2=get_class($entity2);
		if($class1 !== $class2){
			return false;
		}
		
		// check if they were already compared
		if(isset($session[$class1])){
			foreach($session[$class1] as $pair){
				if(($pair[0]===$entity1 && $pair[1]===$entity2) ||
					($pair[0]===$entity2 && $pair[1]===$entity1)){
					// found the pair
					return true;
				}
			}
		}else{
			$session[$class1]=[];
		}
		
		// add the pair to the session
		$thisPair=[];
		$thisPair[]=$entity1;
		$thisPair[]=$entity2;
		$session[$class1][]=$thisPair;
		
		$fieldList=$class1::getFieldList();
		
		foreach($fieldList as $field){
			$value1=$entity1->$field;
			$value2=$entity2->$field;
			if($value1===null && $value2===null){
				continue;
			}
			if($value1===null || $value2===null){
				return false;
			}
			
			$fieldBaseConstName="$class1::$field";
			$fieldType=constant($fieldBaseConstName."FieldType");
			switch($fieldType){
				case FieldTypeEnum::PROPERTY:
					$propertyType=constant($fieldBaseConstName."PropertyType");
					if(!PropertyComparer::compare($value1, $value2, $propertyType)){
						return false;
					}
					break;
				case FieldTypeEnum::MANY_TO_ONE:
					if(!self::areEqualInternal($value1, $value2,$session)){
						return false;
					}
					break;
				case FieldTypeEnum::ONE_TO_MANY:
				case FieldTypeEnum::MANY_TO_MANY:
					$count1=count($value1);
					if($count1!==count($value2)){
						return false;
					}
					for($i=$count1-1;$i>=0;--$i){
						if(!self::areEqualInternal($value1[$i], $value2[$i],$session)){
							return false;
						}
					}
					break;
				default:
					throw new Exception("Unsupported field type '$fieldType'.");
			}
		}
		
		return true;
	}
	
	/**
	 * Creates and returns an object of the same type as the provided entity and with the same ID.
	 * 
	 * @param FieldEntity $entity
	 * @return FieldEntity
	 */
	public static function createDTO($entity)
	{
		$entityType= get_class($entity);
		/* @var $dto FieldEntity */
		$dto=$entityType::createEmpty();
		$dto->setID($entity->getID());
		return $dto;
	}
	
	/**
	 * Loads the specified field of the provided entity and returns it. This function does not modify the provided entity. Field type must either be ONE_TO_MANY or MANY_TO_MANY.
	 * 
	 * @param FieldEntity $entity
	 * @param string $field Must either be ONE_TO_MANY or MANY_TO_MANY.
	 * @param array $fieldsToLoad [optional] Specifies which fields to load.
	 * @param array $fieldsToIgnore [optional]
	 * @param array $additionalExpressions [optional] Any additional expressions to filter the loaded entities. Can be a single expression or a list of expressions.
	 * @return array
	 */
	public static function loadFieldOf($entity,$field,$fieldsToLoad=null,$fieldsToIgnore=null,$additionalExpressions=null)
	{
		$dto=self::createDTO($entity);
		self::loadFieldInternal($dto, $field, $fieldsToLoad, $fieldsToIgnore, $additionalExpressions,false);
		return $dto->$field;
	}
	
	/**
	 * Loads the specified field of the provided entity and returns it. Use this function when you expect to get only one result. This function does not modify the provided entity. Field type must be ONE_TO_MANY.
	 * 
	 * @param FieldEntity $entity
	 * @param string $field Must be ONE_TO_MANY.
	 * @param array $fieldsToLoad [optional] Specifies which fields to load.
	 * @param array $fieldsToIgnore [optional]
	 * @param array $additionalExpressions [optional] Any additional expressions to filter the loaded entities. Can be a single expression or a list of expressions.
	 * @return FieldEntity
	 */
	public static function loadFieldOfSingle($entity,$field,$fieldsToLoad=null,$fieldsToIgnore=null,$additionalExpressions=null)
	{
		$dto=self::createDTO($entity);
		self::loadFieldInternal($dto, $field, $fieldsToLoad, $fieldsToIgnore, $additionalExpressions,true);
		return $dto->$field;
	}
	
	/**
	 * Loads the specified field of the provided entity. Field type must either be ONE_TO_MANY or MANY_TO_MANY.
	 * 
	 * @param FieldEntity $entity
	 * @param string $field Must either be ONE_TO_MANY or MANY_TO_MANY.
	 * @param array $fieldsToLoad [optional] Specifies which fields to load.
	 * @param array $fieldsToIgnore [optional]
	 * @param array $additionalExpressions [optional] Any additional expressions to filter the loaded entities. Can be a single expression or a list of expressions.
	 */
	public static function loadField($entity,$field,$fieldsToLoad=null,$fieldsToIgnore=null,$additionalExpressions=null)
	{
		self::loadFieldInternal($entity, $field, $fieldsToLoad, $fieldsToIgnore, $additionalExpressions, false);
	}
	
	/**
	 * Loads the specified field of the provided entity. Use this function when you expect to get only one result. Field type must be ONE_TO_MANY.
	 * 
	 * @param FieldEntity $entity
	 * @param string $field Must be ONE_TO_MANY.
	 * @param array $fieldsToLoad [optional] Specifies which fields to load.
	 * @param array $fieldsToIgnore [optional]
	 * @param array $additionalExpressions [optional] Any additional expressions to filter the loaded entities. Can be a single expression or a list of expressions.
	 */
	public static function loadFieldSingle($entity,$field,$fieldsToLoad=null,$fieldsToIgnore=null,$additionalExpressions=null)
	{
		self::loadFieldInternal($entity, $field, $fieldsToLoad, $fieldsToIgnore, $additionalExpressions, true);
	}
	
	/**
	 * Loads the specified field of the provided entity. Field type must either be ONE_TO_MANY or MANY_TO_MANY.
	 * 
	 * @param FieldEntity $entity
	 * @param string $field Must either be ONE_TO_MANY or MANY_TO_MANY.
	 * @param array $fieldsToLoad [optional] Specifies which fields to load.
	 * @param array $fieldsToIgnore [optional]
	 * @param array $additionalExpressions [optional] Any additional expressions to filter the loaded entities. Can be a single expression or a list of expressions.
	 * @param bool $expectsSingle
	 */
	private static function loadFieldInternal($entity,$field,$fieldsToLoad,$fieldsToIgnore,$additionalExpressions,$expectsSingle)
	{
		$entityClass= get_class($entity);
		$fieldBaseConstName="$entityClass::$field";
		$fieldType=constant($fieldBaseConstName."FieldType");
		switch($fieldType){
			case FieldTypeEnum::ONE_TO_MANY:
				$fieldClass=constant($fieldBaseConstName."Class");
				$fieldIdentifier=constant($fieldBaseConstName."Identifier");
				$entityDTO=self::createDTO($entity);
				$criteria=new Criteria($fieldClass);
				$criteria->add(Expression::equal($fieldClass, $fieldIdentifier, $entityDTO));
				if($additionalExpressions!==null){
					$criteria->add($additionalExpressions);
				}
				if($expectsSingle){
					$loadedField=$fieldClass::loadByCriteria($criteria,$fieldsToLoad,$fieldsToIgnore);
				}else{
					$loadedField=$fieldClass::loadListByCriteria($criteria,$fieldsToLoad,$fieldsToIgnore);
				}
				break;
			case FieldTypeEnum::MANY_TO_MANY:
				if($expectsSingle){
					throw new Exception("Loading single entities is only allowed for ONE_TO_MANY field types");
				}
				$fieldClass=constant($fieldBaseConstName."Class");
				$fieldSide=constant($fieldBaseConstName."Side");
				if($additionalExpressions===null){
					$loadedField=$fieldClass::loadListForSide($fieldSide,$entity->getID(),$fieldsToLoad,$fieldsToIgnore);
				}else{
					$loadedEntitiesClass=constant("$fieldClass::$fieldSide"."Class");
					$criteria=new Criteria($loadedEntitiesClass);
					$criteria->add($additionalExpressions);
					$loadedField=$fieldClass::loadListForSideByCriteria($fieldSide,$entity->getID(),$criteria,$fieldsToLoad,$fieldsToIgnore);
				}
				break;
			default:
				throw new Exception("Only ONE_TO_MANY and MANY_TO_MANY field types are allowed.");
		}
		
		$entity->$field=$loadedField;
	}
	
	/**
	 * Loads the specified fields of the provided entity. This function is especially useful for ONE_TO_MANY and MANY_TO_MANY field types.
	 * 
	 * @param FieldEntity $entity
	 * @param array $fields
	 */
	public static function loadFields($entity,$fields)
	{
		$entityClass= get_class($entity);
		
		/* @var $entityWithLoadedFields FieldEntity */
		$entityWithLoadedFields=$entityClass::loadByID($entity->getID(),$fields);
		
		foreach($fields as $field){
			$entity->$field=$entityWithLoadedFields->$field;
		}
	}
}
