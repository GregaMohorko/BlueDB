<?php

/*
 * PropertyTypeCreator.php
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
 * @copyright Mar 15, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

use Exception;
use DateTime;
use BlueDB\Configuration\BlueDBProperties;

/**
 * Creates actual objects from string values queried from MySQL.
 * 
 * For example, creates an actual DateTime object from "2017-03-15" value.
 */
abstract class PropertyCreator
{
	/**
	 * @param string $value
	 * @param PropertyTypeEnum $type
	 * return mixed
	 */
	public static function create($value,$type)
	{
		switch($type){
			case PropertyTypeEnum::TEXT:
			case PropertyTypeEnum::EMAIL:
			case PropertyTypeEnum::COLOR:
				return $value;
			case PropertyTypeEnum::INT:
				return self::createInt($value);
			case PropertyTypeEnum::FLOAT:
				return self::createFloat($value);
			case PropertyTypeEnum::ENUM:
				return self::createEnum($value);
			case PropertyTypeEnum::BOOL:
				return self::createBool($value);
			case PropertyTypeEnum::DATE:
				return self::createDate($value);
			case PropertyTypeEnum::TIME:
				return self::createTime($value);
			case PropertyTypeEnum::DATETIME:
				return self::createDateTime($value);
			default:
				throw new Exception("The provided PropertyTypeEnum '".$type."' is not supported.");
		}
	}
	
	/**
	 * @param mixed $value
	 * @return int
	 */
	public static function createInt($value)
	{
		$intValue=intval($value);
		
		return $intValue;
	}
	
	/**
	 * @param mixed $value
	 * @return float
	 */
	public static function createFloat($value)
	{
		$floatValue=floatval($value);
		
		return $floatValue;
	}
	
	/**
	 * @param mixed $value
	 * @return int
	 */
	public static function createEnum($value)
	{
		if($value===null){
			return null;
		}
		$intValue=self::createInt($value);
		if($intValue<0){
			throw new Exception("Enum values have to be bigger than 0. '".$intValue."' was provided.");
		}
		
		return $intValue;
	}
	
	/**
	 * @param mixed $value
	 * @return bool
	 */
	public static function createBool($value)
	{
		if($value===null){
			return false;
		}
		
		$boolValue=boolval($value);
		
		return $boolValue;
	}
	
	/**
	 * @param mixed $value
	 * @return DateTime
	 */
	public static function createDate($value)
	{
		if($value===null){
			return null;
		}
		
		$properties=BlueDBProperties::instance();
		
		/*@var $dateValue DateTime*/
		$dateValue=DateTime::createFromFormat($properties->Format_Date, $value);
		if(!$dateValue){
			throw new Exception("Value '".$value."' is not a date.");
		}
		
		$dateValue->setTime(0, 0, 0);
		
		return $dateValue;
	}
	
	/**
	 * @param mixed $value
	 * @return DateTime
	 */
	public static function createTime($value)
	{
		if($value===null){
			return null;
		}
		
		$properties=BlueDBProperties::instance();
		
		/* @var $timeValue DateTime */
		$timeValue=DateTime::createFromFormat($properties->Format_Time,$value);
		if(!$timeValue){
			throw new Exception("Value '".$value."' is not a time.");
		}
		
		$timeValue->setDate(0, 1, 1);
		
		return $timeValue;
	}
	
	/**
	 * @param mixed $value
	 * @return DateTime
	 */
	public static function createDateTime($value)
	{
		if($value===null){
			return null;
		}
		
		$properties=BlueDBProperties::instance();
		
		$datetimeValue=DateTime::createFromFormat($properties->Format_DateTime,$value);
		if(!$datetimeValue){
			throw new Exception("Value '".$value."' is not a datetime.");
		}

		return $datetimeValue;
	}
}
