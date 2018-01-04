<?php

/*
 * Car.php
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

class Car extends StrongEntity
{
	public static function getTableName() { return "Car"; }
	
	/**
	 * @var string
	 */
	public $Brand;
	const BrandField="Brand";
	const BrandFieldType=FieldTypeEnum::PROPERTY;
	const BrandColumn=self::BrandField;
	const BrandPropertyType=PropertyTypeEnum::TEXT;
	/**
	 * @var User
	 */
	public $Owner;
	const OwnerField="Owner";
	const OwnerFieldType=FieldTypeEnum::MANY_TO_ONE;
	const OwnerColumn="Owner_ID";
	const OwnerClass=User::class;
}
