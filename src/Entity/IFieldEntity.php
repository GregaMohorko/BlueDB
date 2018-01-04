<?php

/*
 * IFieldEntity.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

interface IFieldEntity extends IDatabaseTable
{
	/**
	 * @return int
	 */
	function getID();
	
	/**
	 * @param int $ID
	 */
	function setID($ID);
	
	/**
	 * Converts this field entity into an array that can be encoded to JSON.
	 * 
	 * When possible, use \BlueDB\IO\JSON::toArray().
	 * 
	 * @param array $fieldsToIgnore [optional]
	 * @return array
	 */
	function toArray($fieldsToIgnore=null);
	
	/**
	 * Encodes this field entity into a JSON string.
	 * 
	 * When possible, use \BlueDB\IO\JSON::encode().
	 * 
	 * @param array $fieldsToIgnore [optional]
	 * @return string A JSON encoded string.
	 * @throws Exception
	 */
	function toJson($fieldsToIgnore=null);
	
	/**
	 * Converts provided field entities into an array that can be encoded to JSON.
	 * 
	 * When possible, use \BlueDB\IO\JSON::toArray().
	 * 
	 * @param array $entities Field entities to be converted.
	 * @param array $fieldsToIgnore [optional]
	 * @return array
	 */
	static function toArrayList($entities,$fieldsToIgnore=null);
	
	/**
	 * Encodes provided field entities to a JSON string.
	 * 
	 * When possible, use \BlueDB\IO\JSON::encode().
	 * 
	 * @param array $entities Field entities to be encoded.
	 * @param array $fieldsToIgnore [optional]
	 * @return string A JSON encoded string.
	 * @throws Exception
	 */
	static function toJsonList($entities,$fieldsToIgnore=null);
	
	/**
	 * Decodes provided array into entities.
	 * 
	 * Note that the array must be in a correct format.
	 * 
	 * @param array $array
	 * @return array|FieldEntity A single or an array of entities.
	 */
	static function fromArray($array);
	
	/**
	 * Decodes provided JSON string.
	 * 
	 * Note that the JSON must be in a correct format.
	 * 
	 * @param string $json A JSON encoded string.
	 * @return array|FieldEntity A single or an array of entities.
	 * @throws Exception
	 */
	static function fromJson($json);
	
	/**
	 * Creates an empty instance of the called entity class. Especially useful for SubEntity types for initializing parents.
	 * 
	 * @return IFieldEntity
	 */
	static function createEmpty();
	
	/**
	 * @return string
	 */
	static function getIDColumn();
	
	/**
	 * @return array
	 */
	static function getFieldList();
	
	/**
	 * @param int $ID
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return IFieldEntity
	 */
	static function loadByID($ID,$fields=null,$fieldsToIgnore=null,$inclOneToMany=null,$inclManyToMany=null);
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return IFieldEntity
	 */
	static function loadByCriteria($criteria,$fields=null,$fieldsToIgnore=null,$inclOneToMany=null,$inclManyToMany=null);
	
	/**
	 * Is the same as calling loadListByCriteria with $criteria=null.
	 * 
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return array
	 */
	static function loadList($fields=null,$fieldsToIgnore=null,$inclOneToMany=null,$inclManyToMany=null);
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return array
	 */
	static function loadListByCriteria($criteria,$fields=null,$fieldsToIgnore=null,$inclOneToMany=null,$inclManyToMany=null);
	
	/**
	 * Does not save ManyToOne fields, only sets the ID.
	 * Does not save OneToMany & ManyToMany fields.
	 * 
	 * @param IFieldEntity $fieldEntity
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	static function save($fieldEntity,$beginTransaction=true,$commit=true);
	
	/**
	 * Does not save ManyToOne fields, only sets the ID.
	 * Does not save OneToMany & ManyToMany fields.
	 * 
	 * @param array $fieldEntities
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	static function saveList($fieldEntities,$beginTransaction=true,$commit=true);
	
	/**
	 * Does not update ManyToOne fields, only sets the ID.
	 * Does not update OneToMany & ManyToMany fields.
	 * 
	 * @param IFieldEntity $fieldEntity
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 * @param array $fields [optional]
	 * @param bool $updateParents [optional] Only important for SubEntities. It determines whether to update parent tables.
	 */
	static function update($fieldEntity,$beginTransaction=true,$commit=true,$fields=null,$updateParents=true);
	
	/**
	 * Does not update ManyToOne fields, only sets the ID.
	 * Does not update OneToMany & ManyToMany fields.
	 * 
	 * @param array $fieldEntities
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 * @param array $fields [optional]
	 * @param bool $updateParents [optional] Only important for SubEntities. It determines whether to update parent tables.
	 */
	static function updateList($fieldEntities,$beginTransaction=true,$commit=true,$fields=null,$updateParents=true);
	
	/**
	 * Does not delete child ManyToOne fields.
	 * 
	 * @param IFieldEntity $fieldEntity
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	static function delete($fieldEntity,$beginTransaction=true,$commit=true);
	
	/**
	 * @param array $fieldEntities
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	static function deleteList($fieldEntities,$beginTransaction=true,$commit=true);
	
	/**
	 * Only allowed for property type fields.
	 * 
	 * @param string $field
	 * @param mixed $value
	 * @return bool TRUE if the provided value exists in the provided fields column in the called entity table.
	 */
	static function exists($field,$value);
	
	/**
	 * @param Criteria $criteria
	 * @return bool TRUE if an entry exists that meets criterias restrictions.
	 */
	static function existsByCriteria($criteria);
}
