<?php

/*
 * DateTimeUtility.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright May 23, 2017 Grega Mohorko
 */

namespace BlueDB\Utility;

use DateTime;

abstract class DateTimeUtility
{
	/**
	 * Determines whether the provided two DateTimes are on the same time (ignores date).
	 * 
	 * @param DateTime $dateTime1
	 * @param DateTime $dateTime2
	 * @param bool
	 */
	public static function areOnTheSameTime($dateTime1,$dateTime2)
	{
		return $dateTime1->format("H:i:s")===$dateTime2->format("H:i:s");
	}
	
	/**
	 * Determines whether the provided two dates are on the same day (including month and year).
	 * 
	 * @param DateTime $dateTime1
	 * @param DateTime $dateTime2
	 * @param bool
	 */
	public static function areOnTheSameDay($dateTime1,$dateTime2)
	{
		return $dateTime1->format("Y-m-d")===$dateTime2->format("Y-m-d");
	}
}
