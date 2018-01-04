<?php

/*
 * Address.php
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
 * @copyright Apr 21, 2017 Grega Mohorko
 */

namespace Test3;

use BlueDB\Entity\StrongEntity;
use BlueDB\Entity\FieldTypeEnum;
use BlueDB\Entity\PropertyTypeEnum;

class Address extends StrongEntity
{
	public static function getTableName() { return "Address"; }
	
	/**
	 * @var string
	 */
	public $Street;
	const StreetField="Street";
	const StreetFieldType=FieldTypeEnum::PROPERTY;
	const StreetColumn=self::StreetField;
	const StreetPropertyType=PropertyTypeEnum::TEXT;
	/**
	 * @var array
	 */
	public $Users;
	const UsersField="Users";
	const UsersFieldType=FieldTypeEnum::ONE_TO_MANY;
	const UsersClass=User::class;
	const UsersIdentifier=User::AddressField;
}
