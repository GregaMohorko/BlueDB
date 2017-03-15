<?php

/*
 * StringUtility.php
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
	 * @return boolean
	 */
	public static function startsWith($haystack,$needle)
	{
		return substr($haystack,0,strlen($needle))===$needle;
	}
	
	/**
	 * @param string $haystack
	 * @param string $needle
	 * @return boolean
	 */
	public static function endsWith($haystack,$needle)
	{
		if(($length=strlen($needle))===0)
			return true;

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
