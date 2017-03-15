<?php

/*
 * UserType.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 15, 2017 Grega Mohorko
 */

namespace Test1;

abstract class UserType
{
	const UNKNOWN=0;
	
	// these values should be the same as the ones in the database (see Test1.sql)
	const ADMIN=1;
	const EDITOR=2;
}
