<?php

/*
 * Criteria.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\DataAccess\Criteria;

class Criteria
{
	/**
	 * @var string
	 */
	public $BaseEntityClass;
	
	/**
	 * @var array
	 */
	private $expressions;
	
	/**
	 * @var string
	 */
	public $PreparedQueryJoins;
	
	/**
	 * @var string
	 */
	public $PreparedQueryRestrictions;
	
	/**
	 * @var array Parameters for the prepared statement binding.
	 */
	public $PreparedParameters;
	
	/**
	 * @param string $baseEntityClass
	 */
	public function __construct($baseEntityClass)
	{
		$this->BaseEntityClass=$baseEntityClass;
		$this->expressions=[];
	}
	
	/**
	 * @param Mixed $expression Can be a single expression or an array of expressions
	 */
	public function add($expression)
	{
		if(is_array($expression)){
			$this->expressions=array_merge($this->expressions, $expression);
			return;
		}
		
		$this->expressions[]=$expression;
	}
	
	public function prepare()
	{
		// Joins
		$queryJoins="";
		$alreadyIncludedJoins=[];
		$isFirst=true;
		foreach($this->expressions as $expression){
			/*@var $expression Expression*/
			
			foreach($expression->Joins as $joiningEntityClass => $arrayByJoiningEntityClass){
				foreach($arrayByJoiningEntityClass as $joinType => $arrayByJoinType){
					foreach($arrayByJoinType as $joinBasePlace => $arrayByJoinBasePlace){
						foreach($arrayByJoinBasePlace as $joinBaseColumn => $arrayByJoinBaseColumn){
							foreach($arrayByJoinBaseColumn as $joinColumn => $joinName){
								if(in_array($joinName, $alreadyIncludedJoins))
									continue;
								$alreadyIncludedJoins[]=$joinName;

								$joinTable=$joiningEntityClass::getTableName();

								if(!$isFirst)
									$queryJoins.=" ";
								else
									$isFirst=false;

								$queryJoins.="$joinType JOIN $joinTable AS $joinName ON $joinBasePlace.$joinBaseColumn=$joinName.$joinColumn";
							}
						}
					}
				}
			}
		}
		
		// Where
		$queryConditions="";
		
		$parameters=[];
		$parameters[]="";
		
		$isFirst=true;
		foreach($this->expressions as $expression){
			/*@var $expression Expression*/

			if(!$isFirst)
				$queryConditions.=" AND ";
			else{
				$queryConditions.="(";
				$isFirst=false;
			}
			
			$queryConditions.="(".$expression->Term.")";
			for($i=0;$i<$expression->ValueCount;$i++){
				$parameters[0].=$expression->ValueTypes[$i];
				$parameters[]=&$expression->Values[$i];
			}
		}
		
		if(!empty($queryConditions))
			$queryConditions.=")";
		
		$this->PreparedQueryJoins=$queryJoins;
		$this->PreparedQueryRestrictions=$queryConditions;
		$this->PreparedParameters=$parameters;
	}
}
