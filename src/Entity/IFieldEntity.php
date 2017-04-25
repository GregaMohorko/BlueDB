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
	 * @return IFieldEntity
	 */
	static function loadByID($ID,$fields=null,$fieldsToIgnore=null,$inclOneToMany=true);
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclOneToMany [optional]
	 * @return IFieldEntity
	 */
	static function loadByCriteria($criteria,$fields=null,$fieldsToIgnore=null,$inclOneToMany=true);
	
	/**
	 * Is the same as calling loadListByCriteria with $criteria=null.
	 * 
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclOneToMany [optional]
	 * @return array
	 */
	static function loadList($fields=null,$fieldsToIgnore=null,$inclOneToMany=true);
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclOneToMany [optional]
	 * @return array
	 */
	static function loadListByCriteria($criteria,$fields=null,$fieldsToIgnore=null,$inclOneToMany=true);
	
	/**
	 * Does not save ManyToOne fields, only sets the ID.
	 * Does not save OneToMany & ManyToMany fields.
	 * 
	 * @param IFieldEntity $fieldEntity
	 * @param boolean $beginTransaction [optional]
	 * @param boolean $commit [optional]
	 */
	static function save($fieldEntity,$beginTransaction=true,$commit=true);
	
	/**
	 * Does not save ManyToOne fields, only sets the ID.
	 * Does not save OneToMany & ManyToMany fields.
	 * 
	 * @param array $fieldEntities
	 * @param boolean $beginTransaction [optional]
	 * @param boolean $commit [optional]
	 */
	static function saveList($fieldEntities,$beginTransaction=true,$commit=true);
	
	/**
	 * Does not update ManyToOne fields, only sets the ID.
	 * Does not update OneToMany & ManyToMany fields.
	 * 
	 * @param IFieldEntity $fieldEntity
	 * @param boolean $beginTransaction [optional]
	 * @param boolean $commit [optional]
	 * @param array $fields [optional]
	 * @param bool $updateParents [optional] Only important for SubEntities. It determines whether to update parent tables.
	 */
	static function update($fieldEntity,$beginTransaction=true,$commit=true,$fields=null,$updateParents=true);
	
	/**
	 * Does not update ManyToOne fields, only sets the ID.
	 * Does not update OneToMany & ManyToMany fields.
	 * 
	 * @param array $fieldEntities
	 * @param boolean $beginTransaction [optional]
	 * @param boolean $commit [optional]
	 * @param array $fields [optional]
	 * @param bool $updateParents [optional] Only important for SubEntities. It determines whether to update parent tables.
	 */
	static function updateList($fieldEntities,$beginTransaction=true,$commit=true,$fields=null,$updateParents=true);
	
	/**
	 * Does not delete child ManyToOne fields.
	 * 
	 * @param IFieldEntity $fieldEntity
	 * @param boolean $beginTransaction [optional]
	 * @param boolean $commit [optional]
	 */
	static function delete($fieldEntity,$beginTransaction=true,$commit=true);
	
	/**
	 * @param array $fieldEntities
	 * @param boolean $beginTransaction [optional]
	 * @param boolean $commit [optional]
	 */
	static function deleteList($fieldEntities,$beginTransaction=true,$commit=true);
	
	/**
	 * Only allowed for property type fields.
	 * 
	 * @param string $field
	 * @param mixed $value
	 * @return boolean TRUE if the provided value exists in the provided fields column in the called entity table.
	 */
	static function exists($field,$value);
	
	/**
	 * @param Criteria $criteria
	 * @return boolean TRUE if an entry exists that meets criterias restrictions.
	 */
	static function existsByCriteria($criteria);
}
