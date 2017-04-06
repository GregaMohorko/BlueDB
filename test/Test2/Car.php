<?php

/*
 * Car.php
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
