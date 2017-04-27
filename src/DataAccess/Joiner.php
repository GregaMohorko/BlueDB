<?php

/*
 * JoinHelper.php
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
	 * @param string $joiningEntityClass Entity class that is joining.
	 * @param string $joinType Type of the join.
	 * @param string $joinBasePlace Place of the base joining. Can be a table (from FROM) or a previously created join.
	 * @param string $joinBaseColumn Column of the joinBasePlace on which the join shall be made.
	 * @param string $joinColumn Column of the joining entity on which the join shall be made.
	 * @return string
	 */
	public static function getJoinName($joiningEntityClass,$joinType,$joinBasePlace,$joinBaseColumn,$joinColumn)
	{
		if(!isset(self::$_joinNames[$joiningEntityClass]))
			self::$_joinNames[$joiningEntityClass]=[];
		/*#var $arrayByClass array*/
		$arrayByClass=self::$_joinNames[$joiningEntityClass];
		
		if(!isset($arrayByClass[$joinType]))
			$arrayByClass[$joinType]=[];
		/*@var $arrayByJoinType array*/
		$arrayByJoinType=$arrayByClass[$joinType];
		
		if(!isset($arrayByJoinType[$joinBasePlace]))
			$arrayByJoinType[$joinBasePlace]=[];
		/*@var $arrayByJoinBasePlace array*/
		$arrayByJoinBasePlace=$arrayByJoinType[$joinBasePlace];
		
		if(!isset($arrayByJoinBasePlace[$joinBaseColumn]))
			$arrayByJoinBasePlace[$joinBaseColumn]=[];
		/*@var $arrayByJoinBaseColumn array*/
		$arrayByJoinBaseColumn=$arrayByJoinBasePlace[$joinBaseColumn];
		
		if(!isset($arrayByJoinBaseColumn[$joinColumn])){
			self::$_joinNameCounter++;
			$arrayByJoinBaseColumn[$joinColumn]="J".self::$_joinNameCounter;
			
			// has to change values, arrays do not update automatically ... (which is stupid from PHP)
			$arrayByJoinBasePlace[$joinBaseColumn]=$arrayByJoinBaseColumn;
			$arrayByJoinType[$joinBasePlace]=$arrayByJoinBasePlace;
			$arrayByClass[$joinType]=$arrayByJoinType;
			self::$_joinNames[$joiningEntityClass]=$arrayByClass;
		}
		
		$joinName=$arrayByJoinBaseColumn[$joinColumn];
		
		return $joinName;
	}
	
	/**
	 * Creates a join out of the specified values.
	 * 
	 * @param string $class
	 * @param JoinType $joinType
	 * @param string $joinBasePlace
	 * @param string $joinBaseColumn
	 * @param string $joinColumn
	 * @param string $joinName
	 * @return array
	 */
	public static function createJoin($class,$joinType,$joinBasePlace,$joinBaseColumn,$joinColumn,$joinName)
	{
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
}
