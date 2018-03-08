<?php

/*
 * JoinHelper.php
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
 * @copyright Apr 25, 2017 Grega Mohorko
 */

namespace BlueDB\DataAccess;

/**
 * Manages joins.
 */
abstract class Joiner
{
	/**
	 * @var int
	 */
	private static $_joinNameCounter=0;
	
	/**
	 * @var array 5D: JoiningEntityClass -> JoinType -> JoinBasePlace -> JoinBaseColumn -> JoinColumn = JoinName
	 */
	private static $_joinNames=[];
	
	/**
	 * Gets (or creates, if it doesn't yet exist) the name for the join with the specified parameters.
	 * 
	 * @param string $joiningEntityClass Entity class that is joining.
	 * @param string $joinType Type of the join.
	 * @param string $joinBasePlace Place of the base joining. Can be a table (from FROM) or a previously created join.
	 * @param string $joinBaseColumn Column of the joinBasePlace on which the join shall be made.
	 * @param string $joinColumn Column of the joining entity on which the join shall be made.
	 * @return string
	 */
	public static function getJoinName($joiningEntityClass,$joinType,$joinBasePlace,$joinBaseColumn,$joinColumn)
	{
		if(isset(self::$_joinNames[$joiningEntityClass])){
			$arrayByClass=&self::$_joinNames[$joiningEntityClass];
		}else{
			$arrayByClass=[];
			self::$_joinNames[$joiningEntityClass]=&$arrayByClass;
		}
		
		if(isset($arrayByClass[$joinType])){
			$arrayByJoinType=&$arrayByClass[$joinType];
		}else{
			$arrayByJoinType=[];
			$arrayByClass[$joinType]=&$arrayByJoinType;
		}
		
		if(isset($arrayByJoinType[$joinBasePlace])){
			$arrayByJoinBasePlace=&$arrayByJoinType[$joinBasePlace];
		}else{
			$arrayByJoinBasePlace=[];
			$arrayByJoinType[$joinBasePlace]=&$arrayByJoinBasePlace;
		}
		
		if(isset($arrayByJoinBasePlace[$joinBaseColumn])){
			$arrayByJoinBaseColumn=&$arrayByJoinBasePlace[$joinBaseColumn];
		}else{
			$arrayByJoinBaseColumn=[];
			$arrayByJoinBasePlace[$joinBaseColumn]=&$arrayByJoinBaseColumn;
		}
		
		if(isset($arrayByJoinBaseColumn[$joinColumn])){
			return $arrayByJoinBaseColumn[$joinColumn];
		}
		
		++self::$_joinNameCounter;
		$joinName="J".self::$_joinNameCounter;
		$arrayByJoinBaseColumn[$joinColumn]=$joinName;

		return $joinName;
	}
	
	/**
	 * Creates a join out of the specified values.
	 * 
	 * @param string $class Entity class that is joining.
	 * @param JoinType $joinType Type of the join.
	 * @param string $joinBasePlace Place of the base joining. Can be a table (from FROM) or a previously created join.
	 * @param string $joinBaseColumn Column of the joinBasePlace on which the join shall be made.
	 * @param string $joinColumn Column of the joining entity on which the join shall be made.
	 * @param string [optional] $joinName
	 * @return array
	 */
	public static function createJoin($class,$joinType,$joinBasePlace,$joinBaseColumn,$joinColumn,$joinName=null)
	{
		if($joinName==null){
			$joinName=self::getJoinName($class, $joinType, $joinBasePlace, $joinBaseColumn, $joinColumn);
		}
		
		$theJoin=[];
		$theJoin[$class]=self::createJoinArray($joinType, $joinBasePlace, $joinBaseColumn, $joinColumn, $joinName);
		
		return $theJoin;
	}
	
	/**
	 * Creates a join array out of the specified values.
	 * 
	 * @param JoinType $joinType
	 * @param string $joinBasePlace
	 * @param string $joinBaseColumn
	 * @param string $joinColumn
	 * @param string $joinName
	 * @return array
	 */
	public static function createJoinArray($joinType,$joinBasePlace,$joinBaseColumn,$joinColumn,$joinName)
	{
		$type_BasePlace_BaseColumn=[];
		$type_BasePlace_BaseColumn[$joinColumn]=$joinName;
		$type_BasePlace=[];
		$type_BasePlace[$joinBaseColumn]=$type_BasePlace_BaseColumn;
		$typeJoin=[];
		$typeJoin[$joinBasePlace]=$type_BasePlace;
		$joinArray=[];
		$joinArray[$joinType]=$typeJoin;
		
		return $joinArray;
	}
	
	/**
	 * Converts the specified join to a query string.
	 * 
	 * @param array $theJoin
	 * @return string
	 */
	public static function toQueryString($theJoin)
	{
		$queryJoins="";
		
		$isFirst=true;
		foreach($theJoin as $joiningEntityClass => $arrayByJoiningEntityClass){
			foreach($arrayByJoiningEntityClass as $joinType => $arrayByJoinType){
				foreach($arrayByJoinType as $joinBasePlace => $arrayByJoinBasePlace){
					foreach($arrayByJoinBasePlace as $joinBaseColumn => $arrayByJoinBaseColumn){
						foreach($arrayByJoinBaseColumn as $joinColumn => $joinName){
							$joinTable=$joiningEntityClass::getTableName();

							if(!$isFirst){
								$queryJoins.=" ";
							} else{
								$isFirst=false;
							}

							$queryJoins.="$joinType JOIN $joinTable AS $joinName ON $joinBasePlace.$joinBaseColumn=$joinName.$joinColumn";
						}
					}
				}
			}
		}
		
		return $queryJoins;
	}
}
