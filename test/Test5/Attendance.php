<?php

/*
 * Attendance.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 29, 2017 Grega Mohorko
 */

namespace Test5;

use BlueDB\Entity\StrongAssociativeEntity;
use BlueDB\Entity\FieldTypeEnum;
use BlueDB\Entity\PropertyTypeEnum;

class Attendance extends StrongAssociativeEntity
{
	public static function getTableName() { return "Attendance"; }
	public static function getSideA() { return self::StudentsSide; }
	public static function getSideB() { return self::SubjectsSide; }

	const StudentsSide="Students";
	const StudentsColumn=self::StudentColumn;
	const StudentsClass=self::StudentClass;
	
	const SubjectsSide="Subjects";
	const SubjectsColumn=self::SubjectColumn;
	const SubjectsClass=self::SubjectClass;

	/**
	 * @var Student
	 */
	public $Student;
	const StudentField="Student";
	const StudentFieldType=FieldTypeEnum::MANY_TO_ONE;
	const StudentColumn="Student_ID";
	const StudentClass=Student::class;
	/**
	 * @var Subject
	 */
	public $Subject;
	const SubjectField="Subject";
	const SubjectFieldType=FieldTypeEnum::MANY_TO_ONE;
	const SubjectColumn="Subject_ID";
	const SubjectClass=Subject::class;
	/**
	 * @var float
	 */
	public $AverageGrade;
	const AverageGradeField="AverageGrade";
	const AverageGradeFieldType=FieldTypeEnum::PROPERTY;
	const AverageGradeColumn=self::AverageGradeField;
	const AverageGradePropertyType=PropertyTypeEnum::FLOAT;
}
