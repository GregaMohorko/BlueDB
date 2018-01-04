<?php

/*
 * Student.php
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

namespace Test6;

use BlueDB\Entity\SubEntity;
use BlueDB\Entity\FieldTypeEnum;
use BlueDB\Entity\PropertyTypeEnum;

class Student extends SubEntity
{
	public static function getTableName() { return "Student"; }
	public static function getIDColumn() { return "User_ID"; }
	public static function getParentEntityClass() { return User::class; }
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
	/**
	 * @var array
	 */
	public $Attendances;
	const AttendancesField="Attendances";
	const AttendancesFieldType=FieldTypeEnum::ONE_TO_MANY;
	const AttendancesClass=StudentAttendance::class;
	const AttendancesIdentifier=StudentAttendance::StudentField;
	/**
	 * @var array
	 */
	public $Subjects;
	const SubjectsField="Subjects";
	const SubjectsFieldType=FieldTypeEnum::MANY_TO_MANY;
	const SubjectsClass=StudentAttendance::class;
	const SubjectsSide=StudentAttendance::StudentsSide;
}
