<?php

/* 
 * BlueDB.php
 * 
 * Includes all files needed by this library.
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

// load configuration file
$config=parse_ini_file("config.ini");
if(!$config)
	throw new Exception("Could not parse config.ini configuration file.");

require_once 'Configuration/BlueDBProperties.php';
\BlueDB\Configuration\BlueDBProperties::init($config);

require_once 'DataAccess/Criteria/JoinType.php';
require_once 'DataAccess/Criteria/Expression.php';
require_once 'DataAccess/Criteria/Criteria.php';
require_once 'DataAccess/MySQL.php';
require_once 'DataAccess/Session.php';
require_once 'Entity/FieldTypeEnum.php';
require_once 'Entity/PropertyTypeEnum.php';
require_once 'Entity/QueryTypeEnum.php';
require_once 'Entity/PropertyTypeCreator.php';
require_once 'Entity/IDatabaseTable.php';
require_once 'Entity/IFieldEntity.php';
require_once 'Entity/IAssociativeEntity.php';
require_once 'Entity/ISubEntity.php';
require_once 'Entity/FieldEntity.php';
require_once 'Entity/StrongEntity.php';
require_once 'Entity/SubEntity.php';
require_once 'Utility/ArrayUtility.php';
require_once 'Utility/StringUtility.php';
