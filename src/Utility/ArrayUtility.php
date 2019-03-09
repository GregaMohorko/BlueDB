<?php

/*
 * ArrayUtility.php
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
 * @copyright Apr 4, 2017 Grega Mohorko
 */

namespace BlueDB\Utility;

abstract class ArrayUtility
{
	/**
	 * Returns the first value from this array.
	 * This method can be useful when keys of the array are not integers.
	 * 
	 * @param array $array
	 * @return mixed
	 */
	public static function first($array)
	{
		return array_values($array)[0];
	}
	
	/**
	 * Merge two arrays.
	 * 
	 * @param array $array1 Initial array to merge.
	 * @param array $array2 Array to append.
	 * @return array The resulting array.
	 */
	public static function mergeTwo($array1,$array2)
	{
		return array_merge($array1, $array2);
	}
}
