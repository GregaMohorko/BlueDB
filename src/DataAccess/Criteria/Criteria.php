<?php

/*
 * Criteria.php
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
	 * @var array A list of [field, bool] tuples, where the second item determines whether the order should be ascending OR [OrderByMultipleFieldOperator, fields[], bool].
	 */
	public $OrderingFields;
	/**
	 * @var array The [int, int] array representing the LIMIT clause, where the first item is the offset and the second is the count.
	 */
	public $Limit;
	
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
	
	/**
	 * @param string $field The field on which to order ascendingly.
	 * @return Criteria The same criteria, so that you can chain orderBy and thenBy clauses.
	 */
	public function orderBy($field)
	{
		if($this->OrderingFields!==null){
			throw new Exception('The orderBy has already been called. Call thenBy instead.');
		}
		$this->OrderingFields=[];
		$this->addOrdering($field, true);
		return $this;
	}
	
	/**
	 * Creates a ORDER BY (field1 OPERATOR field2 OPERATOR field3 ...).
	 * 
	 * @param OrderByMultipleFieldOperator $multipleFieldOperator The operator that will be put between all the fields.
	 * @param string[] $fields The fields.
	 * @return Criteria The same criteria, so that you can chain orderBy and thenBy clauses.
	 */
	public function orderByMultipleFields($multipleFieldOperator, $fields)
	{
		if($this->OrderingFields!==null){
			throw new Exception('The orderBy has already been called. Call thenBy instead.');
		}
		$this->OrderingFields=[];
		$this->addOrderingMultipleFields($multipleFieldOperator, $fields, true);
		return $this;
	}
	
	/**
	 * @param string $field The field on which to order descendingly.
	 * @return Criteria The same criteria, so that you can chain orderBy and thenBy clauses.
	 */
	public function orderByDescending($field)
	{
		if($this->OrderingFields!==null){
			throw new Exception('The orderBy has already been called. Call thenByDescending instead.');
		}
		$this->OrderingFields=[];
		$this->addOrdering($field, false);
		return $this;
	}
	
	/**
	 * Creates a ORDER BY (field1 OPERATOR field2 OPERATOR field3 ...) DESC.
	 * 
	 * @param OrderByMultipleFieldOperator $multipleFieldOperator The operator that will be put between all the fields.
	 * @param string[] $fields The fields.
	 * @return Criteria The same criteria, so that you can chain orderBy and thenBy clauses.
	 */
	public function orderByMultipleFieldsDescending($multipleFieldOperator, $fields)
	{
		if($this->OrderingFields!==null){
			throw new Exception('The orderBy has already been called. Call thenByDescending instead.');
		}
		$this->OrderingFields=[];
		$this->addOrderingMultipleFields($multipleFieldOperator, $fields, false);
		return $this;
	}
	
	/**
	 * @param string $field The field on which to order ascendingly.
	 * @return Criteria The same criteria, so that you can chain orderBy and thenBy clauses.
	 */
	public function thenBy($field)
	{
		if($this->OrderingFields===null){
			throw new Exception('The orderBy has not yet been called. Call orderBy instead.');
		}
		$this->addOrdering($field, true);
		return $this;
	}
	
	/**
	 * Creates a (field1 OPERATOR field2 OPERATOR field3 ...).
	 * 
	 * @param OrderByMultipleFieldOperator $multipleFieldOperator The operator that will be put between all the fields.
	 * @param string[] $fields The fields.
	 * @return Criteria The same criteria, so that you can chain orderBy and thenBy clauses.
	 */
	public function thenByMultipleFields($multipleFieldOperator, $fields)
	{
		if($this->OrderingFields===null){
			throw new Exception('The orderBy has not yet been called. Call orderBy instead.');
		}
		$this->addOrderingMultipleFields($multipleFieldOperator, $fields, true);
		return $this;
	}
	
	/**
	 * @param string $field The field on which to order descendingly.
	 * @return Criteria The same criteria, so that you can chain orderBy and thenBy clauses.
	 */
	public function thenByDescending($field)
	{
		if($this->OrderingFields===null){
			throw new Exception('The orderBy has not yet been called. Call orderByDescending instead.');
		}
		$this->addOrdering($field, false);
		return $this;
	}
	
	/**
	 * Creates a (field1 OPERATOR field2 OPERATOR field3 ...) DESC.
	 * 
	 * @param OrderByMultipleFieldOperator $multipleFieldOperator The operator that will be put between all the fields.
	 * @param string[] $fields The fields.
	 * @return Criteria The same criteria, so that you can chain orderBy and thenBy clauses.
	 */
	public function thenByMultipleFieldsDescending($multipleFieldOperator, $fields)
	{
		if($this->OrderingFields===null){
			throw new Exception('The orderBy has not yet been called. Call orderByDescending instead.');
		}
		$this->addOrderingMultipleFields($multipleFieldOperator, $fields, false);
		return $this;
	}
	
	/**
	 * @param string $field
	 * @param bool $ascending
	 */
	private function addOrdering($field,$ascending)
	{
		if($field===null){
			throw new Exception('Field for order by must not be null.');
		}
		$this->OrderingFields[]=[$field,$ascending];
	}
	
	/**
	 * @param OrderByMultipleFieldOperator $multipleFieldOperator
	 * @param string[] $fields
	 * @param bool $ascending
	 */
	private function addOrderingMultipleFields($multipleFieldOperator, $fields, $ascending)
	{
		switch($multipleFieldOperator){
			case OrderByMultipleFieldOperator::ANDD:
			case OrderByMultipleFieldOperator::ORR:
				break;
			default:
				throw new Exception('Unsupported multiple field operator: "'.$multipleFieldOperator.'".');
		}
		if($fields === null || empty($fields)){
			throw new Exception('The count of fields for order by multiple fields must not be empty or null.');
		}
		$this->OrderingFields[]=[$multipleFieldOperator, $fields, $ascending];
	}
	
	/**
	 * @param int $count Specifies the maximum number of rows to be returned.
	 * @param int $offset Specifies the offset of the first row to be returned.
	 */
	public function limit($count, $offset=0)
	{
		if($this->Limit!==null){
			throw new Exception('Limit was already set.');
		}
		$this->Limit=[$offset,$count];
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
								if(in_array($joinName, $alreadyIncludedJoins)){
									continue;
								}
								$alreadyIncludedJoins[]=$joinName;

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
		}
		
		// Where
		$queryConditions="";
		
		$parameters=[];
		$parameters[]="";
		
		$isFirst=true;
		foreach($this->expressions as $expression){
			/*@var $expression Expression*/

			if(!$isFirst){
				$queryConditions.=" AND ";
			} else{
				$queryConditions.="(";
				$isFirst=false;
			}
			
			$queryConditions.="(".$expression->Term.")";
			for($i=0;$i<$expression->ValueCount;$i++){
				$parameters[0].=$expression->ValueTypes[$i];
				$parameters[]=&$expression->Values[$i];
			}
		}
		
		if(!empty($queryConditions)){
			$queryConditions.=")";			
		}
		
		$this->PreparedQueryJoins=$queryJoins;
		$this->PreparedQueryRestrictions=$queryConditions;
		$this->PreparedParameters=$parameters;
	}
}
