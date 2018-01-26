<?php

/* 
 * BlueDB.php
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
 * Bootstrap file for BlueDB library.
 * 
 * Version 1.2.1.0
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

define("BLUEDB_DIR", __DIR__."/");

// load configuration file
$config=parse_ini_file(BLUEDB_DIR."config.ini");
if(!$config){
	throw new Exception("Could not parse config.ini configuration file.");
}

// initialize
require_once BLUEDB_DIR.'Configuration/BlueDBProperties.php';
\BlueDB\Configuration\BlueDBProperties::init($config);

// include all files
require_once BLUEDB_DIR.'DataAccess/Criteria/Expression.php';
require_once BLUEDB_DIR.'DataAccess/Criteria/Criteria.php';
require_once BLUEDB_DIR.'DataAccess/JoinType.php';
require_once BLUEDB_DIR.'DataAccess/Joiner.php';
require_once BLUEDB_DIR.'DataAccess/MySQL.php';
require_once BLUEDB_DIR.'DataAccess/Session.php';
require_once BLUEDB_DIR.'Entity/FieldTypeEnum.php';
require_once BLUEDB_DIR.'Entity/PropertyTypeEnum.php';
require_once BLUEDB_DIR.'Entity/QueryTypeEnum.php';
require_once BLUEDB_DIR.'Entity/PropertyCreator.php';
require_once BLUEDB_DIR.'Entity/PropertySanitizer.php';
require_once BLUEDB_DIR.'Entity/PropertyComparer.php';
require_once BLUEDB_DIR.'Entity/IDatabaseTable.php';
require_once BLUEDB_DIR.'Entity/IFieldEntity.php';
require_once BLUEDB_DIR.'Entity/ISubEntity.php';
require_once BLUEDB_DIR.'Entity/IAssociative.php';
require_once BLUEDB_DIR.'Entity/IAssociativeEntity.php';
require_once BLUEDB_DIR.'Entity/DatabaseTable.php';
require_once BLUEDB_DIR.'Entity/FieldEntity.php';
require_once BLUEDB_DIR.'Entity/StrongEntity.php';
require_once BLUEDB_DIR.'Entity/SubEntity.php';
require_once BLUEDB_DIR.'Entity/AssociativeTrait.php';
require_once BLUEDB_DIR.'Entity/AssociativeEntityTrait.php';
require_once BLUEDB_DIR.'Entity/AssociativeTable.php';
require_once BLUEDB_DIR.'Entity/StrongAssociativeEntity.php';
require_once BLUEDB_DIR.'Entity/SubAssociativeEntity.php';
require_once BLUEDB_DIR.'IO/JSON.php';
require_once BLUEDB_DIR.'Utility/ArrayUtility.php';
require_once BLUEDB_DIR.'Utility/DateTimeUtility.php';
require_once BLUEDB_DIR.'Utility/EntityUtility.php';
require_once BLUEDB_DIR.'Utility/StringUtility.php';
