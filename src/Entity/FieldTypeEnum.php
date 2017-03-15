<?php

/*
 * FieldTypeEnum.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

abstract class FieldTypeEnum
{
	const UNKNOWN=0;
	const PROPERTY=1;
	const MANY_TO_ONE=2;
	const ONE_TO_MANY=3;
	const MANY_TO_MANY=4;
}
