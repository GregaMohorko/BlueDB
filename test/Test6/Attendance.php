<?php

/*
 * Attendance.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 29, 2017 Grega Mohorko
 */

namespace Test6;

use DateTime;
use BlueDB\Entity\StrongEntity;
use BlueDB\Entity\FieldTypeEnum;
use BlueDB\Entity\PropertyTypeEnum;

class Attendance extends StrongEntity
{
	public static function getTableName() { return "Attendance"; }
	
	/**
	 * @var DateTime
	 */
	public $Date;
	const DateField="Date";
	const DateFieldType=FieldTypeEnum::PROPERTY;
	const DateColumn=self::DateField;
	const DatePropertyType=PropertyTypeEnum::DATE;
	/**
	 * @var bool
	 */
	public $WasPresent;
	const WasPresentField="WasPresent";
	const WasPresentFieldType=FieldTypeEnum::PROPERTY;
	const WasPresentColumn=self::WasPresentField;
	const WasPresentPropertyType=PropertyTypeEnum::BOOL;
}
