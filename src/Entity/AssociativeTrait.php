<?php

/*
 * AssociativeTrait.php
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
 * @copyright Apr 29, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

use Exception;
use BlueDB\DataAccess\Session;
use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Criteria\Expression;

trait AssociativeTrait
{
	/**
	 * Returns the opposite side of the specified side.
	 * 
	 * @param string $side
	 * @return string
	 */
	public static function getOppositeSide($side)
	{
		$calledClass=get_called_class();
		$sideA=$calledClass::getSideA();
		$sideB=$calledClass::getSideB();
		if($side===$sideA){
			return $sideB;
		}
		if($side===$sideB){
			return $sideA;
		}
		throw new Exception("The specified side '$side' does not exist in associative entity '$calledClass'.");
	}
	
	/**
	 * Loads a list of entities for the provided origin side.
	 * 
	 * For example: If origin side A is provided, objects of type B will be loaded. And vice versa.
	 * 
	 * @param string $originSide
	 * @param int $ID
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclManyToOne [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return array
	 */
	public static function loadListForSide($originSide, $ID, $fields=null, $fieldsToIgnore=null, $inclManyToOne=null, $inclOneToMany=null, $inclManyToMany=null)
	{
		self::checkConfig($inclManyToOne,$inclOneToMany,$inclManyToMany);
		$calledClass=get_called_class();
		$session=new Session();
		return $calledClass::loadListForSideInternal($originSide,$ID,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session);
	}
	
	/**
	 * @param string $originSide
	 * @param int $ID
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclManyToOne
	 * @param bool $inclOneToMany
	 * @param bool $inclManyToMany
	 * @param Session $session
	 * @return array
	 */
	protected static function loadListForSideInternal($originSide,$ID,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session)
	{
		$calledClass=get_called_class();
		$criteria=new Criteria($calledClass);
		return $calledClass::loadListForSideByCriteriaInternal($originSide,$ID,$criteria,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session);
	}

	/**
	 * Loads a list of entities by criteria for the provided origin side.
	 * 
	 * For example: If origin side A is provided, objects of type B will be loaded. And vice versa.
	 * 
	 * @param string $originSide
	 * @param int $ID
	 * @param Criteria $criteria
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclManyToOne [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return array
	 */
	public static function loadListForSideByCriteria($originSide, $ID, $criteria, $fields=null, $fieldsToIgnore=null, $inclManyToOne=null, $inclOneToMany=null, $inclManyToMany=null)
	{
		self::checkConfig($inclManyToOne,$inclOneToMany,$inclManyToMany);
		$calledClass=get_called_class();
		$session=new Session();
		return $calledClass::loadListForSideByCriteriaInternal($originSide,$ID,$criteria,$fields,$fieldsToIgnore,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session);
	}
	
	/**
	 * @param string $originSide
	 * @param int $ID
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclManyToOne
	 * @param bool $inclOneToMany
	 * @param bool $inclManyToMany
	 * @param Session $session
	 * @return array
	 */
	protected static function loadListForSideByCriteriaInternal($originSide, $ID, $criteria, $fields, $fieldsToIgnore, $inclManyToOne, $inclOneToMany, $inclManyToMany, $session)
	{
		$calledClass=get_called_class();
		
		$sideToLoad=$calledClass::getOppositeSide($originSide);
		
		$toLoadBaseConstName="$calledClass::$sideToLoad";
		$toLoadClass=constant($toLoadBaseConstName."Class");
		$joinColumn=constant($toLoadBaseConstName."Column");
		
		$originColumn=constant("$calledClass::$originSide"."Column");
		$baseEntityTableName=$calledClass::getTableName();
		$criteria->add(Expression::custom($calledClass,"$baseEntityTableName.$originColumn=?",[[$ID, PropertyTypeEnum::INT]]));
		
		$parentClass=null;
		$parentFieldName=null;
		$isSubEntity=is_subclass_of($toLoadClass, SubEntity::class);
		if($isSubEntity){
			$parentClass=$toLoadClass::getParentEntityClass();
			$parentFieldName=$toLoadClass::getParentFieldName();
		}
		
		$manyToOneFieldsToLoad=null;
		$oneToManyListsToLoad=null;
		$manyToManyListsToLoad=null;
		$fieldsOfParent=null;
		$query=self::prepareSelectQuery($calledClass,$toLoadClass,$joinColumn,$criteria,$fields,$fieldsToIgnore,$manyToOneFieldsToLoad,$inclManyToOne,$inclOneToMany,$oneToManyListsToLoad,$inclManyToMany,$manyToManyListsToLoad,$isSubEntity,$parentFieldName,$fieldsOfParent);
		
		$loadedArrays=self::executeSelectQuery($query,$criteria);
		if(empty($loadedArrays)){
			return [];
		}
		
		$loadedEntities=[];
		
		foreach($loadedArrays as $array){
			$loadedEntities[]=self::createInstance($toLoadClass,$array,$manyToOneFieldsToLoad,$oneToManyListsToLoad, $manyToManyListsToLoad,$inclManyToOne,$inclOneToMany,$inclManyToMany,$session,$isSubEntity,$parentClass,$parentFieldName,$fieldsOfParent,$fieldsToIgnore);
		}
		
		return $loadedEntities;
	}
}
