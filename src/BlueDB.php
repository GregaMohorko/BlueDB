<?php

/* 
 * BlueDB.php
 * 
 * Bootstrap file for BlueDB library.
 * 
 * Version 1.1.0.0
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

// load configuration file
$config=parse_ini_file("config.ini");
if(!$config)
	throw new Exception("Could not parse config.ini configuration file.");

// initialize
require_once 'Configuration/BlueDBProperties.php';
\BlueDB\Configuration\BlueDBProperties::init($config);

// include all files
require_once 'DataAccess/Criteria/Expression.php';
require_once 'DataAccess/Criteria/Criteria.php';
require_once 'DataAccess/JoinType.php';
require_once 'DataAccess/Joiner.php';
require_once 'DataAccess/MySQL.php';
require_once 'DataAccess/Session.php';
require_once 'Entity/FieldTypeEnum.php';
require_once 'Entity/PropertyTypeEnum.php';
require_once 'Entity/QueryTypeEnum.php';
require_once 'Entity/PropertyCreator.php';
require_once 'Entity/PropertySanitizer.php';
require_once 'Entity/PropertyComparer.php';
require_once 'Entity/IDatabaseTable.php';
require_once 'Entity/IFieldEntity.php';
require_once 'Entity/ISubEntity.php';
require_once 'Entity/IAssociative.php';
require_once 'Entity/IAssociativeEntity.php';
require_once 'Entity/IAssociativeTable.php';
require_once 'Entity/DatabaseTable.php';
require_once 'Entity/FieldEntity.php';
require_once 'Entity/StrongEntity.php';
require_once 'Entity/SubEntity.php';
require_once 'Entity/AssociativeTrait.php';
require_once 'Entity/AssociativeEntityTrait.php';
require_once 'Entity/AssociativeTable.php';
require_once 'Entity/StrongAssociativeEntity.php';
require_once 'Entity/SubAssociativeEntity.php';
require_once 'IO/JSON.php';
require_once 'Utility/ArrayUtility.php';
require_once 'Utility/DateTimeUtility.php';
require_once 'Utility/EntityUtility.php';
require_once 'Utility/StringUtility.php';
