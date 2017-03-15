<?php

/*
 * Expression.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\DataAccess\Criteria;

use Exception;
use DateTime;
use BlueDB\Entity\FieldTypeEnum;
use BlueDB\Entity\PropertyTypeEnum;

class Expression
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
	private static function getJoinName($joiningEntityClass,$joinType,$joinBasePlace,$joinBaseColumn,$joinColumn)
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
	 * @var string
	 */
	public $EntityClass;
	
	/**
	 * @var array 4D: JoiningEntityClass -> JoinType -> JoinBasePlace -> JoinBaseColumn -> JoinColumn = JoinName
	 */
	public $Joins;
	
	/**
	 * @var string
	 */
	public $Term;
	
	/**
	 * @var array
	 */
	public $Values;
	
	/**
	 * @var array This is used for parameter binding in Prepared Statements.
	 */
	public $ValueTypes;
	
	/**
	 * @var int
	 */
	public $ValueCount;
	
	/**
	 * @param string $entityClass
	 * @param array $joins
	 * @param string $term
	 * @param array $values
	 * @param array $valueTypes
	 */
	private function __construct($entityClass,$joins,$term,$values=null,$valueTypes=null)
	{
		$this->EntityClass=$entityClass;
		if($joins!=null)
			$this->Joins=$joins;
		else
			$this->Joins=[];
		$this->Term=$term;
		if($values==null || $valueTypes==null){
			$this->Values=[];
			$this->ValueTypes=[];
			$this->ValueCount=0;
		}else{
			$this->Values=$values;
			$this->ValueTypes=$valueTypes;
			$this->ValueCount=count($this->Values);
		}
	}
	
	/**
	 * Selects only those entries whose field value is above the provided one. Can be used for int, float, date, time and datetime.
	 * 
	 * @param string $criteriaClass Class of the base entity, on which the criteria will be put.
	 * @param string $field Field (of the restriction object), on which the restriction shall take place.
	 * @param mixed $value Inclusive bottom value.
	 * @param string $subEntityClass Class of the sub entity object, that will be joined if needed.
	 * @return Expression
	 */
	public static function above($criteriaClass,$field,$value,$subEntityClass=null)
	{
		return self::abovePrivate($criteriaClass, $field, $value, true, $subEntityClass);
	}
	
	private static function abovePrivate($criteriaClass,$field,$value,$hasToPrepareStatement,$subEntityClass)
	{
		if($subEntityClass===null)
			$subEntityClass=$criteriaClass;
		
		$joiningFieldBaseConstName=$subEntityClass."::".$field;
		$fieldType=constant($joiningFieldBaseConstName."FieldType");
		if($fieldType!=FieldTypeEnum::PROPERTY)
			throw new Exception("Only PROPERTY field types are allowed for after expressions.");
		
		$propertyType=constant($joiningFieldBaseConstName."PropertyType");
		switch($propertyType){
			case PropertyTypeEnum::INT:
			case PropertyTypeEnum::FLOAT:
			case PropertyTypeEnum::DATETIME:
			case PropertyTypeEnum::DATE:
			case PropertyTypeEnum::TIME:
				break;
			default:
				throw new Exception("Property type '".$propertyType."' is not supported for after expression.");
		}
		
		$valueS=PropertyTypeEnum::convertToString($value, $propertyType);
		
		if($criteriaClass==$subEntityClass){
			$termName=$subEntityClass::getTableName();
			$theJoin=null;
		}else{
			$joinBasePlace=$criteriaClass::getTableName();
			$joinBaseColumn=$criteriaClass::getIDColumn();
			$joinColumn=$subEntityClass::getIDColumn();
			
			$joinName=self::getJoinName($subEntityClass, JoinType::INNER, $joinBasePlace, $joinBaseColumn, $joinColumn);
			$termName=$joinName;
			$theJoin=[];
			$theJoin[$subEntityClass]=[];
			$theJoin[$subEntityClass][JoinType::INNER]=[];
			$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace]=[];
			$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace][$joinBaseColumn]=[];
			$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace][$joinBaseColumn][$joinColumn]=$joinName;
		}
		
		$term=$termName.".".$field." > ";
		
		if($hasToPrepareStatement){
			$term.="?";
			
			$valueType=PropertyTypeEnum::getPreparedStmtType($propertyType);
			
			$values=[$valueS];
			$valueTypes=[$valueType];
		}else{
			$term.="'".$valueS."'";
			$values=[];
			$valueTypes=[];
		}
		
		return new Expression($criteriaClass, $theJoin, $term, $values, $valueTypes);
	}
	
	/**
	 * Selects only those entries whose DateTime field value is after the current date & time.
	 * 
	 * @param string $criteriaClass Class of the base entity, on which the criteria will be put.
	 * @param string $field Field (of the restriction object), on which the restriction shall take place.
	 * @param string $subEntityClass Class of the sub entity object, that will be joined if needed.
	 * @return Expression
	 */
	public static function afterNow($criteriaClass,$field,$subEntityClass=null)
	{
		$dateTimeValue=new DateTime();
		return self::abovePrivate($criteriaClass,$field,$dateTimeValue,false,$subEntityClass);
	}
	
	/**
	 * Selects only those entries whose DateTime field value is after the provided DateTime value.
	 * 
	 * @param string $criteriaClass Class of the base entity, on which the criteria will be put.
	 * @param string $field Field (of the restriction object), on which the restriction shall take place.
	 * @param type $dateTimeValue
	 * @param string $subEntityClass Class of the sub entity object, that will be joined if needed.
	 * @return Expression
	 */
	public static function after($criteriaClass,$field,$dateTimeValue,$subEntityClass=null)
	{
		return self::abovePrivate($criteriaClass, $field, $dateTimeValue, true,$subEntityClass);
	}
	
	/**
	 * Used for int,float,date,time and datetime properties.
	 * 
	 * @param string $criteriaClass Class of the base entity, on which the criteria will be put.
	 * @param string $field Field (of the restriction object), on which the restriction shall take place.
	 * @param mixed $min Inclusive min value.
	 * @param mixed $max Inclusive max value.
	 * @param string $subEntityClass Class of the sub entity object, that will be joined if needed.
	 * @return Expression
	 */
	public static function between($criteriaClass,$field,$min,$max,$subEntityClass=null)
	{
		if($subEntityClass===null)
			$subEntityClass=$criteriaClass;
		
		$joiningFieldBaseConstName=$subEntityClass."::".$field;
		$fieldType=constant($joiningFieldBaseConstName."FieldType");
		if($fieldType!=FieldTypeEnum::PROPERTY)
			throw new Exception("Only PROPERTY field types are allowed for between expressions. '".$fieldType."' was provided.");
		
		$propertyType=constant($joiningFieldBaseConstName."PropertyType");
		switch($propertyType){
			case PropertyTypeEnum::INT:
			case PropertyTypeEnum::FLOAT:
			case PropertyTypeEnum::DATETIME:
			case PropertyTypeEnum::DATE:
			case PropertyTypeEnum::TIME:
				break;
			default:
				throw new Exception("Property type '".$propertyType."' is not supported for between expression.");
		}
		
		$minS=PropertyTypeEnum::convertToString($min, $propertyType);
		$maxS=PropertyTypeEnum::convertToString($max, $propertyType);
		
		if($criteriaClass==$subEntityClass){
			$termName=$subEntityClass::getTableName();
			$theJoin=null;
		}else{
			$joinBasePlace=$criteriaClass::getTableName();
			$joinBaseColumn=$criteriaClass::getIDColumn();
			$joinColumn=$subEntityClass::getIDColumn();
			
			$joinName=self::getJoinName($subEntityClass, JoinType::INNER, $joinBasePlace, $joinBaseColumn, $joinColumn);
			$termName=$joinName;
			$theJoin=[];
			$theJoin[$subEntityClass]=[];
			$theJoin[$subEntityClass][JoinType::INNER]=[];
			$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace]=[];
			$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace][$joinBaseColumn]=[];
			$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace][$joinBaseColumn][$joinColumn]=$joinName;
		}
		
		$term=$termName.".".$field." BETWEEN ? AND ?";
		
		$valueType=PropertyTypeEnum::getPreparedStmtType($propertyType);
		
		$values=[$minS,$maxS];
		$valueTypes=[$valueType,$valueType];
		
		return new Expression($criteriaClass, $theJoin, $term, $values, $valueTypes);
	}
	
	/**
	 * Only allowed for text and email properties.
	 * 
	 * @param string $criteriaClass Class of the base entity, on which the criteria will be put.
	 * @param string $field A text property field (of the restriction object), on which the restriction shall take place.
	 * @param string $value Must be a string of length > 0.
	 * @param string $subEntityClass Class of the sub entity object, that will be joined if needed.
	 * @return Expression
	 */
	public static function contains($criteriaClass,$field,$value,$subEntityClass=null)
	{
		if($subEntityClass==null)
			$subEntityClass=$criteriaClass;
		$joiningFieldBaseConstName=$subEntityClass."::".$field;
		
		if($value===null)
			throw new Exception("Null value is not allowed for contains expression.");
		if(!is_string($value))
			throw new Exception("Value for contains expression must be a string.");
		
		/*@var $type FieldTypeEnum*/
		$type=constant($joiningFieldBaseConstName."FieldType");
		if($type!=FieldTypeEnum::PROPERTY)
			throw new Exception("Only property fields are allowed in contains expression. The provided field was of type '".$type."'.");
		
		/* @var $propertyType PropertyTypeEnum */
		$propertyType=constant($joiningFieldBaseConstName."PropertyType");
		switch($propertyType){
			case PropertyTypeEnum::TEXT:
			case PropertyTypeEnum::EMAIL:
				break;
			default:
				throw new Exception("Only text and email properties are allowed in contains expression. The provided field was of type '$propertyType'.");
		}
		
		$column=constant($joiningFieldBaseConstName."Column");
		if($criteriaClass==$subEntityClass){
			// base class does not need an inner join
			$termName=$subEntityClass::getTableName();
			$theJoin=null;
		}else{
			$joinBasePlace=$criteriaClass::getTableName();
			$joinBaseColumn=$criteriaClass::getIDColumn();
			$joinColumn=$subEntityClass::getIDColumn();

			$joinName=self::getJoinName($subEntityClass, JoinType::INNER,$joinBasePlace,$joinBaseColumn,$joinColumn);
			$termName=$joinName;
			$theJoin=[];
			$theJoin[$subEntityClass]=[];
			$theJoin[$subEntityClass][JoinType::INNER]=[];
			$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace]=[];
			$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace][$joinBaseColumn]=[];
			$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace][$joinBaseColumn][$joinColumn]=$joinName;
		}
		
		$term=$termName.".".$column." LIKE ?";
		$valueAsString="%".$value."%";
		$valueType=PropertyTypeEnum::getPreparedStmtType($propertyType);
		$values=[$valueAsString];
		$valueTypes=[$valueType];

		return new Expression($subEntityClass,$theJoin,$term,$values,$valueTypes);
	}
	
	/**
	 * Only allowed for text and email properties.
	 * 
	 * @param string $criteriaClass Class of the base entity, on which the criteria will be put.
	 * @param string $field A text property field (of the restriction object), on which the restriction shall take place.
	 * @param string $value Must be a string of length > 0.
	 * @param string $subEntityClass Class of the sub entity object, that will be joined if needed.
	 * @return Expression
	 */
	public static function endsWith($criteriaClass,$field,$value,$subEntityClass=null)
	{
		if($subEntityClass==null)
			$subEntityClass=$criteriaClass;
		$joiningFieldBaseConstName=$subEntityClass."::".$field;
		
		if($value===null)
			throw new Exception("Null value is not allowed for contains expression.");
		if(!is_string($value))
			throw new Exception("Value for contains expression must be a string.");
		
		/*@var $type FieldTypeEnum*/
		$type=constant($joiningFieldBaseConstName."FieldType");
		if($type!=FieldTypeEnum::PROPERTY)
			throw new Exception("Only property fields are allowed in contains expression. The provided field was of type '".$type."'.");
		
		/* @var $propertyType PropertyTypeEnum */
		$propertyType=constant($joiningFieldBaseConstName."PropertyType");
		switch($propertyType){
			case PropertyTypeEnum::TEXT:
			case PropertyTypeEnum::EMAIL:
				break;
			default:
				throw new Exception("Only text and email properties are allowed in contains expression. The provided field was of type '$propertyType'.");
		}
		
		$column=constant($joiningFieldBaseConstName."Column");
		if($criteriaClass==$subEntityClass){
			// base class does not need an inner join
			$termName=$subEntityClass::getTableName();
			$theJoin=null;
		}else{
			$joinBasePlace=$criteriaClass::getTableName();
			$joinBaseColumn=$criteriaClass::getIDColumn();
			$joinColumn=$subEntityClass::getIDColumn();

			$joinName=self::getJoinName($subEntityClass, JoinType::INNER,$joinBasePlace,$joinBaseColumn,$joinColumn);
			$termName=$joinName;
			$theJoin=[];
			$theJoin[$subEntityClass]=[];
			$theJoin[$subEntityClass][JoinType::INNER]=[];
			$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace]=[];
			$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace][$joinBaseColumn]=[];
			$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace][$joinBaseColumn][$joinColumn]=$joinName;
		}
		
		$term=$termName.".".$column." LIKE ?";
		$valueAsString="%".$value;
		$valueType=PropertyTypeEnum::getPreparedStmtType($propertyType);
		$values=[$valueAsString];
		$valueTypes=[$valueType];

		return new Expression($subEntityClass,$theJoin,$term,$values,$valueTypes);
	}
	
	/**
	 * If the provided field is a ManyToOne, it will be compared to all notnull properties.
	 * 
	 * @param string $criteriaClass Class of the base entity, on which the criteria will be put.
	 * @param string $field Field (of the restriction object), on which the restriction shall take place.
	 * @param mixed $value Can be null. For ManyToOne fields, all properties that are not null will be included.
	 * @param string $subEntityClass Class of the sub entity object, that will be joined if needed.
	 * @return Mixed Can be a single expression, or multiple ones (if comparing by a manyToOne field, creates multiple expressions that checks for equality to all not-null properties).
	 */
	public static function equal($criteriaClass,$field,$value,$subEntityClass=null)
	{
		if($subEntityClass==null)
			$subEntityClass=$criteriaClass;
		$joiningFieldBaseConstName=$subEntityClass."::".$field;
		
		if($value===null){
			// if comparing for null, its always the same, no matter the type of the field
			$column=constant($joiningFieldBaseConstName."Column");
			if($criteriaClass==$subEntityClass){
				// base class does not need an inner join
				$termName=$subEntityClass::getTableName();
				$theJoin=null;
			}else{
				$joinBasePlace=$criteriaClass::getTableName();
				$joinBaseColumn=$criteriaClass::getIDColumn();
				$joinColumn=$subEntityClass::getIDColumn();

				$joinName=self::getJoinName($subEntityClass, JoinType::INNER,$joinBasePlace,$joinBaseColumn,$joinColumn);
				$termName=$joinName;
				$theJoin=[];
				$theJoin[$subEntityClass]=[];
				$theJoin[$subEntityClass][JoinType::INNER]=[];
				$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace]=[];
				$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace][$joinBaseColumn]=[];
				$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace][$joinBaseColumn][$joinColumn]=$joinName;
			}
			$term=$termName.".".$column." IS NULL";
			
			
			return new Expression($subEntityClass,$theJoin,$term);
		}
		
		/*@var $type FieldTypeEnum*/
		$type=constant($joiningFieldBaseConstName."FieldType");
		switch($type){
			case FieldTypeEnum::PROPERTY:
				$column=constant($joiningFieldBaseConstName."Column");
				if($criteriaClass==$subEntityClass){
					// base class does not need an inner join
					$termName=$subEntityClass::getTableName();
					$theJoin=null;
				}else{
					$joinBasePlace=$criteriaClass::getTableName();
					$joinBaseColumn=$criteriaClass::getIDColumn();
					$joinColumn=$subEntityClass::getIDColumn();
					
					$joinName=self::getJoinName($subEntityClass, JoinType::INNER,$joinBasePlace,$joinBaseColumn,$joinColumn);
					$termName=$joinName;
					$theJoin=[];
					$theJoin[$subEntityClass]=[];
					$theJoin[$subEntityClass][JoinType::INNER]=[];
					$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace]=[];
					$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace][$joinBaseColumn]=[];
					$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace][$joinBaseColumn][$joinColumn]=$joinName;
				}
				$term=$termName.".".$column."=?";
				$propertyType=constant($joiningFieldBaseConstName."PropertyType");
				$valueAsString=PropertyTypeEnum::convertToString($value, $propertyType);
				$valueType=PropertyTypeEnum::getPreparedStmtType($propertyType);
				$values=[$valueAsString];
				$valueTypes=[$valueType];
				
				return new Expression($subEntityClass,$theJoin,$term,$values,$valueTypes);
			case FieldTypeEnum::MANY_TO_ONE:
				// TODO Expression::equal manyToOne
			default:
				throw new Exception("The provided field is of unsupported field type '".$type."'.");
		}
	}
	
	/**
	 * Only allowed for text and email properties.
	 * 
	 * @param string $criteriaClass Class of the base entity, on which the criteria will be put.
	 * @param string $field A text property field (of the restriction object), on which the restriction shall take place.
	 * @param string $value Must be a string of length > 0.
	 * @param string $subEntityClass Class of the sub entity object, that will be joined if needed.
	 * @return Expression
	 */
	public static function startsWith($criteriaClass,$field,$value,$subEntityClass=null)
	{
		if($subEntityClass==null)
			$subEntityClass=$criteriaClass;
		$joiningFieldBaseConstName=$subEntityClass."::".$field;
		
		if($value===null)
			throw new Exception("Null value is not allowed for contains expression.");
		if(!is_string($value))
			throw new Exception("Value for contains expression must be a string.");
		
		/*@var $type FieldTypeEnum*/
		$type=constant($joiningFieldBaseConstName."FieldType");
		if($type!=FieldTypeEnum::PROPERTY)
			throw new Exception("Only property fields are allowed in contains expression. The provided field was of type '".$type."'.");
		
		/* @var $propertyType PropertyTypeEnum */
		$propertyType=constant($joiningFieldBaseConstName."PropertyType");
		switch($propertyType){
			case PropertyTypeEnum::TEXT:
			case PropertyTypeEnum::EMAIL:
				break;
			default:
				throw new Exception("Only text and email properties are allowed in contains expression. The provided field was of type '$propertyType'.");
		}
		
		$column=constant($joiningFieldBaseConstName."Column");
		if($criteriaClass==$subEntityClass){
			// base class does not need an inner join
			$termName=$subEntityClass::getTableName();
			$theJoin=null;
		}else{
			$joinBasePlace=$criteriaClass::getTableName();
			$joinBaseColumn=$criteriaClass::getIDColumn();
			$joinColumn=$subEntityClass::getIDColumn();

			$joinName=self::getJoinName($subEntityClass, JoinType::INNER,$joinBasePlace,$joinBaseColumn,$joinColumn);
			$termName=$joinName;
			$theJoin=[];
			$theJoin[$subEntityClass]=[];
			$theJoin[$subEntityClass][JoinType::INNER]=[];
			$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace]=[];
			$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace][$joinBaseColumn]=[];
			$theJoin[$subEntityClass][JoinType::INNER][$joinBasePlace][$joinBaseColumn][$joinColumn]=$joinName;
		}
		
		$term=$termName.".".$column." LIKE ?";
		$valueAsString=$value."%";
		$valueType=PropertyTypeEnum::getPreparedStmtType($propertyType);
		$values=[$valueAsString];
		$valueTypes=[$valueType];

		return new Expression($subEntityClass,$theJoin,$term,$values,$valueTypes);
	}
	
	/**
	 * Puts an OR between all of the provided expressions.
	 * All expressions must have the same entity class.
	 * 
	 * @param array $expressions
	 * @return Expression
	 */
	public static function any($expressions)
	{
		$entityClass=$expressions[0]->EntityClass;
		$mergedJoins=[];
		$mergedTerm="(";
		$mergedValues=[];
		$mergedValueTypes=[];
		$valueCount=0;
		
		$isFirst=true;
		foreach($expressions as $expression){
			/*@var $expression Expression*/
			
			// merge all joins
			foreach($expression->Joins as $joiningEntityClass => $arrayByJoiningEntityClass){
				if(!isset($mergedJoins[$joiningEntityClass]))
					$mergedJoins[$joiningEntityClass]=[];
				$mergedArrayByJoiningEntityClass=$mergedJoins[$joiningEntityClass];

				foreach($arrayByJoiningEntityClass as $joinType => $arrayByJoinType){
					if(!isset($mergedArrayByJoiningEntityClass[$joinType]))
						$mergedArrayByJoiningEntityClass[$joinType]=[];
					$mergedArrayByJoinType=$mergedArrayByJoiningEntityClass[$joinType];

					foreach($arrayByJoinType as $joinBasePlace => $arrayByJoinBasePlace){
						if(!isset($mergedArrayByJoinType[$joinBasePlace]))
							$mergedArrayByJoinType[$joinBasePlace]=[];
						$mergedArrayByJoinBasePlace=$mergedArrayByJoinType[$joinBasePlace];

						foreach($arrayByJoinBasePlace as $joinBaseColumn => $arrayByJoinBaseColumn){
							if(!isset($mergedArrayByJoinBasePlace[$joinBaseColumn]))
								$mergedArrayByJoinBasePlace[$joinBaseColumn]=[];
							$mergedArrayByJoinBaseColumn=$mergedArrayByJoinBasePlace[$joinBaseColumn];

							foreach($arrayByJoinBaseColumn as $joinColumn => $joinName){
								if(!isset($mergedArrayByJoinBaseColumn[$joinColumn])){
									$mergedArrayByJoinBaseColumn[$joinColumn]=$joinName;

									$mergedArrayByJoinBasePlace[$joinBaseColumn]=$mergedArrayByJoinBaseColumn;
									$mergedArrayByJoinType[$joinBasePlace]=$mergedArrayByJoinBasePlace;
									$mergedArrayByJoiningEntityClass[$joinType]=$mergedArrayByJoinType;
									$mergedJoins[$joiningEntityClass]=$mergedArrayByJoiningEntityClass;
								}
							}
						}
					}
				}
			}
			
			if(!$isFirst)
				$mergedTerm.=" OR ";
			else
				$isFirst=false;
			
			$mergedTerm.="(".$expression->Term.")";
			
			for($i=$expression->ValueCount-1;$i>=0;$i--){
				$mergedValues[]=$expression->Values[$i];
				$mergedValueTypes[]=$expression->ValueTypes[$i];
				$valueCount++;
			}
		}
		
		$mergedTerm.=")";
		
		return new Expression($entityClass, $mergedJoins, $mergedTerm, $mergedValues, $mergedValueTypes);
	}
}
