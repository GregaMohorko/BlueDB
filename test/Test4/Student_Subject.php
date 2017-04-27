<?php

/*
 * Student_Subject.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 26, 2017 Grega Mohorko
 */

namespace Test4;

use BlueDB\Entity\AssociativeEntity;

abstract class Student_Subject extends AssociativeEntity
{
	public static function getTableName() { return "Student_Subject"; }
	public static function getSideA() { return self::StudentsSide; }
	public static function getSideB() { return self::SubjectsSide; }

	const StudentsSide="Students";
	const StudentsColumn="Student_ID";
	const StudentsClass=Student::class;
	
	const SubjectsSide="Subjects";
	const SubjectsColumn="Subject_ID";
	const SubjectsClass=Subject::class;
}
