<?php

/*
 * JoinType.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\DataAccess\Criteria;

abstract class JoinType
{
	const UNKNOWN="0";
	const INNER="INNER";
	const LEFT_OUTER="LEFT OUTER";
	const RIGHT_OUTER="RIGHT OUTER";
	const FULL_OUTER="FULL OUTER";
}
