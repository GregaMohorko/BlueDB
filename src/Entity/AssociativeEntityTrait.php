<?php

/*
 * AssociativeEntity.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 26, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Criteria\Expression;

trait AssociativeEntityTrait
{
	use AssociativeTrait;
	
	/**
	 * Loads the entity that has the provided A and B object.
	 * 
	 * @param IFieldEntity $AObject
	 * @param IFieldEntity $BObject
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclManyToOne [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return IFieldEntity
	 */
	public static function loadFor($AObject,$BObject,$fields=null,$fieldsToIgnore=null,$inclManyToOne=null,$inclOneToMany=null,$inclManyToMany=null)
	{
		$calledClass=get_called_class();
		$criteria=new Criteria($calledClass);
		return $calledClass::loadForByCriteria($AObject,$BObject,$criteria,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany);
	}
	
	/**
	 * Loads the entity that has the provided A and B object and satisfies the provided criteria.
	 * 
	 * @param IFieldEntity $AObject
	 * @param IFieldEntity $BObject
	 * @param $criteria
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclManyToOne [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return IFieldEntity
	 */
	public static function loadForByCriteria($AObject,$BObject,$criteria,$fields=null,$fieldsToIgnore=null,$inclManyToOne=null,$inclOneToMany=null,$inclManyToMany=null)
	{
		$calledClass=get_called_class();
		self::addExpressions($calledClass,$criteria,$AObject,$BObject);
		return $calledClass::loadByCriteria($criteria,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany);
	}
	
	/**
	 * Loads the entities that has the provided A and B object.
	 * 
	 * @param IFieldEntity $AObject
	 * @param IFieldEntity $BObject
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclManyToOne [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return array
	 */
	public static function loadListFor($AObject,$BObject,$fields=null,$fieldsToIgnore=null,$inclManyToOne=null,$inclOneToMany=null,$inclManyToMany=null)
	{
		$calledClass=get_called_class();
		$criteria=new Criteria($calledClass);
		return $calledClass::loadListForByCriteria($AObject,$BObject,$criteria,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany);
	}
	
	/**
	 * Loads the entities that has the provided A and B object and satisfy the provided criteria.
	 * 
	 * @param IFieldEntity $AObject
	 * @param IFieldEntity $BObject
	 * @param $criteria
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclManyToOne [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return array
	 */
	public static function loadListForByCriteria($AObject,$BObject,$criteria,$fields=null,$fieldsToIgnore=null,$inclManyToOne=null,$inclOneToMany=null,$inclManyToMany=null)
	{
		$calledClass=get_called_class();
		self::addExpressions($calledClass,$criteria,$AObject,$BObject);
		return $calledClass::loadListByCriteria($criteria,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany);
	}
	
	/**
	 * Adds the expressions for loading values between the provided two objects.
	 * 
	 * @param string $calledClass
	 * @param Criteria $criteria
	 * @param IFieldEntity $AObject
	 * @param IFieldEntity $BObject
	 */
	private static function addExpressions($calledClass,$criteria,$AObject,$BObject)
	{
		$tableName=$calledClass::getTableName();
		$sideA=$calledClass::getSideA();
		$sideB=$calledClass::getSideB();
		$columnA=constant("$calledClass::$sideA"."Column");
		$columnB=constant("$calledClass::$sideB"."Column");
		
		$criteria->add(Expression::custom($calledClass, "$tableName.$columnA=?", [[$AObject->getID(), PropertyTypeEnum::INT]]));
		$criteria->add(Expression::custom($calledClass, "$tableName.$columnB=?", [[$BObject->getID(), PropertyTypeEnum::INT]]));
	}
	
	/**
	 * Unlinks two objects.
	 * 
	 * @param IFieldEntity $AObject
	 * @param IFieldEntity $BObject
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	public static function unlink($AObject,$BObject,$beginTransaction=true,$commit=true)
	{
		$calledClass=get_called_class();
		
		self::unlinkInternal($calledClass,$calledClass::getSideA(),$AObject,[$BObject],$beginTransaction,$commit);
	}
	
	/**
	 * Unlinks a B object with multiple A objects.
	 * 
	 * @param IFieldEntity $BObject
	 * @param array $AList
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	public static function unlinkMultipleA($BObject,$AList,$beginTransaction=true,$commit=true)
	{
		$calledClass=get_called_class();
		
		self::unlinkInternal($calledClass,$calledClass::getSideB(),$BObject,$AList,$beginTransaction,$commit);
	}
	
	/**
	 * Unlinks an A object with multiple B objects.
	 * 
	 * @param IFieldEntity $AObject
	 * @param array $BList
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	public static function unlinkMultipleB($AObject,$BList,$beginTransaction=true,$commit=true)
	{
		$calledClass=get_called_class();
		
		self::unlinkInternal($calledClass,$calledClass::getSideA(),$AObject,$BList,$beginTransaction,$commit);
	}
	
	/**
	 * @param string $calledClass
	 * @param string $objectSide
	 * @param IFieldEntity $object
	 * @param array $list
	 * @param bool $beginTransaction
	 * @param bool $commit
	 */
	private static function unlinkInternal($calledClass,$objectSide,$object,$list,$beginTransaction,$commit)
	{
		$tableName=$calledClass::getTableName();
		$oppositeSide=$calledClass::getOppositeSide($objectSide);
		$objectColumn=constant("$calledClass::$objectSide"."Column");
		$oppositeColumn=constant("$calledClass::$oppositeSide"."Column");
		
		// create criteria
		$criteria=new Criteria($calledClass);
		$or=[];
		foreach($list as $listObject) {
			/* @var $listObject IFieldEntity */
			$or[]=Expression::custom($calledClass, "$tableName.$oppositeColumn=?", [[$listObject->getID(), PropertyTypeEnum::INT]]);
		}
		if(count($list)>1){
			$criteria->add(Expression::any($or));
		}else{
			$criteria->add($or);
		}
		$criteria->add(Expression::custom($calledClass, "$tableName.$objectColumn=?", [[$object->getID(),  PropertyTypeEnum::INT]]));
		
		$entitiesToDelete=$calledClass::loadListByCriteria($criteria,[]);
		$calledClass::deleteList($entitiesToDelete,$beginTransaction,$commit);
	}
}
