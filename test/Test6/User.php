<?php

/*
 * User.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 29, 2017 Grega Mohorko
 */

namespace Test6;

use BlueDB\Entity\StrongEntity;
use BlueDB\Entity\FieldTypeEnum;
use BlueDB\Entity\PropertyTypeEnum;

class User extends StrongEntity
{
	public static function getTableName() { return "User"; }
	
	/**
	 * @var string
	 */
	public $Name;
	const NameField="Name";
	const NameFieldType=FieldTypeEnum::PROPERTY;
	const NameColumn=self::NameField;
	const NamePropertyType=PropertyTypeEnum::TEXT;
}
