<?php

/*
 * Teacher.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 22, 2017 Grega Mohorko
 */

namespace Test3;

use BlueDB\Entity\SubEntity;

class Teacher extends SubEntity
{
	public static function getTableName() { return "Teacher"; }
	public static function getParentEntityClass() { return User::class; }
	public static function getIDColumn() { return "User_ID"; }
	public static function getParentFieldName() { return "User"; }

	/**
	 * @var User
	 */
	public $User;
}
