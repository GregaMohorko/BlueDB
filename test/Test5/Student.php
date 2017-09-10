<?php

/*
 * Student.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 29, 2017 Grega Mohorko
 */

namespace Test5;

use BlueDB\Entity\StrongEntity;
use BlueDB\Entity\FieldTypeEnum;
use BlueDB\Entity\PropertyTypeEnum;

class Student extends StrongEntity
{
	public static function getTableName() { return "Student"; }
	
	/**
	 * @var string
	 */
	public $Name;
	const NameField="Name";
	const NameFieldType=FieldTypeEnum::PROPERTY;
	const NameColumn=self::NameField;
	const NamePropertyType=PropertyTypeEnum::TEXT;
	/**
	 * @var array
	 */
	public $Subjects;
	const SubjectsField="Subjects";
	const SubjectsFieldType=FieldTypeEnum::MANY_TO_MANY;
	const SubjectsClass=Attendance::class;
	const SubjectsSide=Attendance::StudentsSide;
}