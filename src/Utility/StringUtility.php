<?php

/*
 * StringUtility.php
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
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\Utility;

abstract class StringUtility
{
	/**
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 */
	public static function startsWith($haystack,$needle)
	{
		return substr($haystack,0,strlen($needle))===$needle;
	}
	
	/**
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 */
	public static function endsWith($haystack,$needle)
	{
		if(($length=strlen($needle))===0){
			return true;
		}

		return (substr($haystack, -$length)===$needle);
	}
	
	/**
	 * Inserts the insertion into the provided string at the specified index.
	 * 
	 * @param string $string
	 * @param string $insertion
	 * @param int $index
	 * @return string
	 */
	public static function insert($string,$insertion,$index)
	{
		return substr_replace($string, $insertion, $index, 0);
	}
}
