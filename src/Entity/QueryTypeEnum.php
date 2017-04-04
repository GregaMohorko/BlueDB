<?php

/*
 * QueryTypeEnum.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 4, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

abstract class QueryTypeEnum
{
	const UNKNOWN=0;
	const SELECT=1;
	const INSERT=2;
	const UPDATE=3;
	const DELETE=4;
}
