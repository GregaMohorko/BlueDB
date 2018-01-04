<?php

/*
 * PropertyComparer.php
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

use Exception;
use BlueDB\Utility\DateTimeUtility;

/**
 * Static class used to compare property fields of entities.
 */
abstract class PropertyComparer
{
	/**
	 * Compares two property values based on the specified property type.
	 * 
	 * @param mixed $value1
	 * @param mixed $value2
	 * @param PropertyTypeEnum $propertyType
	 * @return bool True if the values are considered to be the same.
	 */
	public static function compare($value1,$value2,$propertyType)
	{
		if($value1===null && $value2===null){
			return true;
		}
		if($value1===null || $value2===null){
			return false;
		}
		
		switch($propertyType){
			case PropertyTypeEnum::TEXT:
			case PropertyTypeEnum::INT:
			case PropertyTypeEnum::FLOAT:
			case PropertyTypeEnum::ENUM:
			case PropertyTypeEnum::BOOL:
			case PropertyTypeEnum::EMAIL:
			case PropertyTypeEnum::IP:
			case PropertyTypeEnum::COLOR:
				return $value1===$value2;
			case PropertyTypeEnum::DATE:
				return DateTimeUtility::areOnTheSameDay($value1, $value2);
			case PropertyTypeEnum::TIME:
				return DateTimeUtility::areOnTheSameTime($value1, $value2);
			case PropertyTypeEnum::DATETIME:
				return $value1==$value2;
			default:
				throw new Exception("Unsupported property type '$propertyType'.");
		}
	}
}
