<?php

/*
 * DateTimeUtility.php
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
