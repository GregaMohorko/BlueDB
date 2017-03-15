<?php

/*
 * PropertyTypeEnum.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

use Exception;
use BlueDB\Configuration\BlueDBProperties;

abstract class PropertyTypeEnum
{
	const UNKNOWN=0;
	const TEXT=1;
	const INT=2;
	const FLOAT=3;
	const ENUM=4;
	const BOOL=5;
	const DATE=6;
	const TIME=7;
	const DATETIME=8;
	const EMAIL=9;
	const IP=10;
	const COLOR=11;
	
	/**
	 * @param int $propertyTypeEnum
	 * @return string
	 */
	public static function getPreparedStmtType($propertyTypeEnum)
	{
		switch($propertyTypeEnum){
			case PropertyTypeEnum::TEXT:
			case PropertyTypeEnum::DATE:
			case PropertyTypeEnum::TIME:
			case PropertyTypeEnum::DATETIME:
			case PropertyTypeEnum::EMAIL:
			case PropertyTypeEnum::IP:
			case PropertyTypeEnum::COLOR:
				return 's';
			case PropertyTypeEnum::INT:
			case PropertyTypeEnum::ENUM:
			case PropertyTypeEnum::BOOL:
				return 'i';
			case PropertyTypeEnum::FLOAT:
				return 'd';
			default:
				throw new Exception("Provided PropertyTypeEnum '".$propertyTypeEnum."' is not supported.");
		}
	}
	
	/**
	 * @param mixed $propertyValue
	 * @param int $propertyType
	 * @return string
	 */
	public static function convertToString($propertyValue,$propertyType)
	{
		if($propertyValue===null)
			return null;
		
		switch($propertyType){
			case PropertyTypeEnum::TEXT:
			case PropertyTypeEnum::INT:
			case PropertyTypeEnum::FLOAT:
			case PropertyTypeEnum::ENUM:
			case PropertyTypeEnum::BOOL:
			case PropertyTypeEnum::EMAIL:
			case PropertyTypeEnum::IP:
			case PropertyTypeEnum::COLOR:
				return $propertyValue;
			case PropertyTypeEnum::DATE:
				$properties=BlueDBProperties::Instance();
				return $propertyValue->format($properties->Format_Date);
			case PropertyTypeEnum::TIME:
				$properties=BlueDBProperties::Instance();
				return $propertyValue->format($properties->Format_Time);
			case PropertyTypeEnum::DATETIME:
				$properties=BlueDBProperties::Instance();
				return $propertyValue->format($properties->Format_DateTime);
			default:
				throw new Exception("PropertyType '".$propertyType."' is not supported.");
		}
	}
}
