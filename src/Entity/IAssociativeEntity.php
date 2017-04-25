<?php

/*
 * IAssociativeEntity.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

interface IAssociativeEntity extends IDatabaseTable
{
	/**
	 * @return string
	 */
	static function getSideA();
	
	/**
	 * @return string
	 */
	static function getSideB();
	
	/**
	 * Loads a list of entities for the provided origin side.
	 * 
	 * For example: If origin side A is provided, objects of type B will be loaded. And vice versa.
	 * 
	 * @param string $originSide
	 * @param int $ID
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclOneToMany [optional]
	 * @return array
	 */
	static function loadListForSide($originSide,$ID,$fields=null,$fieldsToIgnore=null,$inclOneToMany=null);
	
	/**
	 * Loads a list of entities by criteria for the provided origin side.
	 * 
	 * For example: If origin side A is provided, objects of type B will be loaded. And vice versa.
	 * 
	 * @param string $originSide
	 * @param int $ID
	 * @param Criteria $criteria
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclOneToMany [optional]
	 * @return array
	 */
	static function loadListForSideByCriteria($mySQLConnection,$originSide,$ID,$criteria,$fields=null,$fieldsToIgnore=null,$inclOneToMany=null);
	
	/**
	 * @param IFieldEntity $AObject
	 * @param IFieldEntity $BObject
	 * @param bool $beginTransaction [optional]
	 * @param bool $commit [optional]
	 */
	static function link($AObject,$BObject,$beginTransaction=true,$commit=true);
	
	/**
	 * @param IFieldEntity $BObject
	 * @param array $AList
	 * @param boolean $beginTransaction [optional]
	 * @param boolean $commit [optional]
	 */
	static function linkMultipleA($BObject,$AList,$beginTransaction=true,$commit=true);
	
	/**
	 * @param IFieldEntity $AObject
	 * @param array $BList
	 * @param boolean $beginTransaction [optional]
	 * @param boolean $commit [optional]
	 */
	static function linkMultipleB($AObject,$BList,$beginTransaction=true,$commit=true);
	
	/**
	 * @param IFieldEntity $AObject
	 * @param IFieldEntity $BObject
	 * @param boolean $beginTransaction [optional]
	 * @param boolean $commit [optional]
	 */
	static function unlink($AObject,$BObject,$beginTransaction=true,$commit=true);
	
	/**
	 * @param IFieldEntity $BObject
	 * @param array $AList
	 * @param boolean $beginTransaction [optional]
	 * @param boolean $commit [optional]
	 */
	static function unlinkMultipleA($BObject,$AList,$beginTransaction=true,$commit=true);
	
	/**
	 * @param IFieldEntity $AObject
	 * @param array $BList
	 * @param boolean $beginTransaction [optional]
	 * @param boolean $commit [optional]
	 */
	static function unlinkMultipleB($AObject,$BList,$beginTransaction=true,$commit=true);
}
