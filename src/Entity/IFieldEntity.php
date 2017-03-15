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
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @return IFieldEntity
	 */
	static function loadByID($ID,$fields=null,$fieldsToIgnore=null,$inclOneToMany=false);
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @return IFieldEntity
	 */
	static function loadByCriteria($criteria,$fields=null,$fieldsToIgnore=null,$inclOneToMany=false);
	
	/**
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @return array
	 */
	static function loadList($fields=null,$fieldsToIgnore=null,$inclOneToMany=false);
	
	/**
	 * @param Criteria $criteria
	 * @param array $fields
	 * @param array $fieldsToIgnore
	 * @param bool $inclOneToMany
	 * @return array
	 */
	static function loadListByCriteria($criteria,$fields=null,$fieldsToIgnore=null,$inclOneToMany=false);
	
	/**
	 * Does not save ManyToOne fields, only sets the ID.
	 * 
	 * @param IFieldEntity $fieldEntity
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 * @param bool $inclOneToMany
	 */
	static function save(&$fieldEntity,$beginTransaction=true,$commit=true,$inclOneToMany=false);
	
	/**
	 * Does not save ManyToOne fields, only sets the ID.
	 * 
	 * @param array $fieldEntities
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 * @param bool $inclOneToMany
	 */
	static function saveList($fieldEntities,$beginTransaction=true,$commit=true,$inclOneToMany=false);
	
	/**
	 * Does not update ManyToOne fields, only sets the ID.
	 * 
	 * @param IFieldEntity $fieldEntity
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 * @param array $fields
	 * @param bool $inclOneToMany
	 */
	static function update($fieldEntity,$beginTransaction=true,$commit=true,$fields=null,$inclOneToMany=false);
	
	/**
	 * Does not update ManyToOne fields, only sets the ID.
	 * 
	 * @param array $fieldEntities
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 * @param array $fields
	 * @param bool $inclOneToMany
	 */
	static function updateList($fieldEntities,$beginTransaction=true,$commit=true,$fields=null,$inclOneToMany=false);
	
	/**
	 * Does not delete child ManyToOne fields.
	 * 
	 * @param IFieldEntity $fieldEntity
	 * @param boolean $beginTransaction
	 * @param boolean $commit
	 */
	static function delete($fieldEntity,$beginTransaction=true,$commit=true);
	
	/**
	 * @param array $fieldEntities
	 * @param boolean $beginTransaction
	 * @param boolean $commit
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
