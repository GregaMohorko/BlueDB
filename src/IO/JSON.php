<?php

/*
 * JSON.php
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
 * @copyright May 22, 2017 Grega Mohorko
 */

namespace BlueDB\IO;

use BlueDB\Entity\FieldEntity;

/**
 * This static class is used to encode and decode entities.
 */
abstract class JSON
{
	/**
	 * Encodes provided field entities to a JSON string.
	 * 
	 * @param array|FieldEntity $entities A single or an array of field entities to be encoded.
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $includeHiddenFields [optional] Defaults to FALSE.
	 * @return string A JSON encoded string.
	 * @throws Exception
	 */
	public static function encode($entities,$fieldsToIgnore=null,$includeHiddenFields=false)
	{
		if(!is_array($entities)){
			// is a single entity
			/* @var $entities FieldEntity */
			return $entities->toJson($fieldsToIgnore,$includeHiddenFields);
		}
		return FieldEntity::toJsonList($entities,$fieldsToIgnore,$includeHiddenFields);
	}
	
	/**
	 * Converts provided field entities into an array that can be encoded to JSON.
	 * 
	 * @param array|FieldEntity $entities A single or an array of field entities to be converted.
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $includeHiddenFields [optional] Defaults to FALSE.
	 * @return array
	 */
	public static function toArray($entities,$fieldsToIgnore=null, $includeHiddenFields=false)
	{
		if(!is_array($entities)){
			// is a single entity
			/* @var $fieldsToIgnore FieldEntity */
			return $entities->toArray($fieldsToIgnore,$includeHiddenFields);
		}
		return FieldEntity::toArrayList($entities, $fieldsToIgnore,$includeHiddenFields);
	}
	
	/**
	 * Decodes provided JSON string.
	 * 
	 * Note that the JSON must be in a correct format.
	 * 
	 * @param string $json A JSON encoded string.
	 * @return array|FieldEntity A single or an array of entities.
	 * @throws Exception
	 */
	public static function decode($json)
	{
		return FieldEntity::fromJson($json);
	}
	
	/**
	 * Decodes provided array.
	 * 
	 * Note that the array must be in a correct format.
	 * 
	 * @param array $array Array produced by \BlueDB\IO\JSON::toArray().
	 * @return array|FieldEntity A single or an array of entities.
	 */
	public static function fromArray($array)
	{
		return FieldEntity::fromArray($array);
	}
}
