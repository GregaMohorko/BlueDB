<?php

/*
 * Attendance.php
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
