<?php

/*
 * User.php
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
