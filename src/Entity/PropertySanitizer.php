<?php

/*
 * PropertyTypeSanitizer.php
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
 * @copyright May 23, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

use BlueDB\DataAccess\MySQL;
use BlueDB\Utility\StringUtility;
use DateTime;
use Exception;

/**
 * Sanitizes raw string values of properties received from the client and creates actual objects.
 * 
 * For example, if 'john.shepardgmail.com' is provided for an Email property, it throws an exception because the value is not a valid email.
 */
abstract class PropertySanitizer
{
	/**
	 * @param string $value
	 * @param PropertyTypeEnum $type
	 * return mixed
	 */
	public static function sanitize($value,$type)
	{
		if(!is_string($value)){
			// no need to sanitize non-string values ...
			return PropertyCreator::create($value, $type);
		}
		
		$escapedValue=MySQL::escapeString($value);
		
		switch($type){
			case PropertyTypeEnum::TEXT:
				return self::sanitizeText($escapedValue);
			case PropertyTypeEnum::INT:
				return self::sanitizeInt($escapedValue);
			case PropertyTypeEnum::FLOAT:
				return self::sanitizeFloat($escapedValue);
			case PropertyTypeEnum::ENUM:
				return self::sanitizeEnum($escapedValue);
			case PropertyTypeEnum::BOOL:
				return self::sanitizeBool($escapedValue);
			case PropertyTypeEnum::DATE:
				return self::sanitizeDate($escapedValue);
			case PropertyTypeEnum::TIME:
				return self::sanitizeTime($escapedValue);
			case PropertyTypeEnum::DATETIME:
				return self::sanitizeDatetime($escapedValue);
			case PropertyTypeEnum::EMAIL:
				return self::sanitizeEmail($escapedValue);
			case PropertyTypeEnum::COLOR:
				return self::sanitizeColor($escapedValue);
			default:
				throw new Exception("The provided PropertyTypeEnum '".$type."' is not supported.");
		}
	}
	
	/**
	 * @param string $escapedValue
	 * @return string
	 */
	private static function sanitizeText($escapedValue)
	{
		$textValue=filter_var($escapedValue,FILTER_SANITIZE_STRING);
		if($textValue===false){
			throw new Exception("String filter failed for '$escapedValue'.");
		}
		return $textValue;
	}
	
	/**
	 * @param string $escapedValue
	 * @return int
	 */
	private static function sanitizeInt($escapedValue)
	{
		$filteredValue=filter_var($escapedValue, FILTER_VALIDATE_INT);
		if($filteredValue===false){
			throw new Exception("Int filter failed for '$escapedValue'.");
		}
		return PropertyCreator::createInt($filteredValue);
	}
	
	/**
	 * @param string $escapedValue
	 * @return float
	 */
	private static function sanitizeFloat($escapedValue)
	{
		$valueWithoutCommas=str_replace(",", ".", $escapedValue);
		$filteredValue=filter_var($valueWithoutCommas, FILTER_VALIDATE_FLOAT);
		if($filteredValue===false){
			throw new Exception("Float filter failed for '$escapedValue'.");
		}
		return PropertyCreator::createFloat($filteredValue);
	}
	
	/**
	 * @param string $escapedValue
	 * @return int
	 */
	private static function sanitizeEnum($escapedValue)
	{
		$filteredValue=filter_var($escapedValue,FILTER_VALIDATE_INT);
		if($filteredValue===false){
			throw new Exception("Int filter failed for '$escapedValue'.");
		}
		return PropertyCreator::createEnum($filteredValue);
	}
	
	private static $boolFilterOptions=array("options" => array("min_range"=>0,"max_range"=>1));
	
	/**
	 * @param string $escapedValue
	 * @return bool
	 */
	private static function sanitizeBool($escapedValue)
	{
		$filteredValue=filter_var($escapedValue, FILTER_VALIDATE_INT,self::$boolFilterOptions);
		if($filteredValue===false){
			throw new Exception("Bool filter failed for '$escapedValue'.");
		}
		return PropertyCreator::createBool($filteredValue);
	}
	
	/**
	 * @param string $escapedValue
	 * @return DateTime
	 */
	private static function sanitizeDate($escapedValue)
	{
		$shortenedValue=substr($escapedValue, 0, 10);
		return PropertyCreator::createDate($shortenedValue);
	}
	
	/**
	 * @param string $escapedValue
	 * @return DateTime
	 */
	private static function sanitizeTime($escapedValue)
	{
		return PropertyCreator::createTime($escapedValue);
	}
	
	/**
	 * @param string $escapedValue
	 * @return DateTime
	 */
	private static function sanitizeDatetime($escapedValue)
	{
		return PropertyCreator::createDateTime($escapedValue);
	}
	
	/**
	 * @param string $escapedValue
	 * @return string
	 */
	private static function sanitizeEmail($escapedValue)
	{
		if(strlen($escapedValue)==0){
			return $escapedValue;
		}
		$normalEscapedValue= StringUtility::replaceSlavicCharsToNormalEquivalents($escapedValue);
		$emailValue=filter_var($normalEscapedValue, FILTER_VALIDATE_EMAIL);
		if($emailValue===false){
			throw new Exception("Email filter failed for '$escapedValue'.");
		}
		return $emailValue;
	}
	
	/**
	 * @param string $escapedValue
	 * @return string
	 */
	private static function sanitizeColor($escapedValue)
	{
		if(strlen($escapedValue)!=6){
			throw new Exception("Value '$escapedValue' is not a valid color.");
		}
		
		$upperValue=strtoupper($escapedValue);
		
		for($i=5;$i>=0;--$i){
			switch($upperValue[$i]){
				case '0':
				case '1':
				case '2':
				case '3':
				case '4':
				case '5':
				case '6':
				case '7':
				case '8':
				case '9':
				case 'A':
				case 'B':
				case 'C':
				case 'D':
				case 'E':
				case 'F':
					break;
				default:
					throw new Exception("Value '$escapedValue' is not a valid color.");
			}
		}
		
		return $upperValue;
	}
}
