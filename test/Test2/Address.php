<?php

/*
 * Address.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 4, 2017 Grega Mohorko
 */

namespace Test2;

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
