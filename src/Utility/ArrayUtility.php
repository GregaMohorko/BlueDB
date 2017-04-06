<?php

/*
 * ArrayUtility.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 4, 2017 Grega Mohorko
 */

namespace BlueDB\Utility;

abstract class ArrayUtility
{
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
