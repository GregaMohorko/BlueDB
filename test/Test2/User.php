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
 * @copyright Apr 4, 2017 Grega Mohorko
 */

namespace Test2;

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
	/**
	 * @var Address
	 */
	public $Address;
	const AddressField="Address";
	const AddressFieldType=FieldTypeEnum::MANY_TO_ONE;
	const AddressColumn="Address_ID";
	const AddressClass=Address::class;
	/**
	 * @var Car
	 */
	public $Car;
	const CarField="Car";
	const CarFieldType=FieldTypeEnum::MANY_TO_ONE;
	const CarColumn="Car_ID";
	const CarClass=Car::class;
	/**
	 * @var User
	 */
	public $BestFriend;
	const BestFriendField="BestFriend";
	const BestFriendFieldType=FieldTypeEnum::MANY_TO_ONE;
	const BestFriendColumn="BestFriend_ID";
	const BestFriendClass=User::class;
	/**
	 * @var array
	 */
	public $BestFriendTo;
	const BestFriendToField="BestFriendTo";
	const BestFriendToFieldType=FieldTypeEnum::ONE_TO_MANY;
	const BestFriendToClass=User::class;
	const BestFriendToIdentifier=User::BestFriendField;
}
