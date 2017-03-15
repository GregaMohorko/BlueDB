<?php

/*
 * IDatabaseTable.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

interface IDatabaseTable
{
	/**
	 * @return string
	 */
	static function getTableName();
}
