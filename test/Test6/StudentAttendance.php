<?php

/*
 * StudentAttendance.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 29, 2017 Grega Mohorko
 */

namespace Test6;

use BlueDB\Entity\SubAssociativeEntity;
use BlueDB\Entity\FieldTypeEnum;

class StudentAttendance extends SubAssociativeEntity
{
	public static function getTableName() { return "StudentAttendance"; }
	public static function getParentEntityClass() { return Attendance::class; }
	public static function getIDColumn() { return "Attendance_ID"; }
	public static function getParentFieldName() { return "Attendance"; }
	public static function getSideA() { return self::StudentsSide; }
	public static function getSideB() { return self::SubjectsSide; }

	const StudentsSide="Students";
	const StudentsColumn=self::StudentColumn;
	const StudentsClass=self::StudentClass;
	
	const SubjectsSide="Subjects";
	const SubjectsColumn=self::SubjectColumn;
	const SubjectsClass=self::SubjectClass;
	
	/**
	 * @var Attendance
	 */
	public $Attendance;
	
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
}
