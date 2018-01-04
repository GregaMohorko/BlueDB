<?php

/*
 * User.php
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
 * @copyright Mar 15, 2017 Grega Mohorko
 */

namespace Test1;

use DateTime;
use BlueDB\Entity\StrongEntity;
use BlueDB\Entity\FieldTypeEnum;
use BlueDB\Entity\PropertyTypeEnum;

class User extends StrongEntity
{
	public static function getTableName() { return "User"; }
	
	/**
	 * @var string
	 */
	public $Username;
	const UsernameField="Username";
	const UsernameFieldType=FieldTypeEnum::PROPERTY;
	const UsernameColumn=self::UsernameField;
	const UsernamePropertyType=PropertyTypeEnum::TEXT;
	/**
	 * @var string
	 */
	public $Password;
	const PasswordField="Password";
	const PasswordFieldType=FieldTypeEnum::PROPERTY;
	const PasswordColumn=self::PasswordField;
	const PasswordPropertyType=PropertyTypeEnum::TEXT;
	const PasswordIsHidden=true;
	/**
	 * @var UserType
	 */
	public $Type;
	const TypeField="Type";
	const TypeFieldType=FieldTypeEnum::PROPERTY;
	const TypeColumn="Type_ID";
	const TypePropertyType=PropertyTypeEnum::ENUM;
	/**
	 * @var int
	 */
	public $CarCount;
	const CarCountField="CarCount";
	const CarCountFieldType=FieldTypeEnum::PROPERTY;
	const CarCountColumn=self::CarCountField;
	const CarCountPropertyType=PropertyTypeEnum::INT;
	/**
	 * @var float
	 */
	public $Cash;
	const CashField="Cash";
	const CashFieldType=FieldTypeEnum::PROPERTY;
	const CashColumn=self::CashField;
	const CashPropertyType=PropertyTypeEnum::FLOAT;
	/**
	 * @var bool
	 */
	public $IsOkay;
	const IsOkayField="IsOkay";
	const IsOkayFieldType=FieldTypeEnum::PROPERTY;
	const IsOkayColumn=self::IsOkayField;
	const IsOkayPropertyType=PropertyTypeEnum::BOOL;
	/**
	 * @var string
	 */
	public $Email;
	const EmailField="Email";
	const EmailFieldType=FieldTypeEnum::PROPERTY;
	const EmailColumn=self::EmailField;
	const EmailPropertyType=PropertyTypeEnum::EMAIL;
	/**
	 * @var DateTime
	 */
	public $Birthday;
	const BirthdayField="Birthday";
	const BirthdayFieldType=FieldTypeEnum::PROPERTY;
	const BirthdayColumn=self::BirthdayField;
	const BirthdayPropertyType=PropertyTypeEnum::DATE;
	/**
	 * @var DateTime
	 */
	public $AlarmClock;
	const AlarmClockField="AlarmClock";
	const AlarmClockFieldType=FieldTypeEnum::PROPERTY;
	const AlarmClockColumn=self::AlarmClockField;
	const AlarmClockPropertyType=PropertyTypeEnum::TIME;
	/**
	 * @var DateTime
	 */
	public $Created;
	const CreatedField="Created";
	const CreatedFieldType=FieldTypeEnum::PROPERTY;
	const CreatedColumn=self::CreatedField;
	const CreatedPropertyType=PropertyTypeEnum::DATETIME;
}
