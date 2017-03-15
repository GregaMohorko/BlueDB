<?php

/*
 * FieldEntityHelper.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 15, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

use Exception;
use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Criteria\Expression;

abstract class FieldEntityHelper
{
	/**
	 * @param string $entityClass
	 * @param FieldEntity $entity
	 * @param array $oneToManyLists
	 */
	public static function loadOneToManyLists($entityClass,&$entity,$oneToManyLists)
	{
		/* @var $entityDTO FieldEntity */
		$entityDTO=new $entityClass();
		$entityDTO->setID($entity->getID());
		foreach($oneToManyLists as $oneToManyList){
			$oneToManyFieldName=$oneToManyList["Field"];
			$oneToManyClass=$oneToManyList["Class"];
			$identifier=$oneToManyList["Identifier"];
			$criteria=new Criteria($oneToManyClass);
			$criteria->add(Expression::equal($oneToManyClass, null, $identifier, $entityDTO));
			$list=$oneToManyClass::loadListByCriteria($criteria,null,[$identifier],true);
			foreach($list as $item)
				$item->$identifier=$entity;
			$entity->$oneToManyFieldName=$list;
		}
	}
	
	/**
	 * @param FieldEntity $entity
	 * @param array $manyToOneFieldsToLoad
	 */
	public static function loadManyToOneFields(&$entity,$manyToOneFieldsToLoad)
	{
		foreach($manyToOneFieldsToLoad as $manyToOneField){
			$manyToOneFieldName=$manyToOneField["Field"];
			$manyToOneClass=$manyToOneField["Class"];
			$foreignKey=$entity->$manyToOneFieldName;
			
			if($foreignKey==null)
				continue;
			
			$manyToOneEntity=$manyToOneClass::loadByID($foreignKey);
			$entity->$manyToOneFieldName=$manyToOneEntity;
		}
	}
	
	/**
	 * @param FieldEntity $entity
	 * @param array $fieldValues
	 * @param string $entityClass Class type of the entity. If not provided, the class is determined with get_class function.
	 */
	public static function setFieldValues(&$entity,$fieldValues,$entityClass=null)
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
