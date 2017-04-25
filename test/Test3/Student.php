<?php

/*
 * Student.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 21, 2017 Grega Mohorko
 */

namespace Test3;

use BlueDB\Entity\SubEntity;
use BlueDB\Entity\FieldTypeEnum;
use BlueDB\Entity\PropertyTypeEnum;

class Student extends SubEntity
{
	public static function getTableName() { return "Student"; }
	public static function getParentEntityClass() { return User::class; }
	public static function getIDColumn() { return "User_ID"; }
	public static function getParentFieldName() { return "User"; }
	
	/**
	 * @var User
	 */
	public $User;
	
	/**
	 * @var string
	 */
	public $RegistrationNumber;
	const RegistrationNumberField="RegistrationNumber";
	const RegistrationNumberFieldType=FieldTypeEnum::PROPERTY;
	const RegistrationNumberColumn=self::RegistrationNumberField;
	const RegistrationNumberPropertyType=PropertyTypeEnum::TEXT;
}
