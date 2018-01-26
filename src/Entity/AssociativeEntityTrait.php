<?php

/*
 * AssociativeEntity.php
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
}
