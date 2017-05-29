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
}
